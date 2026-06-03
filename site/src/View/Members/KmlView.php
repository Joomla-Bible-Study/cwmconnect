<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Members;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Helper\DbHelper;
use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use CWM\Component\Cwmconnect\Site\Model\MembersModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * KML 2.2 feed for the member directory.
 *
 * Modern features:
 *  - ExtendedData with typed fields (searchable in Google Earth)
 *  - BalloonStyle template with $[field] substitution
 *  - Category → suburb folder hierarchy
 *  - Per-category pin icons from Joomla category params
 *  - LookAt camera from #__cwmconnect_kml settings
 *  - Rich HTML balloon with photo, position, household, contact
 *  - NetworkLink wrapper for auto-refreshing feeds
 *
 * Dual auth: session for logged-in users, `?token=` for external clients.
 *
 * @see     https://developers.google.com/kml/documentation/kmlreference
 * @since   __DEPLOY_VERSION__
 */
class KmlView extends BaseHtmlView
{
    /**
     * Category IDs that have a custom icon — built during style generation,
     * read during placemark generation.
     *
     * @var    array<int, true>
     * @since  __DEPLOY_VERSION__
     */
    private array $categoryIconMap = [];

    /**
     * The feed token for this request (empty for session users). Threaded into
     * placemark photo URLs so an external client (Google Earth) can fetch them
     * through the gated photo proxy.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    private string $feedToken = '';

    /**
     * @param   string|null  $tpl  Unused.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app   = Factory::getApplication();
        $token = (string) $app->getInput()->getString('token', '');

        $this->feedToken = $token;

        $isLoggedIn = (int) ($app->getIdentity()?->id ?? 0) > 0;

        if (!$isLoggedIn && $token === '') {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_KML_FEED_TOKEN_INVALID'), 403);
        }

        if ($token !== '') {
            $db       = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $service  = new FeedTokenService($db);
            $tokenRow = $service->validate($token);

            if ($tokenRow === null) {
                throw new \RuntimeException(Text::_('COM_CWMCONNECT_KML_FEED_TOKEN_INVALID'), 403);
            }

            $service->touchLastUsed((int) $tokenRow->id);
        }

        if ((bool) $app->getInput()->getInt('networklink', 0)) {
            $this->streamNetworkLink($app, $token);

            return;
        }

        /** @var MembersModel $model */
        $model = $this->getModel();

