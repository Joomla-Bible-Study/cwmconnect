<?php

/**
 * @package		ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;

jimport('joomla.form.formrule');

/**
 * Rule to check for email message
 * @package ChurchDirectory.Site
 * @since 1.7.0
 */

class JFormRuleChurchDirectoryEmailMessage extends JFormRule {

    public function test(& $element, $value, $group = null, & $input = null, & $form = null) {
        $params = JComponentHelper::getParams('com_churchdirectory');
        $banned = $params->get('banned_text');

        foreach (explode(';', $banned) as $item) {
            if (JString::stristr($item, $value) !== false)
                return false;
        }

        return true;
    }

}
