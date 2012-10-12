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

class QContactsViewCategory extends JView {
	function display($tpl = null)
	{
		global $mainframe;

		$user =& JFactory::getUser();
		$uri =& JFactory::getURI();
		$model =& $this->getModel();
		$document =& JFactory::getDocument();

		$pparams =& $mainframe->getParams('com_qcontacts');

		$categoryId = JRequest::getVar('catid', 0, '', 'int');
		
		$limit = $mainframe->getUserStateFromRequest('com_qcontacts.limit'.$categoryId, 'limit',$pparams->get('contacts_per_page', $mainframe->getCfg('list_limit')), '', 'int');
		$limitstart = JRequest::getVar('limitstart', 0, '', 'int');
		$limitstart = ($limit !=0 ? (floor($limitstart / $limit) * $limit): 0);
		$filter_order =$mainframe->getUserStateFromRequest('com_qcontacts.filter_order'.$categoryId, 'filter_order', 'cd.'.$pparams->get('default_ordering','ordering'), '', 'cmd');
		$filter_order_Dir =$mainframe->getUserStateFromRequest('com_qcontacts.filter_order_Dir'.$categoryId, 'filter_order_Dir', 'ASC', '', 'word');
		
		$options['aid'] = $user->get('aid', 0);
		$options['category_id']	= $categoryId;
		$options['limit'] = $limit;
		$options['limitstart'] = $limitstart;
		$options['order by'] = "$filter_order $filter_order_Dir, cd.ordering";

		if($categoryId) {
			$category = $model->getCategory($options);
			if (!is_object( $category )) {
				JError::raiseError( 404, 'Category not found' );
				return;
			}
		}
		$contacts = $model->getContacts($options);
		
		$total = $model->getContactCount($options);

		if($pparams->get('show_feed_link', 1) == 1) {
			$link	= '&format=feed&limitstart=';
			$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
			$document->addHeadLink(JRoute::_($link.'&type=rss'), 'alternate', 'rel', $attribs);
			$attribs = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
			$document->addHeadLink(JRoute::_($link.'&type=atom'), 'alternate', 'rel', $attribs);
		}

		if($pparams->get('show_col_email', 0) == 1) {
		    jimport('joomla.mail.helper');
		}
		$columns = array();
		
		if($pparams->get('show_col_name',1)) {
			$c = new stdClass;
			$c->field = 'name';
			$c->label ='Name';
			$c->sortable = $pparams->get('sort_col_name',1);
			$c->width= $pparams->get('width_col_name');;
			$columns[]=array('order'=>$pparams->get('ord_col_name',0), 'column'=>$c);
		}
		if($pparams->get('show_col_position')) {
			$c = new stdClass;
			$c->field = 'con_position';
			$c->label = 'Position';
			$c->sortable = $pparams->get('sort_col_position',1);
			$c->width = $pparams->get('width_col_position');
			$columns[]=array('order'=>$pparams->get('ord_col_position',1), 'column'=>$c);
		}
		if($pparams->get('show_col_email')) {
			$c = new stdClass;
			$c->field = 'email_to';
			$c->label = 'Email';
			$c->sortable = $pparams->get('sort_col_email',0);
			$c->width = $pparams->get('width_col_email');
			$columns[]=array('order'=>$pparams->get('ord_col_email',2), 'column'=>$c);
		}
		if($pparams->get('show_col_telephone')) {
			$c = new stdClass;
			$c->field = 'telephone';
			$c->label = 'Phone';
			$c->sortable = $pparams->get('sort_col_telephone',0);
			$c->width = $pparams->get('width_col_telephone');
			$columns[]=array('order'=>$pparams->get('ord_col_telephone',3), 'column'=>$c);
		}
		if($pparams->get('show_col_mobile')) {
			$c = new stdClass;
			$c->field = 'mobile';
			$c->label = 'Mobile';
			$c->sortable = $pparams->get('sort_col_mobile',0);
			$c->width = $pparams->get('width_col_mobile');
			$columns[]=array('order'=>$pparams->get('ord_col_mobile',4), 'column'=>$c);
		}
		if($pparams->get('show_col_fax')) {
			$c = new stdClass;
			$c->field = 'fax';
			$c->label = 'Fax';
			$c->sortable = $pparams->get('sort_col_fax',0);
			$c->width = $pparams->get('width_col_fax');
			$columns[]=array('order'=>$pparams->get('ord_col_fax',5), 'column'=>$c);
		}
		if($pparams->get('show_col_street')) {
			$c = new stdClass;
			$c->field = 'address';
			$c->label = 'Address';
			$c->sortable = $pparams->get('sort_col_street',0);
			$c->width = $pparams->get('width_col_street');
			$columns[]=array('order'=>$pparams->get('ord_col_street',6), 'column'=>$c);
		}
		if($pparams->get('show_col_suburb')) {
			$c = new stdClass;
			$c->field = 'suburb';
			$c->label = 'City';
			$c->sortable = $pparams->get('sort_col_suburb',0);
			$c->width = $pparams->get('width_col_suburb');
			$columns[]=array('order'=>$pparams->get('ord_col_suburb',7), 'column'=>$c);
		}
		if($pparams->get('show_col_state')) {
			$c = new stdClass;
			$c->field = 'state';
			$c->label = 'State';
			$c->sortable = $pparams->get('sort_col_state',0);
			$c->width = $pparams->get('width_col_state');
			$columns[]=array('order'=>$pparams->get('ord_col_state',8), 'column'=>$c);
		}
		if($pparams->get('show_col_postcode')) {
			$c = new stdClass;
			$c->field = 'postcode';
			$c->label = 'Zip Code';
			$c->sortable = $pparams->get('sort_col_postcode',0);
			$c->width = $pparams->get('width_col_postcode');
			$columns[]=array('order'=>$pparams->get('ord_col_postcode',9), 'column'=>$c);
		}
		if($pparams->get('show_col_country')) {
			$c = new stdClass;
			$c->field = 'country';
			$c->label = 'Country';
			$c->sortable = $pparams->get('sort_col_country',0);
			$c->width = $pparams->get('width_col_country');
			$columns[]=array('order'=>$pparams->get('ord_col_country',10), 'column'=>$c);
		}
		if($pparams->get('show_col_category')) {
			$c = new stdClass;
			$c->field = 'category_name';
			$c->label = 'Category';
			$c->sortable = $pparams->get('sort_col_category',0);
			$c->width = $pparams->get('width_col_category');
			$columns[]=array('order'=>$pparams->get('ord_col_category',11), 'column'=>$c);
		}
		if($pparams->get('show_col_skype')) {
			$c = new stdClass;
			$c->field = 'skype';
			$c->label = 'Skype';
			$c->sortable = $pparams->get('sort_col_skype',0);
			$c->width = $pparams->get('width_col_skype');
			$columns[]=array('order'=>$pparams->get('ord_col_skype',12), 'column'=>$c);
		}
		if($pparams->get('show_col_yahoo')) {
			$c = new stdClass;
			$c->field = 'yahoo_msg';
			$c->label = 'Yahoo Msg';
			$c->sortable = $pparams->get('sort_col_yahoo',0);
			$c->width = $pparams->get('width_col_yahoo');
			$columns[]=array('order'=>$pparams->get('ord_col_yahoo',13), 'column'=>$c);
		}
		if($pparams->get('show_col_webpage')) {
			$c = new stdClass;
			$c->field = 'webpage';
			$c->label = 'Website';
			$c->sortable = $pparams->get('sort_col_webpage',0);
			$c->width = $pparams->get('width_col_webpage');
			$columns[]=array('order'=>$pparams->get('ord_col_webpage',14), 'column'=>$c);
		}
		if(count($columns)) {
			foreach ($columns as $k => $v) {
	    		$order[$k] = $v['order'];
			}
			array_multisort($order, SORT_ASC, $columns);
		}
		$kk = 0;
		for($i = 0; $i < count($contacts); $i++)	{
			$contact =& $contacts[$i];

			$cparams =  new JParameter($contact->params);
			
			$params = $cparams->toArray();
			foreach($params as $k=>$v) {
				if($cparams->get($k) == '') {
					$cparams->set($k, $pparams->get($k, ''));
				}
			}
			$contact->params = $cparams;
			
			$contact->link = JRoute::_('index.php?option=com_qcontacts&view=contact&id='.$contact->slug.'&catid='.$contact->catslug);
			
			if($pparams->get('show_col_email', 0) == 1 && $cparams->get('show_email', 0) == 1) {
			    $contact->email_to = trim($contact->email_to);
				if(!empty($contact->email_to) && JMailHelper::isEmailAddress($contact->email_to)) {
				    $contact->email_to = JHTML::_('email.cloak', $contact->email_to);
				} else {
				    $contact->email_to = '';
				}
			}
			if(!$cparams->get('show_email', 0)){
				$contact->email_to = '';
			}
			if(!$cparams->get('show_position',1)) {
				$contact->con_position = '';
			}
			if(!$cparams->get('show_telephone',1)) {
				$contact->telephone = '';
			}
			if(!$cparams->get('show_mobile',1)) {
				$contact->mobile = '';
			}
			if(!$cparams->get('show_fax',1)) {
				$contact->fax = '';
			}
			if(!$cparams->get('show_street_address',1)) {
				$contact->address = '';
			}
			if(!$cparams->get('show_suburb',1)) {
				$contact->suburb = '';
			}
			if(!$cparams->get('show_state',1)) {
				$contact->state = '';
			}
			if(!$cparams->get('show_postcode',1)) {
				$contact->postcode = '';
			}
			if(!$cparams->get('show_country',1)) {
				$contact->country = '';
			}
			if($cparams->get('show_skype',0)) {
				if($cparams->get('show_skype',0)==2) {
					$contact->skype = '<a href="skype:'.$contact->skype .'?call">'. $contact->skype .'</a>';
				}
			} else {
				$contact->skype = '';
			}
			if($cparams->get('show_yahoo',0)) {
				if($cparams->get('show_yahoo',0)==2) {
					$contact->yahoo_msg = '<a href="http://messenger.yahoo.com/edit/send/?.target=' . $contact->yahoo_msg .'">'. $contact->yahoo_msg . '</a>';
				}
			} else {
				$contact->yahoo_msg = '';
			}
			if(!$cparams->get('show_webpage',0)) {
				$contact->webpage = '';
			} else {
				$contact->webpage = '<a href="'. $contact->webpage .'" target="_blank">'. $contact->webpage .'</a>';			
			}
			$contact->odd = $kk;
			$contact->count = $i;
			$kk = 1 - $kk;
		}
		
		if(!isset($category)){
			$category = new stdClass;
			$category->title = 'Contacts';
			$category->image = '';
			$category->description = '';
			$category->id = 0;
		}
		
		$menus = &JSite::getMenu();
		$menu = $menus->getActive();
		$pathway =& $mainframe->getPathway();
		
		if (is_object( $menu )) {
			$menu_params = new JParameter( $menu->params );
			if (!$menu_params->get( 'page_title')) {
				$pparams->set('page_title',	$category->title);
			}
		} else {
			$pparams->set('page_title',	$category->title);
		}
		$document->setTitle( $pparams->get( 'page_title' ) );
		
		if(is_object($menu) && $menu->query['view'] != 'category') {
			$pathway->addItem($category->title, '');
		}
		
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;
		$selected = '';

		jimport('joomla.html.pagination');
		$pagination = new JPagination($total, $limitstart, $limit);

		$this->assignRef('items', $contacts);
		$this->assignRef('columns', $columns);
		$this->assignRef('lists', $lists);
		$this->assignRef('pagination', $pagination);
		$this->assignRef('category', $category);
		$this->assignRef('params', $pparams);

		$this->assign('action', JFilterOutput::ampReplace($uri->toString()));
		
		parent::display($tpl);
	}

	function getItems()
	{

	}
}