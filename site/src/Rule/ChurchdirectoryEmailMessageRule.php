<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Site\Rule;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Rejects enquiry-form messages that match the `banned_text` blacklist.
 *
 * @since  2.0.0
 */
class ChurchdirectoryEmailMessageRule extends FormRule
{
    public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null): bool
    {
        $banned = (string) ComponentHelper::getParams('com_churchdirectory')->get('banned_text', '');

        foreach (explode(';', $banned) as $item) {
            if ($item !== '' && StringHelper::stristr($item, $value) !== false) {
                return false;
            }
        }

        return true;
    }
}
