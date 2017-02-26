<?php
/**
 * Reports Builder
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Class ChurchDirectoryReportBuild
 *
 * @since  1.7.10
 */
class ChurchDirectoryReportBuild
{
	/**
	 * Private
	 *
	 * @var JDatabaseDriver
	 *
	 * @since 1.7.10
	 */
	private $db;

	/**
	 * ChurchDirectoryReportBuild constructor.
	 *
	 * @since  1.7.10
	 */
	public function __construct()
	{
		if (!$this->db)
		{
			$this->db = JFactory::getDbo();
		}
	}

	/**
	 * CVS Dump
	 *
	 * @param   object  $items   Items to pass through
	 * @param   string  $report  Name of report to return.
	 *
	 * @return bool
	 *
	 * @since    1.7.0
	 */
	public function getCsv($items, $report)
	{
		$date = new JDate('now');
		$jWeb = new JApplicationWeb;

		$csv   = fopen('php://output', 'w');
		$jWeb->clearHeaders();

		// Clean the output buffer,
		@ob_end_clean();
		@ob_start();

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=report." . $report . '.' . $date->format('Y-m-d-His') . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		$count = 0;

		foreach ($items as $line)
		{
			foreach ($line as $c => $item)
			{
				if ($c == 'params')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toObject();
					unset($line->params);
					$line = (object) array_merge((array) $line, (array) $params);
				}
				elseif ($c == 'attribs')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params     = $reg->toObject();
					$params_att = new stdClass;

					foreach ($params as $p => $item_p)
					{
						$p = 'att_' . $p;

						if ($p == 'sex')
						{
							switch ($item_p)
							{
								case (0):
									$params_att->$p = 'M';
									break;
								case (1):
									$params_att->$p = 'F';
									break;
							}
						}
						else
						{
							$params_att->$p = $item_p;
						}
					}

					unset($line->attribs);
					$line = (object) array_merge((array) $line, (array) $params_att);
				}
				elseif ($c == 'kml_params')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toObject();
					unset($line->kml_params);
					$line = (object) array_merge((array) $line, (array) $params);
				}
				elseif ($c == 'category_params')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toObject();
					unset($line->category_params);
					$line = (object) array_merge((array) $line, (array) $params);
				}
				elseif ($c == 'metadata')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toObject();
					unset($line->metadata);
					$line = (object) array_merge((array) $line, (array) $params);
				}
				elseif ($c == 'con_position')
				{
					$pos = [];

					if ($item != 0)
					{
						$positions = explode(',', $item);

						foreach ($positions as $p => $position)
						{
							$query = $this->db->getQuery(true);

							// Join on Position.
							$query->select('name');
							$query->from('#__churchdirectory_position');
							$query->where('id =' . $position);
							$this->db->setQuery($query);
							$pos[] = $this->db->loadResult();
						}
					}
					else
					{
						$pos[] = null;
					}

					unset($line->con_position);
					$line = (object) array_merge((array) $line, ['con_position' => implode(",", $pos)]);
				}
				elseif ($c == 'image')
				{
					$line->$c = JUri::root() . $item;
				}
			}

			if ($count == 0)
			{
				$array = get_object_vars($line);
				fputcsv($csv, array_keys($array));
			}

			$count = 1;
			fputcsv($csv, (array) $line);
		}

		@ob_flush();
		@flush();

		fclose($csv);
		exit;
	}

	/**
	 * PDF export
	 *
	 * @param   object  $items   Items to pass through
	 * @param   string  $report  Name of report to return.
	 *
	 * @return bool
	 *
	 * @since    1.7.0
	 */
	public function getPDF($items, $report = null)
	{
		// Hold
		return false;
	}

	/**
	 * PDF export
	 *
	 * @param   object  $items   Items to pass through
	 * @param   string  $report  Name of report to return.
	 *
	 * @return bool
	 *
	 * @since    1.7.0
	 */
	public function getKML($items, $report = null)
	{
		$renderHelper = new ChurchDirectoryRenderHelper;
		$dbhelp       = new ChurchDirectoryDB;
		$kmlinfo      = $dbhelp->getKMLdb();

		/** @var Joomla\Registry\Registry $ckml_params */
		$ckml_params  = $kmlinfo->params;

		// Creates an array of strings to hold the lines of the KML file.
		$kml   = ['<?xml version="1.0" encoding="UTF-8"?>'];
		$kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2"'
			. ' xmlns:atom="http://www.w3.org/2005/Atom">';
		$kml[] = '<Document>';
		$kml[] = '<name>' . $kmlinfo->name . '</name>';
		$kml[] = '<open>' . $kmlinfo->params->get('open') . '</open>';
		$kml[] = '<LookAt>
    		 <longitude>' . $kmlinfo->lng . '</longitude>
    		 <latitude>' . $kmlinfo->lat . '</latitude>
		 <altitude>' . $kmlinfo->params->get('altitude') . '</altitude>
		 <range>' . $kmlinfo->params->get('range') . '</range>
		 <tilt>' . $kmlinfo->params->get('tilt') . '</tilt>
		 <heading>' . $kmlinfo->params->get('heading') . '</heading>
		 <gx:altitudeMode>' . $kmlinfo->params->get('gxaltitudeMode') . '</gx:altitudeMode>
  	     </LookAt>    <!-- Camera or LookAt -->';
		$kml[] = $kmlinfo->style;
		$kml[] = '<Style id="text_photo_banner">';
		$kml[] = '<IconStyle>';
		$kml[] = '<scale>';

		if ($items[0]->params->get('icscale') == null)
		{
			$kml[] = '1.1';
		}
		else
		{
			$kml[] = $kmlinfo->params->get('icscale');
		}

		$kml[] = '</scale>';
		$kml[] = '<Icon>';
		$kml[] = '<href>';

		if ($items[0]->category_params->get('image') === null)
		{
			$kml[] = JUri::base() . 'media/com_churchdirectory/images/kml_icons/iconb.png';
		}
		else
		{
			$kml[] = JUri::base() . $items[0]->category_params->get('image');
		}

		$kml[] = '</href>';
		$kml[] = '</Icon>';
		$kml[] = '<hotSpot x="0.5" y="0.5" xunits="fraction" yunits="fraction"/>';
		$kml[] = '</IconStyle>';
		$kml[] = '<LabelStyle>';
		$kml[] = '<scale>';

		if ($items[0]->params->get('lsscale') == null)
		{
			$kml[] = '.6';
		}
		else
		{
			$kml[] = $kmlinfo->params->get('lsscale');
		}

		$kml[] = '</scale>';
		$kml[] = '</LabelStyle>';
		$kml[] = '</Style> ';
		$kml[] = '<Style id="text_photo_banner1">';
		$kml[] = '<IconStyle>';
		$kml[] = '<scale>';

		if ($items[0]->params->get('icscale') == null)
		{
			$kml[] = '1.1';
		}
		else
		{
			$kml[] = $kmlinfo->params->get('icscale');
		}

		$kml[] = '</scale>';
		$kml[] = '<Icon>';
		$kml[] = '<href>';

		if ($items[0]->category_params->get('image') === null)
		{
			$kml[] = JUri::base() . 'media/com_churchdirectory/images/kml_icons/iconb.png';
		}
		else
		{
			$kml[] = JUri::base() . $items[0]->category_params->get('image');
		}

		$kml[] = '</href>';
		$kml[] = '</Icon>';
		$kml[] = '<hotSpot x="0.5" y="0.5" xunits="fraction" yunits="fraction"/>';
		$kml[] = '</IconStyle>';
		$kml[] = '<LabelStyle>';
		$kml[] = '<scale>';

		if ($items[0]->params->get('lsscale') == null)
		{
			$kml[] = '.6';
		}
		else
		{
			$kml[] = $kmlinfo->params->get('lsscale');
		}

		$kml[] = '</scale>';
		$kml[] = '</LabelStyle>';
		$kml[] = '</Style> ';
		$teams = $renderHelper->groupit(['items' => $items, 'field' => 'category_title']);
		$new_rows = [];

		foreach ($teams as $c => $catid)
		{
			$new_rows[$c] = $renderHelper->groupit(['items' => $teams[$c], 'field' => 'suburb']);
		}

		$mycounter = '0';

		foreach ($new_rows as $c => $suburb)
		{
			$mycounter++;
			$kml[] = '<Folder id="' . $mycounter . '"> ';
			$kml[] = '<name>';
			$kml[] = $c;
			$kml[] = '</name>';
			$kml[] = '<open>' . $ckml_params->get('mcropen') . '</open>           <!-- boolean -->';

			foreach ($suburb as $s => $rows)
			{
				$mycounter++;
				$kml[] = ' <Folder id="' . $mycounter . '"> ';
				$kml[] = ' <name>' . $s . ' </name> ';
				$kml[] = ' <open>' . $ckml_params->get('msropen') . '</open>           	   <!-- boolean -->';

				foreach ($rows as $row)
				{
					$mycounter++;
					$kml[] = '<Placemark id="placemark' . $mycounter . ' "> ';
					$kml[] = '<name>' . $row->name . '</name>';
					$kml[] = '<visibility>';

					if ($row->params->get('visibility') == null)
					{
						$kml[] = '0';
					}
					else
					{
						$kml[] = $row->params->get('visibility');
					}

					$kml[] = '</visibility><open>';

					if ($row->params->get('open') == null)
					{
						$kml[] = '0';
					}
					else
					{
						$kml[] = $row->params->get('open');
					}

					$kml[] = '</open>';
					$kml[] = '<gx:balloonVisibility>';

					if ($row->params->get('gxballoonvisibility') == null)
					{
						$kml[] = '0';
					}
					else
					{
						$kml[] = $row->params->get('gxballoonvisibility');
					}

					$kml[] = '</gx:balloonVisibility>';
					$kml[] = '<address><![CDATA[';

					if ($row->address != null)
					{
						$kml[] = $row->address . ',<br />';
					}

					$kml[] = $row->suburb . ', ' . $row->state . ' ' . $row->postcode;
					$kml[] = ']]></address> <!-- string -->';
					$kml[] = '<phoneNumber>' . $row->telephone . '</phoneNumber> <!-- string -->';
					$kml[] = '<Snippet maxLines="';

					if ($kmlinfo->params->get('rmaxlines') == null)
					{
						$kml[] = '2';
					}
					else
					{
						$kml[] = $kmlinfo->params->get('rmaxlines');
					}

					$kml[] = '">More coming soon</Snippet>   <!-- string -->';
					$kml[] = '<description>' . '<![CDATA[<div style="padding: 10px;">';

					if (empty($row->image))
					{
						$kml[] = '<img src="' . JUri::base() . 'media/com_churchdirectory/images/photo_not_available.jpg" alt="Photo" width="100" hight="100" /><br />';
					}
					else
					{
						$kml[] = '<img src="' . JUri::base() . $row->image . '" alt="Photo" width="100" hight="100" /><br />';
					}

					if (!empty($row->con_position))
					{
						$kml[] = '<b>Position:</b>' . $renderHelper->getPosition($row->con_position) . '<br />';
					}

					if (!empty($row->spouse))
					{
						$kml[] = 'Spouse: ' . $row->spouse . '<br />';
					}

					if (!empty($row->children))
					{
						$kml[] = 'Children: ' . $row->children . '<br />';
					}

					if (!empty($row->misc))
					{
						$kml[] = $row->misc;
					}

					if (!empty($row->telephone))
					{
						$kml[] = '<br />PH: ' . $row->telephone;
					}

					if (!empty($row->fax))
					{
						$kml[] = '<br />Fax: ' . $row->fax;
					}

					if (!empty($row->mobile))
					{
						$kml[] = '<br />Cell: ' . $row->mobile;
					}

					if (!empty($row->email_to))
					{
						$kml[] = '<br />Email: <a href="mailto:' . $row->email_to . '">' . $row->email_to . '</a>';
					}

					$kml[] = '</div>]]></description>';
					$kml[] = '<Point>';
					$kml[] = '<coordinates>' . $row->lng . ',' . $row->lat . ',0</coordinates>';
					$kml[] = '</Point>';
					$kml[] = '</Placemark>';
				} /* End the state folder */
				$kml[] = '</Folder>';
			} /* End the country folder */
			$kml[] = '</Folder>';
		}

		header('Content-type: application/vnd.google-earth.kml+xml');

		if ($report)
		{
			$filename = $report;
		}
		else
		{
			$filename = $kmlinfo->alias;
		}

		header('Content-disposition: attachment; filename="' . $filename . '.kml"');

		// End KML file
		$kml[]     = '</Document>';
		$kml[]     = '</kml>';
		$kmlOutput = join("\n", $kml);
		echo $kmlOutput;

		// Hold
		return true;
	}
}
