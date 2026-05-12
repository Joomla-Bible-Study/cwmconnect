<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Site\Rule;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\Rule\EmailRule;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Email-address validator that additionally rejects values matching the
 * component's `banned_email` semicolon-delimited blacklist.
 *
 * @since  2.0.0
 */
class ChurchdirectoryEmailRule extends EmailRule
{
    public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null): bool
    {
        if (!parent::test($element, $value, $group, $input, $form)) {
            return false;
        }

        $banned = (string) ComponentHelper::getParams('com_churchdirectory')->get('banned_email', '');

        foreach (explode(';', $banned) as $item) {
            if ($item !== '' && StringHelper::stristr($item, $value) !== false) {
                return false;
            }
        }

        return true;
    }
}
