<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Site\View\Home;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Site\Helper\RenderHelper;
use CWM\Component\Churchdirectory\Site\Model\HomeModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

/**
 * HTML view for the directory home page.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var \Joomla\Registry\Registry|null */
    protected mixed $state = null;

    /** @var array<int, object> */
    protected array $items = [];

    /** @var Registry */
    protected Registry $params;

    /** @var User|null */
    protected ?User $user = null;

    /** @var string Base64-encoded return URL for post-login redirects. */
    protected string $return = '';

    /** @var RenderHelper */
    protected RenderHelper $renderHelper;

    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var HomeModel $model */
        $model        = $this->getModel();
        $this->state  = $model->getState();
        $this->items  = $model->getItems() ?: [];
        $this->return = $model->getReturnPage();
        $this->user   = Factory::getApplication()->getIdentity();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $params = ComponentHelper::getParams('com_churchdirectory');
        $params->merge($this->state->get('params'));

        // Defaults for the search-field render helper — overridden by menu.
        $defaults = new Registry();
        $defaults->set('opensearch', '1');
        $defaults->set('size-lbl', '12');
        $defaults->set('show_button', '1');
        $defaults->set('button_pos', 'right');
        $params->merge($defaults);

        $this->params       = $params;
        $this->renderHelper = new RenderHelper();

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Set page title and meta from the active menu / global config.
     *
     * @since  2.0.0
     */
    protected function prepareDocument(): void
    {
        $app  = Factory::getApplication();
        $menu = $app->getMenu()?->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->def('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_CHURCHDIRECTORY_DEFAULT_PAGE_TITLE'));
        }

        $title = (string) $this->params->get('page_title', '');

        if ($title === '') {
            $title = $app->get('sitename');
        } elseif ((int) $app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ((int) $app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->getDocument()->setMetaData('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }
    }
}