<?php

/**
 * Family Member Helper
 *
 * @package ChurchDirectory.Site
 * @Copyright (C) 2007 - 2011 Joomla Bible Study Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link    http://www.JoomlaBibleStudy.org
 * */
//No Direct Access
defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/helpers/positions.php';

/**
 * Get Family Mebers Buld
 *
 * @param array $params
 * @param int   $id
 * @param int   $famid
 *
 * @return string
 */
function getFamilyMembersPage($params, $id, $famid)
{

	$teacher = "\n" . '<div id="landing_table" width="100%">';
	$db = JFactory::getDBO();
	$query = $db->getQuery(true);

	$query->select('members.*');
	$query->from('#__churchdirectory_details AS members');
	$query->where('members.funitid = ' . (int)$famid);
	$query->order('members.name DESC');

	$db->setQuery($query->__toString());
	if ($params->get('dr_show_debug')):
		var_dump($id);
		var_dump($famid);
	endif;
	$tresult = $db->loadObjectList();
	$t = 0;
	$i = 0;
	foreach ($tresult as $b) {
		$attribs = json_decode($b->attribs);
		$b->slug = $b->alias ? ($b->id . ':' . $b->alias) : $b->id;
		$teacher .= '<div class="directory-familymembers-list">';
		$teacher .= '<div class="directory-name"><a href="' . JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($b->slug, $b->catid)) . '">';
		$teacher .= $b->name;
		$teacher .= '</a></div>';
		$teacher .= '<div class="directory-subtitle">';
		switch ($attribs->familypostion) {
			case -1:
				$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_SINGLE') . '</span>';
				break;
			case 0:
				$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_HEAD_OF_HOUSEHOLD') . '</span>';
				break;
			case 1:
				$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_SPOUSE') . '</span>';
				break;
			case 2:
				$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_CHILED') . '</span>';
				break;
		}
		$teacher .= '</div>';
		$teacher .= '<div class="clearfix"></div><div class="directory-submemberinfo">';
		if (!empty($b->con_position) && $params->get('dr_show_position')) :
			$teacher .= '<div class="clearfix"></div>';
			$teacher .= '<div id="position-header"><span id="contact-position">';
			$teacher .= '<b>Position: </b>';
			$teacher .= '</span>';
			$teacher .= '</div>';
			$teacher .= '<div id="position-name">';
			$teacher .= '<span id="contact-position">';
			$positions = getPosition($b->con_position);
			$teacher .= $positions;
			$teacher .= '<br />';
			$teacher .= '</span>';
			$teacher .= '</div>';
			$teacher .= '<br /><div class="clearfix"></div>';
		endif;
		if ($b->telephone && $params->get('dr_show_telephone')) {
			$teacher .= '<div class="directory-telephone"><span class="title">' . JText::_('COM_CHURCHDIRECTORY_HOME') . ':</span> ' . $b->telephone . '</div>';
		}
		if ($b->mobile && $params->get('dr_show_mobile')) {
			$teacher .= '<div class="directory-mobile"><span class="title">' . JText::_('COM_CHURCHDIRECTORY_MOBILE') . ':</span> ' . $b->mobile . '</div>';
		}
		$teacher .= '</div>';
		$i++;
		$t++;
		$teacher .= '</div><div class="clearfix"></div>';

		$teacher .= '<hr />';
	}
	$teacher .= '</div>';

	if ($b->children && $params->get('dr_show_children')) {
		$teacher .= '<div class="directory-children"><br /><span class="title">' . JText::_('COM_CHURCHDIRECTORY_CHILDREN') . ':</span> ' . $b->children . '</div>';
	}

	return $teacher;
}