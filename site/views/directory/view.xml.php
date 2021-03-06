<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.mail.helper');

/**
 * XML Contact View class for the ChurchDirectory component
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
	public function display($tpl = null)
	{
		$app  = JFactory::getApplication();

		// Get some data from the models
		$state      = $this->get('State');
		$params     = $state->params;
		$items      = $this->get('Items');
		$category   = $this->get('Category');
		$children   = $this->get('Children');
		$parent     = $this->get('Parent');
		$pagination = $this->get('Pagination');
		$doc        = JFactory::getDocument();
		$doc->setMetaData('Content-Type', 'application/xml', true);

		// $doc->setMetaData('Content-Type', 'text/html', true);

		// Check whether category access level allows access.
		$user   = JFactory::getUser();
		$groups = $user->getAuthorisedViewLevels();

		if ($items == false)
		{
			$app->enqueueMessage(JText::_('COM_CHURCHDIRECTOY_ERROR_DIRECTORY_NOT_FOUND'), 'error');

			return false;
		}

		// Prepare the data.
		// Compute the contact slug.
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item       = & $items[$i];
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

			$item->event = new stdClass;
			$temp        = new JRegistry;
			$temp->loadString($item->params);
			$item->params = clone $params;
			$item->params->merge($temp);

			if ($item->params->get('dr_show_email', 0) == 1)
			{
				$item->email_to = trim($item->email_to);

				if (!empty($item->email_to) && JMailHelper::isEmailAddress($item->email_to))
				{
					$item->email_to = JHtml::_('email.cloak', $item->email_to);
				}
				else
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
		$this->maxLevel   = & $maxLevel;
		$this->state      = & $state;
		$this->items      = & $items;
		$this->category   = & $category;
		$this->children   = & $children;
		$this->params     = & $params;
		$this->parent     = & $parent;
		$this->pagination = & $pagination;

		// Creates an array of strings to hold the lines of the KML file.
		$kml   = ['<?xml version="1.0" encoding="UTF-8"?>'];
		$kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2">';
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
			$kml[] = JURI::base() . 'media/com_churchdirectory/images/kml_icons/iconb.png';
		}
		else
		{
			$kml[] = JURI::base() . DS . $items[0]->category_params->get('image');
		}

		$kml[] = '</href>';
		$kml[] = '</Icon>';
		$kml[] = '</IconStyle>';
		$kml[] = '<LabelStyle>';
		$kml[] = '<color>';
		$kml[] = $items[0]->kml_params->get('lscolor');
		$kml[] = '</color>';
		$kml[] = '<colorMode>';
		$kml[] = $items[0]->kml_params->get('lscolormode');
		$kml[] = '</colorMode>';
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
		$teams = $this->groupit(['items' => $items, 'field' => 'category_title']);

		foreach ($teams as $c => $catid)
		{
			$newrows[$c] = $this->groupit(['items' => $teams[$c], 'field' => 'suburb']);
			$ckml_params = $catid[0]->kml_params;
		}

		$mycounter = '0';

		foreach ($newrows as $c => $suburb)
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
						$kml[] = $row->address . '<br />';
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

					$kml[] = '"><![CDATA[' . $row->id . ' <br />Team ' . $row->catid . ']]></Snippet>   <!-- string -->';
					$kml[] = '<description>' . '<![CDATA[<div>';

					if (empty($row->image))
					{
						$kml[] = '<img src="' . JURI::base() . 'media/com_churchdirectory/images/photo_not_available.jpg" alt="Photo" width="100" hight="100" /><br />';
					}
					else
					{
						$kml[] = '<img src="' . JURI::base() . '/' . $row->image . '" alt="Photo" width="100" hight="100" /><br />';
					}

					if (!empty($row->id))
					{
						$kml[] = '<b>Position: ' . $row->id . '</b><br />';
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
					$kml[] = '<styleUrl>#text_photo_banner</styleUrl>';
					$kml[] = '<Point>';
					$kml[] = '<coordinates>' . $row->lng . ',' . $row->lat . '</coordinates>';
					$kml[] = '</Point>';
					$kml[] = '</Placemark>';
				}

				// End the state folder
				$kml[] = '</Folder>';
			}

			// End the country folder
			$kml[] = '</Folder>';
		}
		// End XML file
		$kml[]     = '</Document>';
		$kml[]     = '</kml>';
		$kmlOutput = join("\n", $kml);
		echo $kmlOutput;

		return true;
	}

	/**
	 * For passing records out to put then in order and not repeat the records.
	 *
	 * @param   array  $args  ?
	 *
	 * @return array
	 *
	 * @since       1.7.2
	 */
	protected function groupit($args)
	{
		$items = null;
		$field = null;
		extract($args);
		$result = [];

		foreach ($items as $item)
		{
			if (!empty($item->$field))
			{
				$key = $item->$field;
			}
			else
			{
				$key = 'nomatch';
			}

			if (array_key_exists($key, $result))
			{
				$result[$key][] = $item;
			}
			else
			{
				$result[$key]   = [];
				$result[$key][] = $item;
			}
		}

		return $result;
	}
}
