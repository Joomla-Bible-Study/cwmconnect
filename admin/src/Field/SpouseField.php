<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Administrator\Helper\ChurchdirectoryHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Read-only "spouse" field — looks up the spouse of the member being
 * edited based on the family-unit grouping.
 *
 * @since  2.0.0
 */
class SpouseField extends FormField
{
    /**
     * The form field type.
     *
     * @var string
     * @since 2.0.0
     */
    protected $type = 'Spouse';

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
        $categoryId     = (int) $this->form->getValue('catid');
        $funitId        = (int) $this->form->getValue('funitid');
        $memberFuStatus = (int) $this->form->getValue('familypostion', 'attribs');

        if ($categoryId === 0 || $funitId === 0) {
            return $this->renderInput(0, '');
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'funitid', 'attribs', 'spouse', 'mstatus']))
            ->from($db->quoteName('#__churchdirectory_details'))
            ->where($db->quoteName('catid') . ' = ' . (int) $categoryId)
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('funitid') . ' = ' . (int) $funitId);
        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        foreach ($rows as $item) {
            $registry = new Registry((string) ($item->attribs ?? ''));
            $value    = $db->escape((string) $item->name) . ' '
                . ChurchdirectoryHelper::memberStatusShort((int) $item->mstatus);

            if (
                (int) $item->funitid !== 0
                && (int) $item->id !== $memberId
                && (int) $registry->get('familypostion', 2) !== $memberFuStatus
                && $memberFuStatus !== 2
            ) {
                return $this->renderInput((int) $item->id, $value);
            }

            if (
                (int) $item->funitid <= 0
                && (int) $item->id === $memberId
                && !empty($item->spouse)
            ) {
                return $this->renderInput((int) $item->id, 'Old Record: ' . $db->escape((string) $item->spouse));
            }
        }

        return $this->renderInput(0, '');
    }

    /**
     * Render the read-only `<input>` markup.
     *
     * @param   int     $rowId  Row id used as the input id attribute.
     * @param   string  $value  The pre-rendered value to display.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    private function renderInput(int $rowId, string $value): string
    {
        return sprintf(
            '<input type="text" id="%d" value="%s" readonly />',
            $rowId,
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
        );
    }
}
