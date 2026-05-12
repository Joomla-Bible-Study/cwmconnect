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

        $counter = 0;

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

                    if (empty($row->image)) {
                        $kml[] = '<img src="' . Uri::base() . 'media/com_cwmconnect/images/photo_not_available.jpg" alt="Photo" width="100" height="100" /><br />';
                    } else {
                        $kml[] = '<img src="' . Uri::base() . $row->image . '" alt="Photo" width="100" height="100" /><br />';
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

        $filename = $report ?: (string) $kmlInfo->alias;
        $stem     = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

        header('Content-type: application/vnd.google-earth.kml+xml');
        header('Content-Disposition: attachment; filename="' . $stem . '.kml"');

        $kml[] = '</Document>';
        $kml[] = '</kml>';

        echo implode("\n", $kml);

        Factory::getApplication()->close();
    }

    /**
     * Stub — PDF export not yet implemented.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function getPdf(): void
    {
        echo 'Coming Soon';
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

        $query = $this->db->getQuery(true)
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

        $query = $this->db->getQuery(true)
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

        $query = $this->db->getQuery(true)
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