        // Trigger populateState() before overriding: it runs lazily inside
        // getItems() and (via parent ListModel) resets list.limit to the
        // menu/global default (20), so without this the feed caps at one page.
        $model->getState();
        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);

        $items      = $model->getItems() ?: [];
        $kmlSettings = new DbHelper()->getKmlSettings();
        $kml        = $this->buildKml($items, $kmlSettings);

        $app->setHeader('Content-Type', 'application/vnd.google-earth.kml+xml; charset=UTF-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="church-directory.kml"', true);
        $app->setHeader('Cache-Control', 'private, max-age=0, must-revalidate', true);
        $app->sendHeaders();

        echo $kml;
        $app->close();
    }

    /**
     * Stream a NetworkLink wrapper that auto-refreshes every 15 minutes.
     *
     * @param   object  $app    Application.
     * @param   string  $token  Cleartext token for the data URL.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function streamNetworkLink(object $app, string $token): void
    {
        $dataUrl = Uri::root() . 'index.php?option=com_cwmconnect&view=members&format=kml';

        if ($token !== '') {
            $dataUrl .= '&token=' . urlencode($token);
        }

        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
        $lines[] = '<Document>';
        $lines[] = '  <name>' . $this->esc(Text::_('COM_CWMCONNECT_KML_DOCUMENT_NAME')) . '</name>';
        $lines[] = '  <NetworkLink>';
        $lines[] = '    <name>' . $this->esc(Text::_('COM_CWMCONNECT_KML_NETWORKLINK_NAME')) . '</name>';
        $lines[] = '    <refreshVisibility>1</refreshVisibility>';
        $lines[] = '    <Link>';
        $lines[] = '      <href>' . $this->esc($dataUrl) . '</href>';
        $lines[] = '      <refreshMode>onInterval</refreshMode>';
        $lines[] = '      <refreshInterval>900</refreshInterval>';
        $lines[] = '    </Link>';
        $lines[] = '  </NetworkLink>';
        $lines[] = '</Document>';
        $lines[] = '</kml>';

        $app->setHeader('Content-Type', 'application/vnd.google-earth.kml+xml; charset=UTF-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="church-directory-live.kml"', true);
        $app->sendHeaders();

        echo implode("\n", $lines);
        $app->close();
    }

    /**
     * Build the full KML document.
     *
     * @param   list<object>  $items        Member rows.
     * @param   object|null   $kmlSettings  Row from #__cwmconnect_kml, or null.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildKml(array $items, ?object $kmlSettings): string
    {
        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2">';
        $lines[] = '<Document>';

        $docName = $kmlSettings !== null && !empty($kmlSettings->name)
            ? (string) $kmlSettings->name
            : Text::_('COM_CWMCONNECT_KML_DOCUMENT_NAME');
        $lines[] = '<name>' . $this->esc($docName) . '</name>';

        $docDesc = $kmlSettings !== null && !empty($kmlSettings->description)
            ? strip_tags((string) $kmlSettings->description)
            : Text::_('COM_CWMCONNECT_KML_DOCUMENT_DESC');
        $lines[] = '<description>' . $this->esc(trim($docDesc)) . '</description>';
        $lines[] = '<open>1</open>';

        $lookAt = $this->buildLookAt($kmlSettings);

        if ($lookAt !== '') {
            $lines[] = $lookAt;
        }

        $balloonFragment = $this->buildBalloonFragment();
        $lines[]         = $this->buildDefaultStyle($balloonFragment);
        $lines[]         = $this->buildCategoryStyles($items, $balloonFragment);

        $categories = $this->groupBy($items, 'category_title');

        foreach ($categories as $catName => $catItems) {
            $lines[] = '<Folder>';
            $lines[] = '  <name>' . $this->esc((string) $catName) . '</name>';
            $lines[] = '  <open>0</open>';

            $suburbs = $this->groupBy($catItems, 'suburb');

            foreach ($suburbs as $suburbName => $suburbItems) {
                $lines[] = '  <Folder>';
                $lines[] = '    <name>' . $this->esc((string) $suburbName) . '</name>';
                $lines[] = '    <open>0</open>';

                foreach ($suburbItems as $item) {
                    $placemark = $this->buildPlacemark($item);

                    if ($placemark !== '') {
                        $lines[] = $placemark;
                    }
                }

                $lines[] = '  </Folder>';
            }

            $lines[] = '</Folder>';
        }

        $lines[] = '</Document>';
        $lines[] = '</kml>';

        return implode("\n", $lines);
    }

    /**
     * Build the LookAt camera element from KML settings.
     *
     * @param   object|null  $kmlSettings  Row from #__cwmconnect_kml.
     *
     * @return  string  Empty string if no settings available.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildLookAt(?object $kmlSettings): string
    {
        if ($kmlSettings === null) {
            return '';
        }

        $lat = (float) ($kmlSettings->lat ?? 0);
        $lng = (float) ($kmlSettings->lng ?? 0);

        if ($lat === 0.0 && $lng === 0.0) {
            return '';
        }

        /** @var Registry $params */
        $params = $kmlSettings->params;

        $lines   = [];
        $lines[] = '<LookAt>';
        $lines[] = '  <longitude>' . $lng . '</longitude>';
        $lines[] = '  <latitude>' . $lat . '</latitude>';
        $lines[] = '  <altitude>' . (float) $params->get('altitude', 0) . '</altitude>';
        $lines[] = '  <range>' . (float) $params->get('range', 5000) . '</range>';
        $lines[] = '  <tilt>' . (float) $params->get('tilt', 0) . '</tilt>';
        $lines[] = '  <heading>' . (float) $params->get('heading', 0) . '</heading>';

        $altMode = (string) $params->get('gxaltitudeMode', '');

        if ($altMode !== '') {
            $lines[] = '  <gx:altitudeMode>' . $this->esc($altMode) . '</gx:altitudeMode>';
        }

        $lines[] = '</LookAt>';

        return implode("\n", $lines);
    }

    /**
     * Return the BalloonStyle XML fragment (without wrapping Style element)
     * so it can be composed into both the default and per-category styles.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildBalloonFragment(): string
    {
        return '<BalloonStyle><text><![CDATA['
            . '<div style="font-family:sans-serif;font-size:13px;min-width:280px;padding:10px;">'
            . '<div style="display:flex;gap:10px;align-items:flex-start;">'
            . '<div style="flex-shrink:0;">$[cwm_photo]</div>'
            . '<div>'
            . '<div style="font-size:16px;font-weight:bold;margin-bottom:4px;">$[name]</div>'
            . '$[cwm_position_html]'
            . '$[cwm_household_html]'
            . '</div>'
            . '</div>'
            . '<hr style="border:0;border-top:1px solid #ddd;margin:8px 0;"/>'
            . '<table style="font-size:12px;border-collapse:collapse;width:100%;">'
            . '$[cwm_contact_rows]'
            . '</table>'
            . '$[cwm_address_html]'
            . '</div>'
            . ']]></text></BalloonStyle>';
    }

    /**
     * Build the default pin style (used when a category has no custom icon).
     *
     * @param   string  $balloonFragment  BalloonStyle XML fragment.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildDefaultStyle(string $balloonFragment): string
    {
        return '<Style id="memberPin">'
            . '<IconStyle><scale>1.0</scale>'
            . '<Icon><href>https://maps.google.com/mapfiles/kml/paddle/red-circle.png</href></Icon>'
            . '</IconStyle>'
            . $balloonFragment
            . '</Style>';
    }

    /**
     * Generate per-category Style/StyleMap elements from category images.
     *
     * @param   list<object>  $items            Member rows with category_params.
     * @param   string        $balloonFragment  BalloonStyle XML fragment.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildCategoryStyles(array $items, string $balloonFragment): string
    {
        $seen  = [];
        $lines = [];

        foreach ($items as $item) {
            $catId = (int) ($item->catid ?? 0);

            if ($catId <= 0 || isset($seen[$catId])) {
                continue;
            }

            $seen[$catId] = true;
            $params       = new Registry((string) ($item->category_params ?? ''));
            $image        = (string) $params->get('image', '');

            if ($image === '') {
                continue;
            }

            $imageUrl = Uri::root() . $image;

            $lines[] = '<Style id="style' . $catId . '">'
                . '<IconStyle>'
                . '<Icon><href>' . $this->esc($imageUrl) . '</href></Icon>'
                . '<hotSpot x="0.5" y="0.5" xunits="fraction" yunits="fraction"/>'
                . '</IconStyle>'
                . $balloonFragment
                . '</Style>';
            $lines[] = '<StyleMap id="stylemap' . $catId . '">'
                . '<Pair><key>normal</key><styleUrl>#style' . $catId . '</styleUrl></Pair>'
                . '<Pair><key>highlight</key><styleUrl>#style' . $catId . '</styleUrl></Pair>'
                . '</StyleMap>';

            $this->categoryIconMap[$catId] = true;
        }

        return implode("\n", $lines);
    }

    /**
     * Build a single Placemark with ExtendedData.
     *
     * @param   object  $item  Member row.
     *
     * @return  string  Empty string if member has no coordinates.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildPlacemark(object $item): string
    {
        $lat = (float) ($item->lat ?? 0);
        $lng = (float) ($item->lng ?? 0);

        if ($lat === 0.0 && $lng === 0.0) {
            return '';
        }

        $fullName = trim(($item->name ?? '') . ' ' . ($item->lname ?? ''));

        // Photos are served through the gated proxy (direct access is blocked),
        // so an external client must carry the feed token to fetch them.
        $photoUrl = Uri::root() . 'index.php?option=com_cwmconnect&task=photo.serve&id=' . (int) $item->id;

        if ($this->feedToken !== '') {
            $photoUrl .= '&token=' . urlencode($this->feedToken);
        }

        $photoHtml = !empty($item->image)
            ? '<img src="' . htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8')
                . '" width="80" height="80" style="border-radius:6px;object-fit:cover;" />'
            : '<div style="width:80px;height:80px;background:#e0e0e0;border-radius:6px;'
                . 'display:flex;align-items:center;justify-content:center;font-size:28px;color:#999;">'
                . mb_strtoupper(mb_substr($fullName, 0, 1)) . '</div>';

        $posHtml = '';

        if (!empty($item->con_position) && $item->con_position !== '-1') {
            $posHtml = '<div style="color:#666;font-size:12px;">'
                . htmlspecialchars((string) $item->con_position, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $householdHtml = '';

        if (!empty($item->household_name)) {
            $householdHtml = '<div style="color:#888;font-size:11px;">'
                . htmlspecialchars((string) $item->household_name, ENT_QUOTES, 'UTF-8') . ' household</div>';
        }

        $contactRows = '';
        $contactRows .= $this->contactRow('Email', $item->email_to ?? '', true);
        $contactRows .= $this->contactRow('Phone', $item->telephone ?? '');
        $contactRows .= $this->contactRow('Mobile', $item->mobile ?? '');
        $contactRows .= $this->contactRow('Fax', $item->fax ?? '');

        if (!empty($item->spouse)) {
            $contactRows .= $this->contactRow('Spouse', (string) $item->spouse);
        }

        if (!empty($item->children)) {
            $contactRows .= $this->contactRow('Children', (string) $item->children);
        }

        $address     = $this->buildAddress($item);
        $addressHtml = $address !== ''
            ? '<div style="margin-top:6px;font-size:11px;color:#555;">'
                . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '</div>'
            : '';

        $catId    = (int) ($item->catid ?? 0);
        $styleUrl = isset($this->categoryIconMap[$catId])
            ? '#stylemap' . $catId
            : '#memberPin';

        $lines   = [];
        $lines[] = '<Placemark>';
        $lines[] = '  <name>' . $this->esc($fullName) . '</name>';
        $lines[] = '  <styleUrl>' . $styleUrl . '</styleUrl>';

        if ($address !== '') {
            $lines[] = '  <address>' . $this->esc($address) . '</address>';
        }

        if (!empty($item->telephone)) {
            $lines[] = '  <phoneNumber>' . $this->esc((string) $item->telephone) . '</phoneNumber>';
        }

        $lines[] = '  <ExtendedData>';
        $lines[] = '    <Data name="cwm_photo"><value>' . $this->esc($photoHtml) . '</value></Data>';
        $lines[] = '    <Data name="cwm_position_html"><value>' . $this->esc($posHtml) . '</value></Data>';
        $lines[] = '    <Data name="cwm_household_html"><value>' . $this->esc($householdHtml) . '</value></Data>';
        $lines[] = '    <Data name="cwm_contact_rows"><value>' . $this->esc($contactRows) . '</value></Data>';
        $lines[] = '    <Data name="cwm_address_html"><value>' . $this->esc($addressHtml) . '</value></Data>';

        if (!empty($item->email_to)) {
            $lines[] = '    <Data name="email"><value>' . $this->esc((string) $item->email_to) . '</value></Data>';
        }

        if (!empty($item->telephone)) {
            $lines[] = '    <Data name="phone"><value>' . $this->esc((string) $item->telephone) . '</value></Data>';
        }

        if (!empty($item->household_name)) {
            $lines[] = '    <Data name="household"><value>' . $this->esc((string) $item->household_name) . '</value></Data>';
        }

        $lines[] = '  </ExtendedData>';
        $lines[] = '  <Point>';
        $lines[] = '    <coordinates>' . $lng . ',' . $lat . ',0</coordinates>';
        $lines[] = '  </Point>';
        $lines[] = '</Placemark>';

        return implode("\n", $lines);
    }

    /**
     * @param   string  $label    Row label.
     * @param   string  $value    Value.
     * @param   bool    $isEmail  Wrap in mailto link.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function contactRow(string $label, string $value, bool $isEmail = false): string
    {
        if ($value === '') {
            return '';
        }

        $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $display = $isEmail
            ? '<a href="mailto:' . $escaped . '">' . $escaped . '</a>'
            : $escaped;

        return '<tr><td style="padding:2px 8px 2px 0;color:#888;white-space:nowrap;">' . $label
            . '</td><td style="padding:2px 0;">' . $display . '</td></tr>';
    }

    /**
     * @param   object  $item  Member row.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildAddress(object $item): string
    {
        return implode(', ', array_filter([
            (string) ($item->address ?? ''),
            (string) ($item->suburb ?? ''),
            (string) ($item->state ?? ''),
            (string) ($item->postcode ?? ''),
            (string) ($item->country ?? ''),
        ]));
    }

    /**
     * @param   list<object>  $items  Items to group.
     * @param   string        $field  Field name.
     *
     * @return  array<string, list<object>>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function groupBy(array $items, string $field): array
    {
        $result = [];

        foreach ($items as $item) {
            $key = !empty($item->{$field}) ? (string) $item->{$field} : 'Other';
            $result[$key][] = $item;
        }

        ksort($result);

        return $result;
    }

    /**
     * @param   string  $text  Raw text.
     *
     * @return  string  XML-escaped.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
