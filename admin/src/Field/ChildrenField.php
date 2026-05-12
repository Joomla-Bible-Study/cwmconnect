<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Administrator\Helper\CwmconnectHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Read-only "children" field — lists every child member belonging to the
 * same family unit as the row being edited.
 *
 * @since  2.0.0
 */
class ChildrenField extends FormField
{
    /**
     * The form field type.
     *
     * @var string
     * @since 2.0.0
     */
    protected $type = 'Children';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function getInput(): string
    {
        $memberId       = (int) $this->form->getValue('id');
        $funitId        = (int) $this->form->getValue('funitid');
        $memberFuStatus = (int) $this->form->getValue('familypostion', 'attribs');
        $children       = [];

        if ($funitId === 0) {
            return $this->renderInput($memberId, '');
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'funitid', 'attribs', 'spouse', 'mstatus']))
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('funitid') . ' = ' . (int) $funitId);
        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        foreach ($rows as $item) {
            $registry = new Registry((string) ($item->attribs ?? ''));

            if (
                (int) $registry->get('familypostion') === 2
                && ($memberFuStatus !== 2 || $memberFuStatus === 0)
            ) {
                $children[] = $db->escape((string) $item->name) . ' '
                    . CwmconnectHelper::memberStatusShort((int) $item->mstatus);
            }
        }

        return $this->renderInput($memberId, implode(', ', $children));
    }

    /**
     * Render the read-only `<input>` markup.
     *
     * @param   int     $memberId  Member id used as the input id attribute.
     * @param   string  $value     The pre-rendered value to display.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    private function renderInput(int $memberId, string $value): string
    {
        return sprintf(
            '<input type="text" id="%d" style="width: 500px" value="%s" readonly />',
            $memberId,
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
        );
    }
}
