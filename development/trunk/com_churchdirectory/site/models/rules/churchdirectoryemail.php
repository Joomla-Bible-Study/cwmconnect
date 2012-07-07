<?php

/**
 * @package		ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

jimport('joomla.form.formrule');

require_once 'libraries/joomla/form/rules/email.php';

/**
 * Rule to check email
 * @package ChurchDirectory.Site
 * @since 1.7.0
 */
class JFormRuleChurchDirectoryEmail extends JFormRuleEmail {

    public function test(& $element, $value, $group = null, & $input = null, & $form = null) {
        if (!parent::test($element, $value, $group, $input, $form)) {
            return false;
        }

        $params = JComponentHelper::getParams('com_churchdirectory');
        $banned = $params->get('banned_email');

        foreach (explode(';', $banned) as $item) {
            if (JString::stristr($item, $value) !== false)
                return false;
        }

        return true;
    }

}
