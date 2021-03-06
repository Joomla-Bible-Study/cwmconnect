<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Class for DirHeaders
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryControllerDirHeaders extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  Ingnore info
	 *
	 * @return  bool|JModelLegacy
	 *
	 * @since    1.7.0
	 */
	public function getModel($name = 'DirHeader', $prefix = 'ChurchDirectoryModel', $config = ['ignore_request' => true])
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
}
