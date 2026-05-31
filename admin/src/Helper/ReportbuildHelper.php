<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\Pc\PhotoThumbnailer;
use CWM\Component\Cwmconnect\Site\Helper\PhotoAccess;
use CWM\Component\Cwmconnect\Site\Service\DirectoryPdfPresenter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Report builder — streams CSV / KML / missing-photos reports straight to
 * the response body and exits.
 *
 * @since  2.0.0
 */
class ReportbuildHelper
{
    /**
     * @var \Joomla\Database\DatabaseInterface
     * @since 2.0.0
     */
    private \Joomla\Database\DatabaseInterface $db;

    /**
     * Constructor.
     *
     * @throws \Exception
     * @since  2.0.0
     */
    public function __construct()
    {
        $this->db = Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Stream a CSV report and exit.
     *
     * @param   array<int, object>  $items   Member rows to render.
     * @param   string              $report  File-name stem.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getCsv(array $items, string $report): void
    {
        $date = new Date('now');

        Factory::getApplication()->clearHeaders();
        @ob_end_clean();
        @ob_start();

        $stem = preg_replace('/[^A-Za-z0-9._-]/', '_', $report);

        header('Content-type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=report_' . $stem . '_' . $date->format('Y-m-d-His') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $csv   = fopen('php://output', 'w');
        $count = 0;

        foreach ($items as $line) {
            foreach ($line as $c => $item) {
                if (\in_array($c, ['params', 'kml_params', 'category_params', 'metadata'], true)) {
                    $reg    = new Registry();
                    $reg->loadString((string) $item);
                    $params = $reg->toObject();
                    unset($line->{$c});
                    $line = (object) array_merge((array) $line, (array) $params);
                } elseif ($c === 'attribs') {
                    $reg       = new Registry();
                    $reg->loadString((string) $item);
                    $params    = $reg->toObject();
                    $paramsAtt = new \stdClass();

                    foreach ((array) $params as $p => $itemP) {
                        $key = 'att_' . $p;

                        if ($p === 'sex') {
                            $paramsAtt->{$key} = match ((int) $itemP) {
                                0       => 'M',
                                1       => 'F',
                                default => $itemP,
                            };
                        } else {
                            $paramsAtt->{$key} = $itemP;
                        }
                    }

                    unset($line->attribs);
                    $line = (object) array_merge((array) $line, (array) $paramsAtt);
                } elseif ($c === 'con_position') {
                    $line = (object) array_merge((array) $line, ['con_position' => $this->renderPositionNames((string) $item)]);
                } elseif ($c === 'image' && !empty($item)) {
                    $line->{$c} = Uri::root() . $item;
                }
            }

            if ($count === 0) {
                $headers = array_keys(get_object_vars($line));
                fputcsv($csv, $headers);
            }

            $count = 1;
            fputcsv($csv, (array) $line);
        }

        @ob_flush();
        @flush();

        fclose($csv);
        Factory::getApplication()->close();
    }

    /**
     * Stream a KML document and exit.
     *
     * @param   array<int, object>  $items   Member rows to render.
     * @param   string|null         $report  Optional file-name stem.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getKml(array $items, ?string $report = null): void
    {
        $dbHelper = new DbHelper();
        $kmlInfo  = $dbHelper->getKmlSettings();

        if ($kmlInfo === null) {
            // No KML seed row — bail out silently rather than streaming a
            // half-built document.
            Factory::getApplication()->close();

            return;
        }

        Factory::getApplication()->clearHeaders();
        @ob_end_flush();

        /** @var Registry $kmlParams */
        $kmlParams = $kmlInfo->params;

        $kml   = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2"'
            . ' xmlns:atom="http://www.w3.org/2005/Atom">';
        $kml[] = '<Document>';
        $kml[] = '<name>' . htmlspecialchars((string) $kmlInfo->name, ENT_XML1) . '</name>';
        $kml[] = '<open>' . (int) $kmlParams->get('open', 0) . '</open>';
        $kml[] = '<description><![CDATA[' . (string) $kmlInfo->description . ']]></description>';
        $kml[] = '<LookAt>';
        $kml[] = '<longitude>' . (string) $kmlInfo->lng . '</longitude>';
        $kml[] = '<latitude>' . (string) $kmlInfo->lat . '</latitude>';
        $kml[] = '<altitude>' . (string) $kmlParams->get('altitude', 0) . '</altitude>';
        $kml[] = '<range>' . (string) $kmlParams->get('range', 0) . '</range>';
        $kml[] = '<tilt>' . (string) $kmlParams->get('tilt', 0) . '</tilt>';
        $kml[] = '<heading>' . (string) $kmlParams->get('heading', 0) . '</heading>';
        $kml[] = '<gx:altitudeMode>' . (string) $kmlParams->get('gxaltitudeMode', 'absolute') . '</gx:altitudeMode>';
        $kml[] = '</LookAt>';
        $kml[] = (string) $kmlInfo->style;
        $kml   = array_merge($kml, $this->buildKmlCategories());

        $byCat   = $this->groupBy($items, 'category_title');
        $grouped = [];

        foreach ($byCat as $catName => $rows) {
            $grouped[$catName] = $this->groupBy($rows, 'suburb');
        }

        // entry-name => absolute source path, bundled into the KMZ so Google
        // Earth resolves photos from inside the archive (the live photo dir is
        // blocked from direct web access).
        $photoFiles = [];
        $counter    = 0;

        foreach ($grouped as $catName => $bySuburb) {
            $kml[] = '<Folder id="' . $counter . '">';
            $kml[] = '<name>' . htmlspecialchars((string) $catName, ENT_XML1) . '</name>';
            $kml[] = '<open>' . (int) $kmlParams->get('mcropen', 0) . '</open>';

            $firstSuburbKey = key($bySuburb);

            if (
                $firstSuburbKey !== null
                && isset($bySuburb[$firstSuburbKey][0]->category_description)
            ) {
                $kml[] = '<description><![CDATA[' . (string) $bySuburb[$firstSuburbKey][0]->category_description . ']]></description>';
            }

            $counter++;

            foreach ($bySuburb as $suburb => $rows) {
                $counter++;
                $kml[] = '<Folder id="' . $counter . '">';
                $kml[] = '<name>' . htmlspecialchars((string) $suburb, ENT_XML1) . '</name>';
                $kml[] = '<open>' . (int) $kmlParams->get('msropen', 0) . '</open>';

                foreach ($rows as $row) {
                    $counter++;
                    $rowParams = $row->params instanceof Registry ? $row->params : new Registry();

                    $kml[] = '<Placemark id="placemark' . $counter . '">';
                    $kml[] = '<name>' . htmlspecialchars((string) $row->name, ENT_XML1) . '</name>';
                    $kml[] = '<styleUrl>#stylemap' . (int) $row->catid . '</styleUrl>';
                    $kml[] = '<visibility>' . (int) $rowParams->get('visibility', 0) . '</visibility>';
                    $kml[] = '<open>' . (int) $rowParams->get('open', 0) . '</open>';
                    $kml[] = '<gx:balloonVisibility>' . (int) $rowParams->get('gxballoonvisibility', 0) . '</gx:balloonVisibility>';
                    $kml[] = '<address><![CDATA[';

                    if (!empty($row->address)) {
                        $kml[] = $row->address . ',<br />';
                    }

                    $kml[] = ($row->suburb ?? '') . ', ' . ($row->state ?? '') . ' ' . ($row->postcode ?? '');
                    $kml[] = ']]></address>';
                    $kml[] = '<phoneNumber>' . htmlspecialchars((string) ($row->telephone ?? ''), ENT_XML1) . '</phoneNumber>';
                    $kml[] = '<Snippet maxLines="' . (int) $kmlParams->get('rmaxlines', 2) . '">…</Snippet>';
                    $kml[] = '<description><![CDATA[<div style="padding: 10px;">';

                    $photoPath = $this->kmzPhotoPath($row);

                    if ($photoPath !== null) {
                        $entry = 'files/' . (int) $row->id . '.' . strtolower(pathinfo($photoPath, \PATHINFO_EXTENSION));
                    } else {
                        $entry       = 'files/_placeholder.jpg';
                        $placeholder = PhotoAccess::placeholderPath();
                        $photoPath   = $placeholder;
                    }

                    if ($photoPath !== null) {
                        $photoFiles[$entry] = $photoPath;
                        $kml[]              = '<img src="' . htmlspecialchars($entry, ENT_XML1) . '" alt="Photo" width="100" height="100" /><br />';
                    }

                    if (!empty($row->con_position)) {
                        $kml[] = '<b>Position:</b> ' . $this->renderPositionNames((string) $row->con_position) . '<br />';
                    }

                    if (!empty($row->spouse)) {
                        $kml[] = 'Spouse: ' . $row->spouse . '<br />';
                    }

                    if (!empty($row->children)) {
                        $kml[] = 'Children: ' . $row->children . '<br />';
                    }

                    if (!empty($row->misc)) {
                        $kml[] = $row->misc;
                    }

                    if (!empty($row->telephone)) {
                        $kml[] = '<br />PH: ' . $row->telephone;
                    }

                    if (!empty($row->fax)) {
                        $kml[] = '<br />Fax: ' . $row->fax;
                    }

                    if (!empty($row->mobile)) {
                        $kml[] = '<br />Cell: ' . $row->mobile;
                    }

                    if (!empty($row->email_to)) {
                        $kml[] = '<br />Email: <a href="mailto:' . $row->email_to . '">' . $row->email_to . '</a>';
                    }

                    $kml[] = '</div>]]></description>';
                    $kml[] = '<Point>';
                    $kml[] = '<coordinates>' . ($row->lng ?? 0) . ',' . ($row->lat ?? 0) . ',0</coordinates>';
                    $kml[] = '</Point>';
                    $kml[] = '</Placemark>';
                }

                $kml[] = '</Folder>';
            }

            $kml[] = '</Folder>';
        }

        $kml[] = '</Document>';
        $kml[] = '</kml>';

        $filename = $report ?: (string) $kmlInfo->alias;
        $stem     = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'directory';

        // Bundle the KML + photos into a self-contained KMZ so Google Earth can
        // render photos offline without reaching the (blocked) live photo dir.
        $tmpKmz = tempnam(sys_get_temp_dir(), 'cwmkmz');
        $zip    = new \ZipArchive();

        if ($tmpKmz === false || $zip->open($tmpKmz, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create the KMZ archive.');
        }

        $zip->addFromString('doc.kml', implode("\n", $kml));

        foreach ($photoFiles as $entry => $source) {
            $zip->addFile($source, $entry);
        }

        $zip->close();

        header('Content-Type: application/vnd.google-earth.kmz');
        header('Content-Disposition: attachment; filename="' . $stem . '.kmz"');
        header('Content-Length: ' . (string) (filesize($tmpKmz) ?: 0));

        readfile($tmpKmz);
        @unlink($tmpKmz);

        Factory::getApplication()->close();
    }

    /**
     * Resolve a member's photo to an absolute path to bundle into the KMZ,
     * preferring the small 3:4 thumbnail (generated on demand to keep the
     * archive small), or null when there is no usable image.
     *
     * @param   object  $row  Member row.
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function kmzPhotoPath(object $row): ?string
    {
        $image = trim((string) ($row->image ?? ''));

        if ($image === '') {
            return null;
        }

        $thumb = JPATH_ROOT . '/media/com_cwmconnect/photos/thumb/' . PhotoThumbnailer::thumbFilename($image);

        if (is_file($thumb)) {
            return $thumb;
        }

        $full = PhotoAccess::resolvePath($image);

        if ($full === null) {
            return null;
        }

        return new PhotoThumbnailer()->generate($full, $thumb) ? $thumb : $full;
    }

    /**
     * Generate a print directory PDF via mpdf and save to the exports
     * directory. Returns the relative path to the generated file so the
     * caller can redirect to a download link.
     *
     * @param   array<int, object>  $items          Member rows.
     * @param   string|null         $report         Optional file-name stem.
     * @param   bool                $includeHidden  Include display_in_directory=0 rows (admin override, spec §17).
     *
     * @return  string  Relative path under JPATH_ROOT to the generated PDF.
     *
     * @throws  \RuntimeException
     * @since   __DEPLOY_VERSION__
     */
    public function getPdf(array $items = [], ?string $report = null, bool $includeHidden = false): string
    {
        // A full-directory PDF (hundreds of members + embedded photos) is
        // memory- and time-hungry in mpdf. Raise the ceilings for this request
        // so a large render can't die mid-build with an uncatchable fatal
        // (which leaves no file and no feedback). Never lower an unlimited cap.
        @set_time_limit(0);

        if ((string) @ini_get('memory_limit') !== '-1') {
            @ini_set('memory_limit', '1024M');
        }

        $autoload = JPATH_LIBRARIES . '/mpdf/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new \RuntimeException('The mPDF library (lib_mpdf) is not installed.');
        }

        require_once $autoload;

        $exportsDir = JPATH_ROOT . '/media/com_cwmconnect/exports';

        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0o755, true);
        }

        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_cwmconnect');

