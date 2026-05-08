<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Field\Modal;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ModalSelectField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

/**
 * Modal member picker.
 *
 * Mirrors com_contact's ContactField but pointed at the churchdirectory
 * members list. The form XML referenced the legacy `modal_members` type,
 * which Joomla 5 resolves to this class via the component namespace.
 *
 * @since  2.0.0
 */
class MembersField extends ModalSelectField
{
    /**
     * The form field type.
     *
     * @var string
     * @since 2.0.0
     */
    protected $type = 'Modal_Members';

    /**
     * Method to attach a Form object to the field.
     *
     * @param   \SimpleXMLElement  $element  Field element.
     * @param   mixed              $value    Field value.
     * @param   string|null        $group    Field group.
     *
     * @return  bool
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    #[\Override]
    public function setup(\SimpleXMLElement $element, $value, $group = null): bool
    {
        if ($value && \is_string($value) && str_contains($value, ':')) {
            [$id]  = explode(':', $value, 2);
            $value = (int) $id;
        }

        $result = parent::setup($element, $value, $group);

        if (!$result) {
            return $result;
        }

        Factory::getApplication()->getLanguage()->load('com_churchdirectory', JPATH_ADMINISTRATOR);

        $linkItems = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $linkItems->setQuery([
            'option'                => 'com_churchdirectory',
            'view'                  => 'members',
            'layout'                => 'modal',
            'tmpl'                  => 'component',
            Session::getFormToken() => 1,
        ]);
        $linkItem = clone $linkItems;
        $linkItem->setVar('view', 'member');

        $linkCheckin = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $linkCheckin->setQuery([
            'option'                => 'com_churchdirectory',
            'task'                  => 'members.checkin',
            'format'                => 'json',
            Session::getFormToken() => 1,
        ]);

        $urlEdit = clone $linkItem;
        $urlEdit->setVar('task', 'member.edit');
        $urlNew  = clone $linkItem;
        $urlNew->setVar('task', 'member.add');

        $this->urls['select']  = (string) $linkItems;
        $this->urls['new']     = (string) $urlNew;
        $this->urls['edit']    = (string) $urlEdit;
        $this->urls['checkin'] = (string) $linkCheckin;

        $this->modalTitles['select'] = Text::_('COM_CHURCHDIRECTORY_SELECT_A_MEMBER');
        $this->modalTitles['new']    = Text::_('COM_CHURCHDIRECTORY_NEW_MEMBER');
        $this->modalTitles['edit']   = Text::_('COM_CHURCHDIRECTORY_EDIT_MEMBER');

        $this->hint = $this->hint ?: Text::_('COM_CHURCHDIRECTORY_SELECT_A_MEMBER');

        return $result;
    }

    /**
     * Method to retrieve the title of selected item.
     *
     * @return  string
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function getValueTitle(): string
    {
        $value = (int) $this->value ?: 0;

        if ($value === 0) {
            return '';
        }

        try {
            $db    = $this->getDatabase();
            $query = $db->createQuery()
                ->select($db->quoteName('name'))
                ->from($db->quoteName('#__churchdirectory_details'))
                ->where($db->quoteName('id') . ' = :value')
                ->bind(':value', $value, ParameterType::INTEGER);
            $db->setQuery($query);

            return (string) ($db->loadResult() ?? $value);
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        return (string) $value;
    }

    /**
     * Get the renderer.
     *
     * @param   string  $layoutId  Layout id to load.
     *
     * @return  FileLayout
     *
     * @since   2.0.0
     */
    protected function getRenderer($layoutId = 'default'): FileLayout
    {
        $layout = parent::getRenderer($layoutId);
        $layout->setComponent('com_churchdirectory');
        $layout->setClient(1);

        return $layout;
    }
}
