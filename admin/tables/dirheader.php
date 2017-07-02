<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Registry\Registry;

/**
 * DirHeader Table Class
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryTableDirHeader extends JTable
{
	public $id;

	public $name;

	public $alias;

	public $params;

	public $created;

	public $webpage;

	public $publish_down;

	public $publish_up;

	/**
	 * Ensure the params and metadata in json encoded in the bind method
	 *
	 * @var    array
	 * @since  3.3
	 */
	protected $_jsonEncode = array('params');

	public $catid;

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database connector object
	 *
	 * @since 1.7.0
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__churchdirectory_dirheader', 'id', $db);
	}

	/**
	 * Stores a header
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

		// Verify that the alias is unique
		/** @var \ChurchDirectoryTableDirHeader $table */
		$table = JTable::getInstance('DirHeader', 'ChurchDirectoryTable');

		if ($table->load(array('alias' => $this->alias, 'catid' => $this->catid)) && ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(JText::_('COM_CONTACT_ERROR_UNIQUE_ALIAS'));

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
		if (JFilterInput::checkAttribute(['href', $this->webpage]))
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
		$query->select('*')
			->from('#__churchdirectory_dirheader')
			->where($this->_db->qn('name') . ' = ' . $this->_db->q($this->name));
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