        // The shared template uses COM_CWMCONNECT_PDF_* strings; they're mirrored
        // into the admin language file (see com_cwmconnect.ini) because this runs
        // in the administrator app, which doesn't load the site language file.

        // Same shared view-model + template as the front-end self-service PDF,
        // so the admin print uses the configured layout and normalized photos.
        // Admin print is a working document: no cover/staff section, and it can
        // flag hidden rows (spec §17 override).
        $presenter                     = new DirectoryPdfPresenter();
        $presenter->items              = array_values($items);
        $presenter->showSectionHeaders = (bool) $params->get('pdf_section_headers', 1);
        $presenter->pdfLayout          = (string) $params->get('pdf_layout', 'photo_detail');
        $presenter->appendRoster       = (bool) $params->get('pdf_append_roster', 0);
        $presenter->showHiddenBadges   = $includeHidden;
        // Admin print carries the title/date in the running header + footer
        // (SetHeader/SetFooter below), so suppress the duplicate body title.
        $presenter->showTitleBlock     = false;
        $presenter->appearance         = [
            'fontBasePt' => match ((string) $params->get('pdf_font_size', 'normal')) {
                'large'  => 12.0,
                'xlarge' => 14.0,
                default  => 10.0,
            },
        ];

        $config = [
            'mode'          => 'utf-8',
            'format'        => $params->get('pdf_page_size', 'letter') === 'booklet' ? [139.7, 215.9] : 'Letter',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 16,
            'margin_bottom' => 14,
            'margin_header' => 6,
            'margin_footer' => 6,
            'tempDir'       => $app->get('tmp_path', sys_get_temp_dir()),
        ];

