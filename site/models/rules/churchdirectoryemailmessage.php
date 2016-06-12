<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

jimport('joomla.form.formrule');

/**
 * Rule to check for email message
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class JFormRuleChurchDirectoryEmailMessage extends JFormRule
{

	/**
	 * Method to test the value.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 * @param   Registry          $input    An optional JRegistry object with the entire data set to validate against the entire form.
	 * @param   JForm             $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
	{
		$params = JComponentHelper::getParams('com_churchdirectory');
		$banned = $params->get('banned_text');

		foreach (explode(';', $banned) as $item)
		{
			if (\Joomla\String\StringHelper::stristr($item, $value) !== false)
			{
				return false;
			}
		}

		return true;
	}

}
