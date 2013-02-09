<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Class for Html Icons
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.1
 */
class JHtmlIcon
{

	/**
	 * Icon for email
	 *
	 * @param   object     $member   Member info
	 * @param   JRegistry  $params   HTML Params
	 * @param   array      $attribs  Member attribs
	 *
	 * @return string
	 */
	public static function email($member, $params, $attribs = array())
	{
		require_once JPATH_SITE . '/components/com_mailto/helpers/mailto.php';
		$uri  = JURI::getInstance();
		$base = $uri->toString(array('scheme', 'host', 'port'));
		$link = $base . JRoute::_(ContentHelperRoute::getArticleRoute($member->slug, $member->catid), false);
		$url  = 'index.php?option=com_mailto&tmpl=component&link=' . MailToHelper::addLink($link);

		$status = 'width=400,height=350,menubar=yes,resizable=yes';

		if ($params->get('show_icons'))
		{
			$text = JHtml::_('image', 'system/emailButton.png', JText::_('JGLOBAL_EMAIL'), null, true);
		}
		else
		{
			$text = '&#160;' . JText::_('JGLOBAL_EMAIL');
		}

		$attribs['title']   = JText::_('JGLOBAL_EMAIL');
		$attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";

		$output = JHtml::_('link', JRoute::_($url), $text, $attribs);

		return $output;
	}

	/**
	 * Print Popup
	 *
	 * @param   object     $member   Member info
	 * @param   JRegistry  $params   HTML Params
	 * @param   array      $attribs  Member attribs
	 *
	 * @return string
	 */
	public static function print_popup($member, $params, $attribs = array())
	{
		$url = ChurchDirectoryHelperRoute::getMemberRoute($member->slug, $member->catid);
		$url .= '&tmpl=component&print=1&layout=default';

		$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

		// Checks template image directory for image, if non found default are loaded
		if ($params->get('show_icons'))
		{
			$text = JHtml::_('image', 'system/printButton.png', JText::_('JGLOBAL_PRINT'), null, true);
		}
		else
		{
			$text = JText::_('JGLOBAL_ICON_SEP') . '&#160;' . JText::_('JGLOBAL_PRINT') . '&#160;' . JText::_('JGLOBAL_ICON_SEP');
		}

		$attribs['title']   = JText::_('JGLOBAL_PRINT');
		$attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";
		$attribs['rel']     = 'nofollow';

		return JHtml::_('link', JRoute::_($url), $text, $attribs);
	}

	/**
	 * Print screen icon
	 *
	 * @param   object     $member   Member info
	 * @param   JRegistry  $params   HTML Params
	 * @param   array      $attribs  Member attribs
	 *
	 * @return string
	 */
	public static function print_screen($member, $params, $attribs = array())
	{
		// Checks template image directory for image, if non found default are loaded
		if ($params->get('show_icons'))
		{
			$text = JHtml::_('image', 'system/printButton.png', JText::_('JGLOBAL_PRINT'), null, true);
		}
		else
		{
			$text = JText::_('JGLOBAL_ICON_SEP') . '&#160;' . JText::_('JGLOBAL_PRINT') . '&#160;' . JText::_('JGLOBAL_ICON_SEP');
		}

		return '<a href="#" onclick="window.print();return false;">' . $text . '</a>';
	}

}
