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
defined('_JEXEC') or die('Restricted access');
JToolBarHelper::title(JText::_('QContacts') .': <small><small>[ '. JText::_('Info')  .' ]</small></small>', 'generic.png');
JSubMenuHelper::addEntry(JText::_('Contacts'), 'index.php?option=com_qcontacts');
JSubMenuHelper::addEntry(JText::_('Categories'), 'index.php?option=com_categories&section=com_qcontacts_details');
JSubMenuHelper::addEntry(JText::_('Tools'), 'index.php?option=com_qcontacts&controller=tools');
JSubMenuHelper::addEntry(JText::_('Info'), 'index.php?option=com_qcontacts&view=info',true);
?>
<p>A contact manager component. Displays a list of contacts and contact details pages with various informations and a mail-to form.</p>
<h2>Online Documentation</h2>
<p><a href="http://www.latenight-coding.com/joomla-addons/qcontacts/documentation.html">English</a><br />
<a href="http://www.latenight-coding.com/it/joomla-addons/qcontacts/documentazione.html">Italiano</a></p>
<h2>Support QContacts</h2>
<p>If you like this component please rate it at the Joomla Extensions Directory. <a href="http://extensions.joomla.org/component/option,com_mtree/task,viewlink/link_id,4811/Itemid,35/">Rate here</a></p>
<h2>Need help?</h2>
<p>Feel free to ask a question in our <a href="http://forum.latenight-coding.com/">support forum</a>.</p>
<h2>Copyright</h2>
<p>QContacts is free software released under the <a href="http://www.gnu.org/copyleft/gpl.html">GNU/GPL License v3</a>. Copyright 2008 Massimo Giagnoni</p>
<p>Some parts of QContacts are derived from Joomla! contact component (com_contact). Copyright 2005-2008 Open Source Matters</p>