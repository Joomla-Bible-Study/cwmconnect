<?php
/**
 * @package     ChurchDirectory.Site
 * @subpackage  mod_birthdayanniversary
 * @copyright   2007 - 2016 (C) Joomla Bible Study Team All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * helper for Birthdy Anniversary Display
 *
 * @package     ChurchDirectory.Site
 * @subpackage  mod_birthdayanniversary
 * @since       1.7.2
 */
class ModBirthdayAnniversaryHelper
{
	/**
	 * Convert a stdClass to an Array.
	 *
	 * @param   stdClass  $Class  Setup Variable
	 *
	 * @return array
	 *
	 * @since       1.7.2
	 */
	static public function object_to_array (stdClass $Class)
	{
		// Typecast to (array) automatically converts stdClass -> array.
		$Class = (array) $Class;

		// Iterate through the former properties looking for any stdClass properties.
		// Recursively apply (array).
		foreach ($Class as $key => $value)
		{
			if (is_object($value) && get_class($value) === 'stdClass')
			{
				$Class[$key] = self::object_to_array($value);
			}
		}

		return $Class;
	}
}
