<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2005 - 2011 Joomla! Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Controller Home for ChurchDirectory
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryControllerHome extends JControllerForm
{
	/**
	 * Get model
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since       1.7.2
	 */
	public function getModel($name = '', $prefix = '', $config = [])
	{
		return parent::getModel($name, $prefix, $config = ['ignore_request' => true]);
	}
}
