<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\View\Member;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Administrator\Model\MemberModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit a Member.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var Form|null
     * @since 2.0.0
     */
    protected ?Form $form = null;

    /**
     * @var object|null
     * @since 2.0.0
     */
    protected ?object $item = null;

    /**
     * @var mixed
     * @since 2.0.0
     */
    protected mixed $state = null;

    /**
     * @var mixed
     * @since 2.0.0
     */
    protected mixed $canDo = null;

    /**
     * @var array
     * @since 2.0.0
     */
    protected array $groups = [];

    /**
     * @var int|string
     * @since 2.0.0
     */
    protected int|string $age = 0;

    /**
     * @var bool
     * @since 2.0.0
     */
    protected bool $access = false;

    /**
     * Display the view.
     *
     * @param   string|null  $tpl  The template file to parse.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var MemberModel $model */
        $model = $this->getModel();

        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();
        $this->canDo = ContentHelper::getActions(
            'com_churchdirectory',
            'category',
            (int) ($this->item->catid ?? 0)
        );

        $user         = $this->getCurrentUser();
        $this->groups = $user->groups;
        $this->age    = self::calculateAge((string) $this->form->getValue('birthdate'));

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $params         = ComponentHelper::getParams('com_churchdirectory');
        $protectedAccess = (string) $params->get('protectedaccess');
        $groups         = $this->groups;
        $this->access   = (
            ($protectedAccess !== '' && isset($groups[$protectedAccess]))
            || isset($groups[8])
        );

        // Forced language in modal layout (used for associations).
        if (
            $this->getLayout() === 'modal'
            && $forcedLanguage = Factory::getApplication()->getInput()->get('forcedLanguage', '', 'cmd')
        ) {
            $this->form->setValue('language', null, $forcedLanguage);
            $this->form->setFieldAttribute('language', 'readonly', 'true');
            $this->form->setFieldAttribute('catid', 'language', '*,' . $forcedLanguage);
            $this->form->setFieldAttribute('tags', 'language', '*,' . $forcedLanguage);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @throws \Exception
     * @since 2.0.0
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $user       = $this->getCurrentUser();
        $userId     = (int) $user->id;
        $isNew      = ((int) $this->item->id === 0);
        $checkedOut = !((int) $this->item->checked_out === 0 || (int) $this->item->checked_out === $userId);
        $canDo      = $this->canDo;

        ToolbarHelper::title(
            $isNew
                ? Text::_('COM_CHURCHDIRECTORY_MANAGER_MEMBER_NEW')
                : Text::_('COM_CHURCHDIRECTORY_MANAGER_MEMBER_EDIT'),
            'address contact'
        );

        if ($isNew) {
            if (\count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0) {
                ToolbarHelper::apply('member.apply');
                ToolbarHelper::save('member.save');
                ToolbarHelper::save2new('member.save2new');
            }

            ToolbarHelper::cancel('member.cancel');
        } else {
            $itemEditable = $canDo->get('core.edit')
                || ($canDo->get('core.edit.own') && (int) $this->item->created_by === $userId);

            if (!$checkedOut && $itemEditable) {
                ToolbarHelper::apply('member.apply');
                ToolbarHelper::save('member.save');

                if ($canDo->get('core.create')) {
                    ToolbarHelper::save2new('member.save2new');
                }
            }

            if ($canDo->get('core.create')) {
                ToolbarHelper::save2copy('member.save2copy');
            }

            if (
                ComponentHelper::isEnabled('com_contenthistory')
                && $this->state->params->get('save_history', 0)
                && $itemEditable
            ) {
                ToolbarHelper::versions('com_churchdirectory.member', $this->item->id);
            }

            ToolbarHelper::cancel('member.cancel', 'JTOOLBAR_CLOSE');
        }

        ToolbarHelper::divider();
        ToolbarHelper::help('churchdirectory_member', true);
    }

    /**
     * Calculate the age in years from a birth date.
     *
     * @param   string  $birthDate  The birth date string.
     *
     * @return  int|string  Number of years, or '0' when no/future date.
     *
     * @since   2.0.0
     */
    protected static function calculateAge(string $birthDate): int|string
    {
        if ($birthDate === '' || $birthDate === '0000-00-00' || $birthDate === '0000-00-00 00:00:00') {
            return '0';
        }

        try {
            $date     = new \DateTime($birthDate);
            $now      = new \DateTime();
            $interval = $now->diff($date);

            return (int) $interval->y;
        } catch (\Exception) {
            return '0';
        }
    }
}
