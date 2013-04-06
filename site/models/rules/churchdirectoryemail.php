<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die;

jimport('joomla.form.formrule');

require_once 'libraries/joomla/form/rules/email.php';

/**
 * Rule to check email
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class JFormRuleChurchDirectoryEmail extends JFormRuleEmail
{

	/**
	 * Method to test the email address and optionally check for uniqueness.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 * @param   JRegistry         $input    An optional JRegistry object with the entire data set to validate against the entire form.
	 * @param   JForm             $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, JRegistry $input = null, JForm $form = null)
	{
		if (!parent::test($element, $value, $group, $input, $form))
		{
			return false;
		}

		$params = JComponentHelper::getParams('com_churchdirectory');
		$banned = $params->get('banned_email');

		foreach (explode(';', $banned) as $item)
		{
			if (JString::stristr($item, $value) !== false)
				return false;
		}

		return true;
	}

}
