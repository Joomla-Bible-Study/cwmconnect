<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2005 - 2016 Joomla Bible Study, All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * HTML Contact View class for the Contact component
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewDirectory extends JViewLegacy
{
	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $state;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $items;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $params;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $kml_params;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $category_params;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $category;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $children;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $pagination;

	protected $maxLevel;

	protected $parent;

	/**
	 * Display function
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since       1.7.2
	 */
	public function display ($tpl = null)
	{
		$app = JFactory::getApplication();

		// Get some data from the models
		$state      = $this->get('State');
		/** @var Registry $params */
		$params     = $state->params;
		$items      = $this->get('Items');
		$category   = $this->get('Category');
		$children   = $this->get('Children');
		$parent     = $this->get('Parent');
		$pagination = $this->get('Pagination');

		// Check whether category access level allows access.
		$user   = JFactory::getUser();
		$groups = $user->getAuthorisedViewLevels();

		if (!in_array($category->access, $groups))
		{
			echo JText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		if ($items == false || empty($items))
		{
			echo JText::_('COM_CHURCHDIRECTOY_ERROR_DIRECTORY_NOT_FOUND');

			return false;
		}

		// Prepare the data.
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = &$items[$i];

			// Compute the contact slug.
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

			$item->event = new stdClass;
			$temp        = new Registry;
			$temp->loadString($item->params);
			$item->params = clone $params;
			$item->params->merge($temp);

			if ($item->params->get('dr_show_email', 0) == 1)
			{
				$item->email_to = trim($item->email_to);

				if (empty($item->email_to) && !JMailHelper::isEmailAddress($item->email_to))
				{
					$item->email_to = null;
				}
			}
		}

		// Setup the category parameters.
		$cparams          = $category->getParams();
		$category->params = clone $params;
		$category->params->merge($cparams);
		$children = [$category->id => $children];

		$maxLevel         = $params->get('maxLevel', -1);
		$this->maxLevel   = &$maxLevel;
		$this->state      = &$state;
		$this->items      = &$items;
		$this->category   = &$category;
		$this->children   = &$children;
		$this->params     = &$params;
		$this->parent     = &$parent;
		$this->pagination = &$pagination;

		// Creates an array of strings to hold the lines of the KML file.
		$kml   = ['<?xml version="1.0" encoding="UTF-8"?>'];
		$kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2"'
				. ' xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">';
		$kml[] = '<Document>';
		$kml[] = '<name>' . $items[0]->kml_name . '</name>';
		$kml[] = '<open>' . $items[0]->kml_params->get('open') . '</open>';
		$kml[] = '<LookAt>
    		 <longitude>' . $items[0]->kml_lng . '</longitude>
    		 <latitude>' . $items[0]->kml_lat . '</latitude>
		 <altitude>' . $items[0]->kml_params->get('altitude') . '</altitude>
		 <range>' . $items[0]->kml_params->get('range') . '</range>
		 <tilt>' . $items[0]->kml_params->get('tilt') . '</tilt>
		 <heading>' . $items[0]->kml_params->get('heading') . '</heading>
		 <gx:altitudeMode>' . $items[0]->kml_params->get('gxaltitudeMode') . '</gx:altitudeMode>
  	     </LookAt>    <!-- Camera or LookAt -->';
		$kml[] = $items[0]->kml_style;

		$kml[] = '<StyleMap id="text_photo_banner0">
		<Pair>
			<key>normal</key>
			<styleUrl>#text_photo_banner</styleUrl>
		</Pair>
		<Pair>
			<key>highlight</key>
			<styleUrl>#text_photo_banner1</styleUrl>
		</Pair>
	</StyleMap>';
		$kml[] = '<Style id="text_photo_banner">';
		$kml[] = '<IconStyle>';
		$kml[] = '<scale>';

		if ($items[0]->params->get('icscale') == null)
		{
			$kml[] = '1.1';
		}
		else
		{
			$kml[] = $items[0]->kml_params->get('icscale');
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
			$kml[] = $items[0]->kml_params->get('lsscale');
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
			$kml[] = $items[0]->kml_params->get('icscale');
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
			$kml[] = $items[0]->kml_params->get('lsscale');
		}

		$kml[] = '</scale>';
		$kml[] = '</LabelStyle>';
		$kml[] = '</Style> ';
		$teams = RenderHelper::groupit(['items' => $items, 'field' => 'category_title']);
		$new_rows = [];
		$ckml_params = new Registry;

		foreach ($teams as $c => $catid)
		{
			$new_rows[$c] = RenderHelper::groupit(['items' => $teams[$c], 'field' => 'suburb']);
			$ckml_params->merge($catid[0]->kml_params);
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

					if ($row->kml_params->get('rmaxlines') == null)
					{
						$kml[] = '2';
					}
					else
					{
						$kml[] = $row->kml_params->get('rmaxlines');
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

					if (!empty($row->id))
					{
						$kml[] = '<b>Position:</b> Fixing sitll need to implement<br />';
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

					$kml[] = '</div>]]>' . '</description>';
					$kml[] = '<styleUrl>#text_photo_banner0</styleUrl>';
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
		header('Content-disposition: attachment; filename="' . $items[0]->kml_alias . '.kml"');

		// End KML file
		$kml[]     = '</Document>';
		$kml[]     = '</kml>';
		$kmlOutput = join("\n", $kml);
		echo $kmlOutput;

		return true;
	}
}
