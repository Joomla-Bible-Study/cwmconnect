<?php
/**
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('_JEXEC') or die;

/**
 * For Getting GeoUpdate Status from Google
 *
 * @package             ChurchDirectory.Admin
 * @since               1.7.0
 */
class ChurchDirectoryModelGeoStatus extends JModelLegacy {

	public function getGeoErrors(){

		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('u.*, m.*')->from('#__churchdirectory_details AS m');
		$query->leftJoin('#__churchdirectory_geoupdate AS u ON m.id = u.member_id ');
		$query->where('m.id = u.member_id');
		$db->setQuery($query);
		return $db->loadObjectList();
	}

}