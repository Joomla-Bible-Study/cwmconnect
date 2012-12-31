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
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');

class QContactsViewQContacts extends JViewLegacy {
    function display($tpl = null) {
	
		global $mainframe, $option;
		
		$filter_order = $mainframe->getUserStateFromRequest($option.'filter_order', 'filter_order', 'cd.ordering', 'cmd');
		$filter_order_Dir = $mainframe->getUserStateFromRequest($option.'filter_order_Dir',	'filter_order_Dir', '', 'word');
		$filter_state = $mainframe->getUserStateFromRequest($option.'filter_state', 'filter_state', '', 'word');
		$filter_catid = $mainframe->getUserStateFromRequest($option.'filter_catid', 'filter_catid', 0, 'int');
		$search = $mainframe->getUserStateFromRequest($option.'search', 'search', '', 'string');
		$search = JString::strtolower($search);
	
		$pagination =& $this->get('Pagination');
              $items =& $this->get('Data');
		
		$javascript = 'onchange="document.adminForm.submit();"';
		$lists['catid'] = JHTML::_('list.category',  'filter_catid', 'com_qcontacts_details', intval($filter_catid), $javascript);

		$lists['state'] = JHTML::_('grid.state',  $filter_state);

		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order'] = $filter_order;

		$lists['search'] = $search;
	
              $this->assignRef('items', $items);
		$this->assignRef('lists', $lists);
		$this->assignRef('pagination', $pagination);
		
        parent::display($tpl);
    }
}
?>