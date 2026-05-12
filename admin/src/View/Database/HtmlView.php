<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Administrator\View\Database;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Administrator\Model\DatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\CanDo;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Schema\ChangeSet;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Database tools view.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var ChangeSet|null */
    protected ?ChangeSet $changeSet = null;

    /** @var array<int, object> */
    protected array $errors = [];

    /** @var array{ok: array, error: array, skipped: array} */
    protected array $results = ['ok' => [], 'error' => [], 'skipped' => []];

    /** @var string */
    protected string $schemaVersion = '';

    /** @var string */
    protected string $updateVersion = '';

    /** @var string|null */
    protected ?string $filterParams = null;

    /** @var int */
    protected int $errorCount = 0;

    /** @var string */
    protected string $manifestVersion = '';

    /** @var CanDo|null */
    protected ?CanDo $canDo = null;

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
        Factory::getApplication()->getLanguage()->load('com_installer');

        /** @var DatabaseModel $model */
        $model                 = $this->getModel();
        $this->changeSet       = $model->getChangeSet();
        $this->errors          = $this->changeSet->check();
        $this->results         = $this->changeSet->getStatus();
        $this->schemaVersion   = (string) ($model->getSchemaVersion() ?? Text::_('JNONE'));
        $this->updateVersion   = (string) ($model->getUpdateVersion() ?? Text::_('JNONE'));
        $this->filterParams    = $model->getDefaultTextFilters();
        $this->manifestVersion = $model->getCompVersion();
        $this->canDo           = ContentHelper::getActions('com_cwmconnect');

        $this->errorCount = \count($this->errors);

        if (strncmp($this->schemaVersion, $this->manifestVersion, 5) !== 0) {
            $this->errorCount++;
        }

        if (!$this->filterParams) {
            $this->errorCount++;
        }

        if ($this->updateVersion !== $this->manifestVersion) {
            $this->errorCount++;
        }

        $this->setLayout('default');
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
        ToolbarHelper::title(Text::_('COM_CHURCHDIRECTORY_DATABASE'), 'puzzle install');
        ToolbarHelper::divider();

        if ($this->canDo && $this->canDo->get('core.admin')) {
            ToolbarHelper::custom(
                'database.fix',
                'refresh',
                'refresh',
                'COM_CHURCHDIRECTORY_DATABASE_FIX',
                false
            );
        }
    }
}
