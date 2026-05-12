<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Kml;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Model\KmlModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit a Kml record.
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
        /** @var KmlModel $model */
        $model = $this->getModel();

        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();
        $this->canDo = ContentHelper::getActions('com_cwmconnect');

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
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
                ? Text::_('COM_CWMCONNECT_MANAGER_KML_NEW')
                : Text::_('COM_CWMCONNECT_MANAGER_KML_EDIT'),
            'cwmconnect'
        );

        if ($isNew) {
            if (\count($user->getAuthorisedCategories('com_cwmconnect', 'core.create')) > 0) {
                ToolbarHelper::apply('kml.apply');
                ToolbarHelper::save('kml.save');
                ToolbarHelper::save2new('kml.save2new');
            }

            ToolbarHelper::cancel('kml.cancel');
        } else {
            if (!$checkedOut) {
                if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && (int) $this->item->created_by === $userId)) {
                    ToolbarHelper::apply('kml.apply');
                    ToolbarHelper::save('kml.save');
                }
            }

            if ($canDo->get('core.create')) {
                ToolbarHelper::save2copy('kml.save2copy');
            }

            ToolbarHelper::cancel('kml.cancel', 'JTOOLBAR_CLOSE');
        }

        ToolbarHelper::divider();
        ToolbarHelper::help('cwmconnect_kml', true);
    }
}
