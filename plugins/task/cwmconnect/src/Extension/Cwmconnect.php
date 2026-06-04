<?php

/**
 * @package    Cwmconnect.Plugin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Plugin\Task\Cwmconnect\Extension;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ConfigurationException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncRunner;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Scheduled-task plugin that runs one Planning Center sync pass (campus +
 * people + office lists) via the shared {@see SyncRunner} — the same code the
 * admin Control Panel "Sync now" button uses. Lets a site keep the directory
 * fresh on a cron without anyone opening the admin, and runs headless from the
 * CLI via `php cli/joomla.php scheduler:run`.
 *
 * @since  __DEPLOY_VERSION__
 */
final class Cwmconnect extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    /**
     * The routines this plugin advertises to the Scheduler.
     *
     * @var    array<string, array{langConstPrefix: string}>
     * @since  __DEPLOY_VERSION__
     */
    private const TASKS_MAP = [
        'cwmconnect.sync' => [
            'langConstPrefix' => 'PLG_TASK_CWMCONNECT_SYNC',
        ],
    ];

    /**
     * Autoload the plugin's language file.
     *
     * @var    boolean
     * @since  __DEPLOY_VERSION__
     */
    protected $autoloadLanguage = true;

    /**
     * @inheritDoc
     *
     * @return  array<string, string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask'     => 'runSync',
        ];
    }

    /**
     * Run the Planning Center sync when our routine fires.
     *
     * @param   ExecuteTaskEvent  $event  The onExecuteTask event.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function runSync(ExecuteTaskEvent $event): void
    {
        if (!\array_key_exists($event->getRoutineId(), self::TASKS_MAP)) {
            return;
        }

        $this->startRoutine($event);

        try {
            $report = SyncRunner::create()->runFull();
        } catch (ConfigurationException $e) {
            $this->logTask($e->getMessage(), 'error');
            $this->endRoutine($event, Status::KNOCKOUT);

            return;
        } catch (\Throwable $e) {
            $this->logTask($e->getMessage(), 'error');
            $this->endRoutine($event, Status::KNOCKOUT);

            return;
        }

        $summary = $report->toArray();

        $this->logTask(\sprintf(
            'CWM Connect sync: seen %d, added %d, updated %d, deleted %d, errors %d.',
            (int) ($summary['seen'] ?? 0),
            (int) ($summary['added'] ?? 0),
            (int) ($summary['updated'] ?? 0),
            (int) ($summary['deleted'] ?? 0),
            (int) ($summary['errorCount'] ?? 0),
        ));

        $this->endRoutine($event, $report->success() ? Status::OK : Status::KNOCKOUT);
    }
}
