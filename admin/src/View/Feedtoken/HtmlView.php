<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Feedtoken;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Edit view for a single feed token.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /** @since __DEPLOY_VERSION__ */
    protected ?Form $form = null;

    /** @since __DEPLOY_VERSION__ */
    protected ?object $item = null;

    /**
     * @param   string|null  $tpl  Template name.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $model      = $this->getModel();
        $this->form = $model->getForm();
        $this->item = $model->getItem();

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew = empty($this->item->id);

        ToolbarHelper::title(
            $isNew
                ? Text::_('COM_CWMCONNECT_MANAGER_FEEDTOKEN_NEW')
                : Text::_('COM_CWMCONNECT_MANAGER_FEEDTOKEN_EDIT'),
            'key',
        );

        if ($isNew) {
            ToolbarHelper::save('feedtoken.save');
        }

        ToolbarHelper::cancel('feedtoken.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
