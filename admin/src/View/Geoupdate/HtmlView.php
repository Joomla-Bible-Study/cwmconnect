<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\View\Geoupdate;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Administrator\Model\GeoupdateModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View used by the geocoding worker — renders a progress bar and
 * auto-resubmits the form via JS until the queue drains.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Whether more slices remain.
     *
     * @var bool
     * @since 2.0.0
     */
    protected bool $more = true;

    /**
     * Percentage of work completed (0-100).
     *
     * @var int
     * @since 2.0.0
     */
    protected int $percentage = 0;

    /**
     * Worker state passed in from the controller via input.
     *
     * @var string
     * @since 2.0.0
     */
    protected string $state = '';

    /**
     * Display the view.
     *
     * @param   string|null  $tpl  Template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app         = Factory::getApplication();
        $this->state = (string) $app->getInput()->get('scanstate', '');

        /** @var GeoupdateModel $model */
        $model    = $this->getModel();
        $loaded   = $model->loadStack();
        $more     = true;
        $percent  = 0;

        if ($this->state !== '' && $this->state !== 'done' && $loaded) {
            if ($model->totalMembers > 0) {
                $percent = (int) min(99, floor(($model->doneMembers / $model->totalMembers) * 100) + 1);
            }
        } elseif ($loaded || $this->state === 'done') {
            $percent = 100;
            $more    = false;
        }

        $this->more       = $more;
        $this->percentage = $percent;

        ToolbarHelper::title(Text::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE'), 'churchdirectory');

        if ($more) {
            $app->getDocument()->addScriptDeclaration(
                "document.addEventListener('DOMContentLoaded', function () { document.forms.adminForm.submit(); });"
            );
        }

        $this->setLayout('default');

        parent::display($tpl);
    }
}
