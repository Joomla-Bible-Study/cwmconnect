<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * KMLs list controller class.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryControllerKMLs extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  Ingnore info
	 *
	 * @return    JModel
	 *
	 * @since    1.7.0
	 */
	public function getModel($name = 'KML', $prefix = 'ChurchDirectoryModel', $config = ['ignore_request' => true])
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
}