        if ($params->get('pdf_color', 'color') === 'bw') {
            $config['restrictColorSpace'] = 1;
        }

        try {
            $mpdf = new \Mpdf\Mpdf($config);

            $title = 'Church Member Directory';
            $mpdf->SetTitle($title);
            $mpdf->SetAuthor('CWM Connect');
            $mpdf->SetHeader($title . '|' . ($includeHidden ? '{STAFF COPY}' : '') . '|Page {PAGENO}');
            $mpdf->SetFooter('Generated ' . date('F j, Y') . '||' . \count($items) . ' members');

            $mpdf->WriteHTML($presenter->renderHtml());
        } catch (\Mpdf\MpdfException $e) {
            throw new \RuntimeException('PDF rendering failed: ' . $e->getMessage());
        }

        $stem     = preg_replace('/[^A-Za-z0-9._-]/', '_', $report ?: 'directory') ?: 'directory';
        $filename = $stem . '-' . date('Y-m-d-His') . '.pdf';
        $fullPath = $exportsDir . '/' . $filename;

        $mpdf->Output($fullPath, \Mpdf\Output\Destination::FILE);

        return 'media/com_cwmconnect/exports/' . $filename;
    }

    /**
     * Stream a CSV listing every member or family-unit with a missing photo,
     * then exit.
     *
     * @param   array<int, object>  $items   Member rows.
     * @param   string|null         $report  Optional file-name stem.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getMissingPhotos(array $items, ?string $report = null): void
    {
        $date = new Date('now');

        Factory::getApplication()->clearHeaders();
        @ob_end_clean();
        @ob_start();

        $stem = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($report ?? 'missing'));

        header('Content-type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=report_' . $stem . '_' . $date->format('Y-m-d-His') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $csv = fopen('php://output', 'w');

        fputcsv($csv, ['Missing Images - ' . Factory::getApplication()->get('sitename')]);
        fputcsv($csv, [' ']);
        fputcsv($csv, ['ID', 'Name']);

        foreach ($items as $member) {
            if (empty($member->image)) {
                fputcsv($csv, [(int) $member->id, (string) $member->name]);
            }
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName(['id', 'name', 'image']))
            ->from($this->db->quoteName('#__cwmconnect_familyunit'));
        $this->db->setQuery($query);
        $families = $this->db->loadObjectList() ?: [];

        fputcsv($csv, ['', '']);
        fputcsv($csv, ['ID', 'Family Name']);

        foreach ($families as $family) {
            if (empty($family->image)) {
                fputcsv($csv, [(int) $family->id, (string) $family->name]);
            }
        }

        @ob_flush();
        @flush();

        fclose($csv);
        Factory::getApplication()->close();
    }

    /**
     * Build the KML <Style>/<StyleMap> blocks for every Cwmconnect
     * category that has an icon configured.
     *
     * @return  array<int, string>
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    private function buildKmlCategories(): array
    {
        $string = [];

        $query = $this->db->createQuery()
            ->select('*')
            ->from($this->db->quoteName('#__categories'))
            ->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_cwmconnect'));
        $this->db->setQuery($query);
        $cats = $this->db->loadObjectList() ?: [];

        foreach ($cats as $cat) {
            $params = new Registry();
            $params->loadString((string) ($cat->params ?? ''));

            if (!$params->get('image')) {
                continue;
            }

            $string[] = '<Style id="style' . (int) $cat->id . '">';
            $string[] = '<IconStyle>';
            $string[] = '<Icon><href>' . Uri::base() . $params->get('image') . '</href></Icon>';
            $string[] = '<hotSpot x="0.5" y="0.5" xunits="fraction" yunits="fraction"/>';
            $string[] = '</IconStyle>';
            $string[] = '<ListStyle></ListStyle>';
            $string[] = '</Style>';

            $string[] = '<StyleMap id="stylemap' . (int) $cat->id . '">';
            $string[] = '<Pair><key>normal</key><styleUrl>#style' . (int) $cat->id . '</styleUrl></Pair>';
            $string[] = '<Pair><key>highlight</key><styleUrl>#style' . (int) $cat->id . '</styleUrl></Pair>';
            $string[] = '</StyleMap>';
        }

        return $string;
    }

    /**
     * Render a comma-separated list of position names from a stored
     * `con_position` value (a CSV of position ids).
     *
     * @param   string  $value  The raw value.
     *
     * @return  string
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    private function renderPositionNames(string $value): string
    {
        if ($value === '' || $value === '0') {
            return '';
        }

        $ids = array_filter(array_map('intval', explode(',', $value)));

        if ($ids === []) {
            return '';
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('name'))
            ->from($this->db->quoteName('#__cwmconnect_position'))
            ->whereIn($this->db->quoteName('id'), $ids);
        $this->db->setQuery($query);

        $names = $this->db->loadColumn() ?: [];

        return implode(',', $names);
    }

    /**
     * Group a list of objects by a single field. Mirrors the legacy
     * `ChurchDirectoryRenderHelper::groupit` behaviour.
     *
     * @param   array<int, object>  $items  The items to group.
     * @param   string              $field  The field on each item to group by.
     *
     * @return  array<string, array<int, object>>
     *
     * @since   2.0.0
     */
    private function groupBy(array $items, string $field): array
    {
        $result = [];

        foreach ($items as $item) {
            $key = !empty($item->{$field}) ? (string) $item->{$field} : 'nomatch';
            $result[$key][] = $item;
        }

        ksort($result);

        return $result;
    }
}
