<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Registry\Registry;

/**
 * Position Table Class
 *
 * @property mixed webpage
 * @property mixed name
 * @property mixed id
 * @property mixed alias
 * @property mixed publish_down
 * @property mixed publish_up
 * @property mixed created
 * @property mixed created_by
 * @property mixed modified
 * @property mixed modified_by
 * @property mixed params
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryTablePosition extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database connector object
	 *
	 * @since 1.0
	 */
	public function __construct(& $db)
	{
		$this->_jsonEncode = ['params'];

		parent::__construct('#__churchdirectory_position', 'id', $db);
	}

	/**
	 * Stores a position
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
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

		// Attempt to store the data.
		return parent::store($updateNulls);
	}

	/**
	 * Override check function
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
		$query->select('id')
			->from('#__churchdirectory_position')
			->where('name = ' . $this->_db->q($this->name));
		$this->_db->setQuery($query);
		$xid = intval($this->_db->loadResult());

		if ($xid && $xid != intval($this->id))
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_SAME_NAME'));

			return false;
		}

		// Check the publish down date is not earlier than publish up.
		if ((int) $this->publish_down > 0 && $this->publish_down < $this->publish_up)
		{
			$this->setError(JText::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		// Generate a valid alias
		$this->generateAlias();

		return true;
	}

	/**
	 * Generate a valid alias from title / date.
	 * Remains public to be able to check for duplicated alias before saving
	 *
	 * @return  string
	 *
	 * @since 1.7.3
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
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
		}

		return $this->alias;
	}
}
