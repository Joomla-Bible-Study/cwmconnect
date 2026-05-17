<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Pcmapping;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Model\PcmappingModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Phase D: edit view for one PC ↔ Joomla field mapping row.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    protected ?Form $form = null;

    protected ?object $item = null;

    protected mixed $state = null;

    protected mixed $canDo = null;

    #[\Override]
    public function display($tpl = null): void
    {
        /** @var PcmappingModel $model */
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

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew = ((int) ($this->item->id ?? 0) === 0);
        $canDo = $this->canDo;

        ToolbarHelper::title(
            $isNew
                ? Text::_('COM_CWMCONNECT_MANAGER_PCMAPPING_NEW')
                : Text::_('COM_CWMCONNECT_MANAGER_PCMAPPING_EDIT'),
            'cwmconnect',
        );

        if ($isNew) {
            if ($canDo->get('core.create')) {
                ToolbarHelper::apply('pcmapping.apply');
                ToolbarHelper::save('pcmapping.save');
            }

            ToolbarHelper::cancel('pcmapping.cancel');
        } else {
            if ($canDo->get('core.edit')) {
                ToolbarHelper::apply('pcmapping.apply');
                ToolbarHelper::save('pcmapping.save');
            }

            ToolbarHelper::cancel('pcmapping.cancel', 'JTOOLBAR_CLOSE');
        }
    }
}
