<?php
/**
 * ChurchDirectory Helper
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Class ChurchDirectoryRportobuild
 *
 * @since  1.7.10
 */
class ChurchDirectoryDB
{
	/**
	 * Import Members/Family
	 *
	 * @param   array  $data  Data.
	 *
	 * @since   1.7.10
	 *
	 * @return bool
	 */
	public function import($data)
	{
		// Hold
	}

	/**
	 * Export Members/Family
	 *
	 * @param   string  $type  Export in what type of file
	 *                         Supported file types. "CSV, PDF, XML".
	 *
	 * @return  void
	 *
	 * @since 1.7.10
	 */
	public function export($type = 'csv')
	{
		// Hold
	}

	/**
	 * Get KML Table
	 *
	 * @return mixed
	 *
	 * @since 1.8.1
	 */
	public function getKMLdb()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)->select('*')->from('#__churchdirectory_kml')->where('id = ' . 1);
		$db->setQuery($query);

		$kml = $db->loadObject();

		// Loade Reg
		$reg = new Joomla\Registry\Registry;
		$reg->loadString($kml->params);
		$kml->params = $reg;

		return $kml;
	}
}
