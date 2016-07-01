<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Registry\Registry;

/**
 * KML Table Class
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryTableKML extends JTable
{
	public $name;

	public $alias;

	public $params;

	public $id;

	public $created;

	public $webpage;

	public $publish_down;

	public $publish_up;

	public $ordering;

	public $modified;

	public $modified_by;

	public $created_by;

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  JDatabaseDriver object.
	 *
	 * @since 1.7.0
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__churchdirectory_kml', 'id', $db);
	}

	/**
	 * Overloaded bind function
	 *
	 * @param   mixed  $array   An associative array or object to bind to the JTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since    1.7.0
	 */
	public function bind($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Stores a kml
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
			$registry = new Registry;
			$registry->loadArray($this->params);
			$this->params = (string) $registry;
		}

		$date = JFactory::getDate();
		$user = JFactory::getUser();

		if ($this->id)
		{
			// Existing item
			$this->modified    = $date->toSql();
			$this->modified_by = $user->get('id');
		}
		else
		{
			// New newsfeed. A feed created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!intval($this->created))
			{
				$this->created = $date->toSql();
			}
			if (empty($this->created_by))
			{
				$this->created_by = $user->get('id');
			}
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

		if (JFilterInput::checkAttribute(array('href', $this->webpage)))
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_URL'));

			return false;
		}

		// Check for http, https, ftp on webpage
		if ((strlen($this->webpage) > 0)
			&& (stripos($this->webpage, 'http://') === false)
			&& (stripos($this->webpage, 'https://') === false)
			&& (stripos($this->webpage, 'ftp://') === false))
		{
			$this->webpage = 'http://' . $this->webpage;
		}

		/** check for valid name */
		if (trim($this->name) == '')
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_NAME'));

			return false;
		}
		/** check for existing name */
		$query = $this->_db->getQuery(true);
		$query->select('id')->from('#__churchdirectory_kml')->where('name = ' . $this->_db->q($this->name));
		$this->_db->setQuery($query);

		$xid = intval($this->_db->loadResult());
		if ($xid && $xid != intval($this->id))
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_SAME_NAME'));

			return false;
		}

		if (empty($this->alias))
		{
			$this->alias = $this->name;
		}
		$this->alias = JApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = JFactory::getDate()->format("Y-m-d-H-i-s");
		}

		// Check the publish down date is not earlier than publish up.
		if (intval($this->publish_down) > 0 && $this->publish_down < $this->publish_up)
		{
			// Swap the dates.
			$temp               = $this->publish_up;
			$this->publish_up   = $this->publish_down;
			$this->publish_down = $temp;
		}

		return true;
	}

}
