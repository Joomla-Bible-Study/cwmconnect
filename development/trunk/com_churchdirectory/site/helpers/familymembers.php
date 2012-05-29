<?php

/**
 * @version $Id: teacher.php 1 $
 * @package BibleStudy
 * @Copyright (C) 2007 - 2011 Joomla Bible Study Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.JoomlaBibleStudy.org
 * */
//No Direct Access
defined('_JEXEC') or die;

function getFamilyMembersPage($params, $id, $famid) {

    $teacher = null;
    $teacher = "\n" . '<div id="landing_table" width="100%">';
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);

    $query->select('members.*');
    $query->from('#__churchdirectory_details AS members');
    $query->where('members.funitid = ' . (int) $id);
    $query->order('members.name DESC');

    $db->setQuery($query->__toString());

    $tresult = $db->loadObjectList();
    $t = 0;
    $i = 0;
    foreach ($tresult as $b) {
        $attribs = json_decode($b->attribs);
        //var_dump($params->get('show_telephone'));
        //var_dump($attribs);
        $teacher .= '<div class="directory-familymembers-list">';
        $teacher .= '<div class="directory-name"><a href="' . JRoute::_('index.php?option=com_churchdirectory&view=churchdirectory&id=' . $b->id) . '">';
        $teacher .= $b->name;
        $teacher .='</a></div>';
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
        $teacher .='<div class="clearfix"></div><div class="directory-submemberinfo">';
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