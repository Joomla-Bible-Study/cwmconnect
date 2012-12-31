<?php
/**
 * QContacts Contact manager component for Joomla! 1.5
 *
 * @version 1.0.6
 * @package qcontacts
 * @author Massimo Giagnoni
 * @copyright Copyright (C) 2008 Massimo Giagnoni. All rights reserved.
 * @copyright Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of QContacts.
QContacts is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
defined( '_JEXEC' ) or die( 'Restricted access' );

function com_install() {
	$db =& JFactory::getDBO();
	$sql = "SELECT id FROM #__components " .
	"WHERE `option` = 'com_qcontacts' AND parent=0";
	$db->setQuery($sql);
	$r = $db->loadObject();
	if(is_object($r)) {
		$sql = "UPDATE #__menu SET componentid=" . $r->id .
		" WHERE link LIKE '%option=com_qcontacts%' AND type='component'";
		$db->setQuery($sql);
		$db->query();
	}
}
?>
<h1>QContacts Installed</h1>
Online documentation at<br />
<a href="http://www.latenight-coding.com/joomla-addons/qcontacts/documentation.html" target="_blank">www.latenight-coding.com/joomla-addons/qcontacts/documentation.html</a> (English)
<br /><a href="http://www.latenight-coding.com/it/joomla-addons/qcontacts/documentazione.html" target="_blank">www.latenight-coding.com/it/joomla-addons/qcontacts/documentazione.html</a> (Italian)
<p>QContacts is an open source component derived from standard Joomla! contact manager (com_contact)</p>
<p>GNU/GPL v3 License</p>