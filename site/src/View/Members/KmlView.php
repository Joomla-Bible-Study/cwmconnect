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
 * Phase J: KML feed for the filtered member directory.
 *
 * Dual auth: logged-in Joomla users access the feed via their session;
 * external KML clients (Google Earth, etc.) pass `?token=<cleartext>`
 * which is validated against `#__cwmconnect_feed_tokens`. The site
 * Dispatcher already exempts this route from the login wall.
 *
 * KML 2.2 output follows https://developers.google.com/kml/documentation/kmlreference
 *
 * @since  __DEPLOY_VERSION__
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
        $user  = $app->getIdentity();
        $token = (string) $app->getInput()->getString('token', '');

        $isLoggedIn = (int) ($user?->id ?? 0) > 0;

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
     * Build a KML 2.2 document from member items.
     *
     * @param   list<object>  $items  Member rows with lat, lng, name, etc.
     *
     * @return  string  Complete KML XML document.
     *
     * @see     https://developers.google.com/kml/documentation/kmlreference
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

        $lines[] = '<Style id="memberPin">';
        $lines[] = '  <IconStyle><Icon><href>https://maps.google.com/mapfiles/kml/pushpin/ylw-pushpin.png</href></Icon></IconStyle>';
        $lines[] = '  <BalloonStyle><text><![CDATA[$[description]]]></text></BalloonStyle>';
        $lines[] = '</Style>';

        foreach ($items as $item) {
            $lat = (float) ($item->lat ?? 0);
            $lng = (float) ($item->lng ?? 0);

            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }

            $name = trim(($item->name ?? '') . ' ' . ($item->lname ?? ''));

            $lines[] = '<Placemark>';
            $lines[] = '  <name>' . $this->esc($name) . '</name>';
            $lines[] = '  <styleUrl>#memberPin</styleUrl>';
            $lines[] = '  <description><![CDATA[' . $this->buildBalloon($item) . ']]></description>';

            $address = $this->buildAddress($item);

            if ($address !== '') {
                $lines[] = '  <address>' . $this->esc($address) . '</address>';
            }

            if (!empty($item->telephone)) {
                $lines[] = '  <phoneNumber>' . $this->esc((string) $item->telephone) . '</phoneNumber>';
            }

            $lines[] = '  <Point>';
            $lines[] = '    <coordinates>' . $lng . ',' . $lat . ',0</coordinates>';
            $lines[] = '  </Point>';
            $lines[] = '</Placemark>';
        }

        $lines[] = '</Document>';
        $lines[] = '</kml>';

        return implode("\n", $lines);
    }

    /**
     * Build the HTML balloon content for a placemark.
     *
     * @param   object  $item  Member row.
     *
     * @return  string  HTML fragment for CDATA.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildBalloon(object $item): string
    {
        $html = '<div style="font-family:sans-serif;font-size:13px;padding:8px;">';
        $name = trim(($item->name ?? '') . ' ' . ($item->lname ?? ''));
        $html .= '<strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong><br/>';

        if (!empty($item->email_to)) {
            $email = htmlspecialchars((string) $item->email_to, ENT_QUOTES, 'UTF-8');
            $html .= '<a href="mailto:' . $email . '">' . $email . '</a><br/>';
        }

        if (!empty($item->telephone)) {
            $html .= 'Phone: ' . htmlspecialchars((string) $item->telephone, ENT_QUOTES, 'UTF-8') . '<br/>';
        }

        if (!empty($item->mobile)) {
            $html .= 'Mobile: ' . htmlspecialchars((string) $item->mobile, ENT_QUOTES, 'UTF-8') . '<br/>';
        }

        $address = $this->buildAddress($item);

        if ($address !== '') {
            $html .= htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
        }

        $html .= '</div>';

        return $html;
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
