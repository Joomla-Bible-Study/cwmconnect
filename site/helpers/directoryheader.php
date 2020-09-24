<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.christianwebministries.org
 * */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
/**
 * Directory Header Helper
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.1
 */
class DirectoryHeaderHelper
{
	/**
	 * @var array  Headers
	 * @since    1.5
	 */
	public $header = [];

	/**
	 * @var string  Footers
	 * @since    1.5
	 */
	public $footer = [];

	/**
	 * set Header or Footer html
	 *
	 * @param   Registry  $params  HTML Params
	 *
	 * @return string
	 *
	 * @since    1.5
	 */
	public function setPages($params)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('a.*');
		$query->from('#__churchdirectory_dirheader AS a');
		$query->where('published = 1');
		$query->order('a.ordering ASC');
		$db->setQuery($query);

		$result = $db->loadObjectList();
		$h = 0;

		foreach ($result as $b)
		{
			$header = new stdClass;
			$header->html = '<div class="headerpage">';
			$header->html .= '<h2>' . $b->name . '</h2>';
			$header->html .= $b->description;
			$header->html .= '</div>';
			$header->name = $b->name;
			$h++;

			if ($b->section == '1')
			{
				$this->footer[$b->id] = $header;
			}
			else
			{
				$this->header[$b->id] = $header;
			}

			$header = null;
		}

		return null;
	}
}
