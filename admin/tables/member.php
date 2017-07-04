<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Registry\Registry;

/**
 * Member Table Class
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryTableMember extends JTable
{
	public $id;

	public $name;

	public $lname;

	public $alias;

	public $con_position;

	public $contact_id;

	public $address;

	public $suburb;

	public $state;

	public $country;

	public $postcode;

	public $postcodeaddon;

	public $telephone;

	public $fax;

	public $misc;

	public $spouse;

	public $children;

	public $image;

	public $imagepos;

	public $email_to;

	public $default_con;

	public $published;

	public $checked_out;

	public $checked_out_time;

	public $ordering;

	public $params;

	public $user_id;

	public $catid;

	public $kmlid;

	public $funitid;

	public $access;

	public $mobile;

	public $webpage;

	public $sortname1;

	public $sortname2;

	public $sortname3;

	public $language;

	public $created;

	public $created_by;

	public $created_by_alias;

	public $modified;

	public $modified_by;

	public $metakey;

	public $metadesc;

	public $metadata;

	public $featured;

	public $xreference;

	public $publish_up;

	public $publish_down;

	public $skype;

	public $yahoo_msg;

	public $lat;

	public $lng;

	public $birthdate;

	public $anniversary;

	public $attribs;

	public $version;

	public $hits;

	public $surname;

	public $mstatus;

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database connector object
	 *
	 * @since 1.0
	 */
	public function __construct (& $db)
	{
		$this->_jsonEncode = ['params', 'attribs', 'metadata'];

		parent::__construct('#__churchdirectory_details', 'id', $db);

		JTableObserverTags::createObserver($this, array('typeAlias' => 'com_churchdirectory.member'));
		JTableObserverContenthistory::createObserver($this, array('typeAlias' => 'com_churchdirectory.member'));
	}

	/**
	 * Override bind function
	 *
	 * @param   mixed  $array   An associative array or object to bind to the JTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link     http://docs.joomla.org/JTable/bind
	 * @since    1.7.0
	 */
	public function bind($array, $ignore = '')
	{
		if (array_key_exists('con_position', $array) && is_array($array['con_position']))
		{
			$array['con_position'] = implode(',', $array['con_position']);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Stores a Member
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return    boolean    True on success, false on failure.
	 *
	 * @since    1.7.0
	 */
	public function store($updateNulls = false)
	{
		// Transform the params field
		if (is_array($this->params))
		{
			$registry = new Registry($this->params);
			$this->params = (string) $registry;
		}

		// Transform the attribs field
		if (is_array($this->attribs))
		{
			$registry = new Registry($this->attribs);
			$this->attribs = (string) $registry;
		}

		// Force the Valu of FamilyPostion if Family unit = -1
		if ($this->funitid == '-1')
		{
			$registry = new Registry($this->attribs);
			$registry->set('familypostion', '0');
			$this->attribs = (string) $registry;
		}

		$date   = JFactory::getDate()->toSql();
		$userId = JFactory::getUser()->id;

		$this->modified = $date;

		if ($this->id)
		{
			// Existing item
			$this->modified_by = $userId;
		}
		else
		{
			// New newsfeed. A feed created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!intval($this->created))
			{
				$this->created = $date;
			}

			if (empty($this->created_by))
			{
				$this->created_by = $userId;
			}
		}

		// Set publish_up to null date if not set
		if (!$this->publish_up)
		{
			$this->publish_up = $this->_db->getNullDate();
		}

		// Set publish_down to null date if not set
		if (!$this->publish_down)
		{
			$this->publish_down = $this->_db->getNullDate();
		}

		// Set xreference to empty string if not set
		if (!$this->xreference)
		{
			$this->xreference = '';
		}

		// Store utf8 email as punycode
		$this->email_to = JStringPunycode::emailToPunycode($this->email_to);

		// Convert IDN urls to punycode
		$this->webpage = JStringPunycode::urlToPunycode($this->webpage);

		// Verify that the alias is unique
		$table = JTable::getInstance('Member', 'ChurchDirectoryTable');

		if ($table->load(['alias' => $this->alias, 'catid' => $this->catid]) && ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_ERROR_UNIQUE_ALIAS'));

			return false;
		}

		// Attempt to store the data.
		return parent::store($updateNulls);
	}

	/**
	 * Overloaded check function
	 *
	 * @return boolean
	 *
	 * @see   JTable::check
	 * @since 1.7.0
	 */
	public function check()
	{
		$this->default_con = intval($this->default_con);

		if (JFilterInput::checkAttribute(['href', $this->webpage]))
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_URL'));

			return false;
		}

		/** Check for valid name */
		if (trim($this->name) == '')
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_NAME'));

			return false;
		}

		// Generate a valid alias
		$this->generateAlias();

		/** check for valid category */
		if (trim($this->catid) == '')
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_CATEGORY'));

			return false;
		}

		// Sanity check for user_id
		if (!($this->user_id))
		{
			$this->user_id = 0;
		}

		// Check the publish down date is not earlier than publish up.
		if (intval($this->publish_down) > 0 && $this->publish_down < $this->publish_up)
		{
			$this->setError(JText::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		// Clean up keywords -- eliminate extra spaces between phrases
		// and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey))
		{
			// Only process if not empty
			$badCharacters = ["\n", "\r", "\"", "<", ">"];

			// Remove bad characters.
			$afterClean = \Joomla\String\StringHelper::str_ireplace($badCharacters, '', $this->metakey);

			// Create array using commas as delimiter.
			$keys = explode(',', $afterClean);
			$cleanKeys = array();

			foreach ($keys as $key)
			{
				if (trim($key))
				{
					// Ignore blank keywords
					$clean_keys[] = trim($key);
				}
			}

			$this->metakey = implode(", ", $cleanKeys);
		}

		// Clean up description -- eliminate quotes and <> brackets
		if (!empty($this->metadesc))
		{
			// Only process if not empty
			$bad_characters = ["\"", "<", ">"];
			$this->metadesc = \Joomla\String\StringHelper::str_ireplace($bad_characters, "", $this->metadesc);
		}

		return true;
	}

	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return      string
	 *
	 * @since       1.6
	 */
	protected function _getAssetTitle()
	{
		$title = $this->name;

		return $title;
	}

	/**
	 * Get the parent asset id for the record
	 *
	 * @param   JTable  $table  ?
	 * @param   int     $id     ?
	 *
	 * @return      int
	 *
	 * @since       1.6
	 */
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		/** @var \JTableAsset $asset */
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_churchdirectory');

		return (int) $asset->id;
	}

	/**
	 * Generate a valid alias from title / date.
	 * Remains public to be able to check for duplicated alias before saving
	 *
	 * @return  string
	 *
	 * @since    1.7.0
	 */
	public function generateAlias()
	{
		if (empty($this->alias))
		{
			$this->alias = $this->name;
		}

		$this->alias = JApplicationHelper::stringURLSafe($this->alias, $this->language);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = JFactory::getDate()->format("Y-m-d-H-i-s");
		}

		return $this->alias;
	}
}
