<?php
/**
 * @version		$Id: view.pdf.php 14401 2010-01-26 14:10:00Z louis $
 * @package		Joomla
 * @subpackage	qcontacts
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

/**
 * HTML Article View class for the Content component
 *
 * @package		Joomla
 * @subpackage	qcontacts
 * @since 1.5
 */
class QContactsViewQContacts extends JView
{
	function display($tpl = null) {
	
		global $mainframe;
		
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

		
		echo $this->get('Data');
	}
}
?>