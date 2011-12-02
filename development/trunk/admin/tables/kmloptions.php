<?php
/**
 * ChurchDirectory Contact manager component for Joomla! 1.5 and 1.6
 *
 * @version 1.6.0
 * @package churchdirectory
 * @author NFSDA
 * @copyright Copyright (C) 2011 NFSDA. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */


defined('_JEXEC') or die;

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
		parent::__construct( '#__churchdirectory_details', 'id', $db );
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
