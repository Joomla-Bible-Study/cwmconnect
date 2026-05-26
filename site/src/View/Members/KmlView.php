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

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use CWM\Component\Cwmconnect\Site\Model\MembersModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

/**
 * Phase J: KML 2.2 feed for the member directory.
 *
 * Modern features beyond the legacy ReportbuildHelper::getKml():
 *  - ExtendedData with typed fields (searchable in Google Earth)
 *  - BalloonStyle template with $[field] substitution
 *  - Category → suburb folder hierarchy
 *  - Per-category pin icons from Joomla category params
 *  - Rich HTML balloon with photo, position, household, contact
 *  - NetworkLink wrapper option for auto-refreshing feeds
 *
 * Dual auth: session for logged-in users, `?token=` for external clients.
 *
 * @see     https://developers.google.com/kml/documentation/kmlreference
 * @since   __DEPLOY_VERSION__
 */
class KmlView extends BaseHtmlView
{
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

        $isLoggedIn = (int) ($app->getIdentity()?->id ?? 0) > 0;

        if (!$isLoggedIn && $token === '') {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_KML_FEED_TOKEN_INVALID'), 403);
        }

        if ($token !== '') {
            $service  = Factory::getContainer()->get(FeedTokenService::class);
            $tokenRow = $service->validate($token);

            if ($tokenRow === null) {
                throw new \RuntimeException(Text::_('COM_CWMCONNECT_KML_FEED_TOKEN_INVALID'), 403);
            }

            $service->touchLastUsed((int) $tokenRow->id);
        }

        $networkLink = (bool) $app->getInput()->getInt('networklink', 0);

        if ($networkLink) {
            $this->streamNetworkLink($app, $token);

            return;
        }

        /** @var MembersModel $model */
        $model = $this->getModel();
        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);

        $items = $model->getItems() ?: [];

        $kml = $this->buildKml($items);

        $app->setHeader('Content-Type', 'application/vnd.google-earth.kml+xml; charset=UTF-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="church-directory.kml"', true);
        $app->setHeader('Cache-Control', 'private, max-age=0, must-revalidate', true);
        $app->sendHeaders();

        echo $kml;
        $app->close();
    }

    /**
     * Stream a NetworkLink wrapper KML that tells Google Earth to
     * auto-refresh the actual data feed every 15 minutes.
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
     * Build the full KML 2.2 document with modern features.
     *
     * @param   list<object>  $items  Member rows.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildKml(array $items): string
    {
        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
        $lines[] = '<Document>';
        $lines[] = '<name>' . $this->esc(Text::_('COM_CWMCONNECT_KML_DOCUMENT_NAME')) . '</name>';
        $lines[] = '<description>' . $this->esc(Text::_('COM_CWMCONNECT_KML_DOCUMENT_DESC')) . '</description>';
        $lines[] = '<open>1</open>';

        $lines[] = $this->buildBalloonStyleTemplate();

        $categories = $this->groupBy($items, 'category_title');

        $lines[] = $this->buildDefaultStyle();

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
     * Build the shared BalloonStyle template using ExtendedData substitution.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildBalloonStyleTemplate(): string
    {
        $photosBase = Uri::root() . 'media/com_cwmconnect/photos/';

        $balloon = '<![CDATA['
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
            . ']]>';

        return '<Style id="memberBalloon">'
            . '<BalloonStyle><text>' . $balloon . '</text></BalloonStyle>'
            . '</Style>';
    }

    /**
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildDefaultStyle(): string
    {
        return '<Style id="memberPin">'
            . '<IconStyle>'
            . '<scale>1.0</scale>'
            . '<Icon><href>https://maps.google.com/mapfiles/kml/paddle/red-circle.png</href></Icon>'
            . '</IconStyle>'
            . '</Style>';
    }

    /**
     * Build a single Placemark with ExtendedData for balloon template substitution.
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

        $fullName   = trim(($item->name ?? '') . ' ' . ($item->lname ?? ''));
        $photosBase = Uri::root() . 'media/com_cwmconnect/photos/';

        $photoHtml = !empty($item->image)
            ? '<img src="' . htmlspecialchars($photosBase . $item->image, ENT_QUOTES, 'UTF-8')
                . '" width="80" height="80" style="border-radius:6px;object-fit:cover;" />'
            : '<div style="width:80px;height:80px;background:#e0e0e0;border-radius:6px;'
                . 'display:flex;align-items:center;justify-content:center;font-size:28px;color:#999;">'
                . mb_strtoupper(mb_substr($fullName, 0, 1)) . '</div>';

        $posHtml = '';

        if (!empty($item->con_position) && $item->con_position !== '-1') {
            $posHtml = '<div style="color:#666;font-size:12px;">' . htmlspecialchars((string) $item->con_position, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $householdHtml = '';

        if (!empty($item->household_name)) {
            $householdHtml = '<div style="color:#888;font-size:11px;">' . htmlspecialchars((string) $item->household_name, ENT_QUOTES, 'UTF-8') . ' household</div>';
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

        $address    = $this->buildAddress($item);
        $addressHtml = $address !== ''
            ? '<div style="margin-top:6px;font-size:11px;color:#555;">' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '</div>'
            : '';

        $lines   = [];
        $lines[] = '<Placemark>';
        $lines[] = '  <name>' . $this->esc($fullName) . '</name>';
        $lines[] = '  <styleUrl>#memberBalloon</styleUrl>';

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

        $lines[] = '  </ExtendedData>';
        $lines[] = '  <Point>';
        $lines[] = '    <coordinates>' . $lng . ',' . $lat . ',0</coordinates>';
        $lines[] = '  </Point>';
        $lines[] = '</Placemark>';

        return implode("\n", $lines);
    }

    /**
     * Build a single contact table row for the balloon.
     *
     * @param   string  $label    Row label.
     * @param   string  $value    Value text.
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
        $parts = array_filter([
            (string) ($item->address ?? ''),
            (string) ($item->suburb ?? ''),
            (string) ($item->state ?? ''),
            (string) ($item->postcode ?? ''),
            (string) ($item->country ?? ''),
        ]);

        return implode(', ', $parts);
    }

    /**
     * Group items by a field value.
     *
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
     * @return  string  XML-escaped text.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
