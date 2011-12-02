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

class QContactsViewContact extends JView {
	function display($tpl = null)
	{
		global $mainframe;
		
		$user = &JFactory::getUser();
		$pathway = &$mainframe->getPathway();
		$document = &JFactory::getDocument();
		$model = &$this->getModel();
		$data =& $this->get('FormData');
		$menus = &JSite::getMenu();
		$menu = $menus->getActive();

		$params = &$mainframe->getParams('com_qcontacts');
		
		$model = &$this->getModel();
		$modelCat = &$this->getModel('Category');

		$options['aid']	= $user->get('aid', 0);

		$contact = $model->getContact( $options );

		if (!is_object( $contact )) {
			JError::raiseError( 404, 'Contact not found' );
			return;
		}

		$options['category_id']	= $contact->catid;
		$options['order by'] = 'cd.default_con DESC, cd.ordering ASC';

		$contacts = $modelCat->getContacts( $options );

		if (is_object( $menu ) && isset($menu->query['view']) && $menu->query['view'] == 'contact' && isset($menu->query['id']) && $menu->query['id'] == $contact->id) {
			$menu_params = new JParameter( $menu->params );
			if (!$menu_params->get( 'page_title')) {
				$params->set('page_title',	$contact->name);
			}
		} else {
			$params->set('page_title',	$contact->name);
		}
		$document->setTitle( $params->get( 'page_title' ) );
		

		if (isset($menu) && isset($menu->query['view']) && $menu->query['view'] != 'contact'){
			$pathway->addItem($contact->name, '');
		}

		$cparams =  new JParameter($contact->params);
		$params->merge($cparams);
			
		if ($contact->email_to && $params->get('show_email')) {
			$contact->email_to = JHTML::_('email.cloak', $contact->email_to);
		}

		if ((!empty($contact->address) && $params->get('show_street_address',1)) || 
			(!empty($contact->suburb) && $params->get('show_suburb',1)) || 
			(!empty($contact->state) && $params->get('show_state',1)) || 
			(!empty($contact->postcode) && $params->get('show_postcode',1)) || 
			(!empty($contact->country) && $params->get('show_country',1)))
		{
			$params->set('address_check', 1);
			
		} else {
			$params->set('address_check', 0);
		}

		switch ($params->get('contact_icons'))	{
			case 1 :
				// text
				$params->set('marker_address', JText::_('Address').": ");
				$params->set('marker_email', JText::_('Email').": ");
				$params->set('marker_telephone', JText::_('Telephone').": ");
				$params->set('marker_fax', JText::_('Fax').": ");
				$params->set('marker_mobile', JText::_('Mobile').": ");
				$params->set('marker_skype', JText::_('Skype').": ");
				$params->set('marker_yahoo', JText::_('Yahoo Msg').": ");
				$params->set('marker_misc', JText::_('Information').": ");
				$params->set('marker_web', JText::_('Website').": ");
				$params->set('other_class', ' wtext');
				break;

			case 2 :
				// none
				$params->set('marker_address', '');
				$params->set('marker_email', '');
				$params->set('marker_telephone', '');
				$params->set('marker_mobile', '');
				$params->set('marker_fax', '');
				$params->set('marker_skype', '');
				$params->set('marker_yahoo', '');
				$params->set('marker_misc', '');
				$params->set('marker_web', '');
				$params->set('other_class', '');
				break;

			default :
				// icons
				$image1 = JHTML::_('image.site', 'con_address.png', '/images/M_images/', $params->get('icon_address'), '/images/M_images/', JText::_('Address').": ");
				$image2 = JHTML::_('image.site', 'emailButton.png', '/images/M_images/', $params->get('icon_email'), '/images/M_images/', JText::_('Email').": ");
				$image3 = JHTML::_('image.site', 'con_tel.png', '/images/M_images/', $params->get('icon_telephone'), '/images/M_images/', JText::_('Telephone').": ");
				$image4 = JHTML::_('image.site', 'con_fax.png', '/images/M_images/', $params->get('icon_fax'), '/images/M_images/', JText::_('Fax').": ");
				$image5 = JHTML::_('image.site', 'con_info.png', '/images/M_images/', $params->get('icon_misc'), '/images/M_images/', JText::_('Information').": ");
				$image6 = JHTML::_('image.site', 'con_mobile.png', '/images/M_images/', $params->get('icon_mobile'), '/images/M_images/', JText::_('Mobile').": ");
				$image7 = JHTML::_('image.site', 'skype.png', '/components/com_qcontacts/images/', $params->get('icon_skype'), '/components/com_qcontacts/images/', JText::_('Skype').": ");
				$image8 = JHTML::_('image.site', 'yahoo.gif', '/components/com_qcontacts/images/', $params->get('icon_yahoo'), '/components/com_qcontacts/images/', JText::_('Yahoo Messenger').": ");
				$image9 = JHTML::_('image.site', 'weblink.png', '/images/M_images/', $params->get('icon_web'), '/images/M_images/', JText::_('Website').": ");

				$params->set('marker_address', $image1);
				$params->set('marker_email', $image2);
				$params->set('marker_telephone', $image3);
				$params->set('marker_fax', $image4);
				$params->set('marker_misc', $image5);
				$params->set('marker_mobile', $image6);
				$params->set('marker_skype', $image7);
				$params->set('marker_yahoo', $image8);
				$params->set('marker_web', $image9);
				$params->set('other_class', ' wicon');
				break;
		}
		if($params->get('show_captcha')) {
			$params->set('captcha', JHTML::image('index.php?option=com_qcontacts&amp;controller=captcha&amp;id='.$contact->id.'&amp;format=raw&amp;sid=' . md5(uniqid(time())), 'captcha',array('id'=>'captcha-img')));
		} else {
			$params->set('captcha','');
		}
		$fields = array();
		if($params->get('name_show',2)) {
			$fld = new stdClass;
			$fld->name = 'name';
			$fld->label = 'Enter your name';
			$fld->id = 'contact_name';
			$fld->required = ($params->get('name_show',2)==2);
			$fld->type = 'text';
			$fld->size = $params->get('name_size',30);
			$fields[]=array('order'=>$params->get('name_ord',0), 'field'=>$fld);
		}
		if($params->get('email_show',2)) {
			$fld = new stdClass;
			$fld->name = 'email';
			$fld->label = 'Email address';
			$fld->id = 'contact_email';
			$fld->required = ($params->get('email_show',2)==2);
			$fld->type = 'text';
			$fld->size = $params->get('email_size',30);
			$fields[]=array('order'=>$params->get('email_ord',1), 'field'=>$fld);
		}
			
		if($params->get('subject_show',1)) {
			$fld = new stdClass;
			$fld->name = 'subject';
			$fld->label = 'Message subject';
			$fld->id = 'contact_subject';
			$fld->required = ($params->get('subject_show')==2);
			$fld->type = 'text';
			$fld->size = $params->get('subject_size',30);
			$fields[]=array('order'=>$params->get('subject_ord',2), 'field'=>$fld);
		}
		
		if($params->get('message_show',2)) {
			$fld = new stdClass;
			$fld->name = 'body';
			$fld->label = 'Enter your message';
			$fld->id = 'contact_text';
			$fld->required = ($params->get('message_show',2)==2);
			$fld->type = 'textarea';
			$rc = $this->_textarea_rows_cols($params->get('message_size','10;50'));
			$fld->rows = $rc[0];
			$fld->cols = $rc[1];
			$fields[]=array('order'=>$params->get('message_ord',3), 'field'=>$fld);
		}
		for($i=1; $i<=$model->max_cust_fields; $i++) {
			if($params->get('cust'.$i.'_show') > 0) {
				$cf = new stdClass;
				$cf->name = "cust$i";
				$cf->id = $cf->name;
				$cf->required = ($params->get('cust'.$i.'_show')==2);
				$cf->label = $params->get('cust'.$i.'_label');
				switch(intval($params->get('cust'.$i.'_type'))) {
					case 1:
						$cf->type = 'textarea';
						$rc = $this->_textarea_rows_cols($params->get('cust'.$i.'_size'));
						$cf->rows = $rc[0];
						$cf->cols = $rc[1];
						if(JRequest::getMethod() == 'GET') {
							$data->{"cust$i"} = $params->get('cust'.$i.'_value');
						}
						break;
					case 2:
						$cf->type = 'radio';
						$cf->value = explode(';',$params->get('cust'.$i.'_value'));
						break;
					case 3:
						$cf->type = 'checkbox';
						$cf->value = $params->get('cust'.$i.'_value');
						break;
					case 4:
						$cf->type = 'dropdown';
						$cf->value = explode(';',$params->get('cust'.$i.'_value'));
						break;
					case 0:
					default:
					$cf->type = 'text';
					$cf->size = 30;
					if((int)$params->get('cust'.$i.'_size') > 0) {
						$cf->size = (int)$params->get('cust'.$i.'_size');
					}
					if(JRequest::getMethod() == 'GET') {
						$data->{"cust$i"} = $params->get('cust'.$i.'_value');
					}
					
				}
				$fields[]=array('order'=>$params->get('cust'.$i.'_ord',3+$i), 'field'=>$cf);
			}
		}
		if(count($fields)) {
			foreach ($fields as $k => $v) {
	    		$order[$k] = $v['order'];
			}
			array_multisort($order, SORT_ASC, $fields);
		} else {
			$params->set('show_email_form',0);
		}
		JHTML::stylesheet('qcontacts.css', 'components/com_qcontacts/css/');
		$this->assignRef('contact', $contact);
		$this->assignRef('contacts', $contacts);
		$this->assignRef('params', $params);
		$this->assignRef('fields', $fields);
		
		$this->assignRef('data', $data);
		parent::display($tpl);
	}
	function _textarea_rows_cols($p) {
		$r = array(10,50);
		$s = explode(';', $p);
		if(isset($s[0]) && (int)$s[0] > 0) {
			$r[0] = (int)$s[0];
		}
		if(isset($s[1]) && (int)$s[1] > 0) {
			$r[1] = (int)$s[1];
		}	
		return $r;
	}
}