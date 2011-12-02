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

class TableContact extends JTable {
	/** @var int Primary key */
	var $id = null;
	/** @var string */
	var $name = null;
	/** @var string */
	var $lname = null;
	/** @var string */
	var $alias = null;
	/** @var string */
	var $con_position = null;
	/** @var string */
	var $address = null;
	/** @var string */
	var $suburb = null;
	/** @var string */
	var $state = null;
	/** @var string */
	var $country = null;
	/** @var string */
	var $postcode = null;
	/** @var string */
	var $postcodeaddon = null;
	/** @var string */
	var $telephone = null;
	/** @var string */
	var $fax = null;
	/** @var string */
	var $misc = null;
	/** @var string */
	var $spouse = null;
	/** @var string */
	var $children = null;
	/** @var string */
	var $image = null;
	/** @var string */
	var $imagepos = null;
	/** @var string */
	var $email_to = null;
	/** @var int */
	var $default_con = null;
	/** @var int */
	var $published = 0;
	/** @var int */
	var $checked_out = 0;
	/** @var datetime */
	var $checked_out_time = 0;
	/** @var int */
	var $ordering = null;
	/** @var string */
	var $params = null;
	/** @var int A link to a registered user */
	var $user_id = null;
	/** @var int A link to a category */
	var $catid = null;
	/** @var int */
	var $access = null;
	/** @var string Mobile phone number(s) */
	var $mobile = null;
	/** @var string */
	var $webpage = null;
	/** @var string */
	var $skype = null;
	/** @var string */
	var $yahoo_msg = null;
	/** @var string */
	var $lat = null;
	/** @var string */
	var $lng = null;
	/** @var string */
	var $team = null;
	/** @var string */
	var $teamicon = null;
	/**
	* @param database A database connector object
	*/
	function __construct(&$db) {
		parent::__construct( '#__qcontacts_details', 'id', $db );
	}

	function check()
	{
		$this->default_con = intval( $this->default_con );

		if (JFilterInput::checkAttribute(array ('href', $this->webpage))) {
			$this->setError(JText::_('Please provide a valid URL'));
			return false;
		}

		if (strlen($this->webpage) > 0 && (!(eregi('http://', $this->webpage) || (eregi('https://', $this->webpage)) || (eregi('ftp://', $this->webpage))))) {
			$this->webpage = 'http://'.$this->webpage;
		}

		if(empty($this->alias)) {
			$this->alias = $this->name;
		}
		$this->alias = JFilterOutput::stringURLSafe($this->alias);
		if(trim(str_replace('-','',$this->alias)) == '') {
			$datenow =& JFactory::getDate();
			$this->alias = $datenow->toFormat("%Y-%m-%d-%H-%M-%S");
		}

		return true;
	}
}
