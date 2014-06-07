<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.JoomlaBibleStudy.org
 * */

defined('_JEXEC') or die;

/**
 * Directory Header Helper
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.1
 */
class DirectoryHeaderHelper
{
	/**
	 * @var string  Headers
	 */
	public $header = null;

	/**
	 * @var string  Footers
	 */
	public $footer = null;

	/**
	 * set Header or Footer html
	 *
	 * @param   JRegistry  $params  HTML Params
	 *
	 * @return string
	 */
	public function setPages($params)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('a.*');
		$query->from('#__churchdirectory_dirheader AS a');
		$query->order('a.ordering ASC');
		$db->setQuery($query);

		$result = $db->loadObjectList();
		$h      = 0;
		$header = null;

		foreach ($result as $b)
		{
			$header .= '<div class="headerpage">';

			if ($params->get('dr_show_debug'))
			{
				$header .= '<p>ID: ' . $b->id . '<br />';
				$header .= 'Count: ' . $h . '</p>';
			}
			$header .= '<h2>' . $b->name . '</h2>';
			$header .= $b->description;
			$header .= '</div>';
			$header .= '<div style="page-break-after:always"></div>';
			$h++;

			if ($b->section == '1')
			{
				$this->footer = $this->footer . $header;
			}
			else
			{
				$this->header = $this->header . $header;
			}

			$header = null;
		}
	}

}
