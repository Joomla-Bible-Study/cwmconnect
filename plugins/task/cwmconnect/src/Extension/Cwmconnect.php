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
use Joomla\CMS\Application\ConsoleApplication;
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
    /**
     * Seconds of avatar fetching per slice. Small enough that the budget below
     * is honoured closely rather than overshot by most of a slice.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const PHOTO_SLICE_SECONDS = 5.0;

    /** @since __DEPLOY_VERSION__ */
    private const PHOTO_BUDGET_CLI = 300.0;

    /** @since __DEPLOY_VERSION__ */
    private const PHOTO_BUDGET_WEB = 20.0;

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

        // Photos are excluded here for the same reason the Control Panel
        // excludes them: they were ~400 of the 415 seconds a first import took.
        // That matters more for a scheduled run than it looks. Joomla's lazy
        // scheduler is enabled by default, and it executes tasks inside a web
        // request — a background fetch fired from a visitor's page, so it does
        // not delay their browsing, but it is bound by max_execution_time and
        // occupies a PHP worker for the duration. A seven-minute routine is
        // killed there long before it finishes.
        try {
            $report = SyncRunner::create()->runFull(null, false);
        } catch (ConfigurationException $e) {
            $this->logTask($e->getMessage(), 'error');
            $this->endRoutine($event, Status::KNOCKOUT);

            return;
        } catch (\Throwable $e) {
            $this->logTask($e->getMessage(), 'error');
            $this->endRoutine($event, Status::KNOCKOUT);

            return;
        }

        // Fetch what avatars fit in the remaining budget. Nothing is lost by
        // stopping early: what to download is decided by comparing Planning
        // Center's avatar URL against the stored hash, so the next run simply
        // picks up where this one left off without any cursor to persist.
        $photos = $this->fetchPhotosWithinBudget();

        $summary = $report->toArray();

        $this->logTask(\sprintf(
            'CWM Connect sync: seen %d, added %d, updated %d, deleted %d, errors %d. '
            . 'Photos: %d fetched, %s.',
            (int) ($summary['seen'] ?? 0),
            (int) ($summary['added'] ?? 0),
            (int) ($summary['updated'] ?? 0),
            (int) ($summary['deleted'] ?? 0),
            (int) ($summary['errorCount'] ?? 0),
            $photos['downloaded'],
            $photos['done'] ? 'all up to date' : 'more remain for the next run',
        ));

        $this->endRoutine($event, $report->success() ? Status::OK : Status::KNOCKOUT);
    }

    /**
     * Walk avatar slices until they are done or the budget runs out.
     *
     * Nothing is lost by stopping early. What to download is decided by
     * comparing Planning Center's avatar URL against the stored hash, so the
     * next run resumes where this one stopped without any cursor to persist —
     * and under the lazy scheduler there is a next run every few minutes.
     *
     * @return  array{downloaded: int, done: bool}
     *
     * @since   __DEPLOY_VERSION__
     */
    private function fetchPhotosWithinBudget(): array
    {
        $started    = microtime(true);
        $budget     = $this->photoBudget();
        $cursor     = null;
        $downloaded = 0;
        $complete   = false;

        while (microtime(true) - $started < $budget) {
            try {
                $slice  = SyncRunner::create()->runPhotoPass(self::PHOTO_SLICE_SECONDS, $cursor);
                $cursor = $slice['nextUrl'];
                $downloaded += (int) ($slice['report']->toArray()['photosDownloaded'] ?? 0);
            } catch (\Throwable $e) {
                // The members are already committed; a photo failure must not
                // fail the task, or a working sync reports as broken.
                $this->logTask('CWM Connect photo pass stopped: ' . $e->getMessage(), 'warning');

                return ['downloaded' => $downloaded, 'done' => false];
            }

            if ($cursor === null) {
                // A null cursor means the last page was reached, not that the
                // loop was skipped — the distinction matters, because with no
                // budget left the loop never runs and $cursor is null having
                // fetched nothing.
                $complete = true;
                break;
            }
        }

        return ['downloaded' => $downloaded, 'done' => $complete];
    }

    /**
     * How long the photo phase may run, in seconds.
     *
     * From the CLI there is no request to outlive, so a first import can make
     * real progress in one go. In a web request the ceiling is whatever is left
     * of max_execution_time — measured from the start of the request, since the
     * member sync has already spent part of it — less a slice's worth of
     * headroom so the budget expires before PHP does.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function photoBudget(): float
    {
        if ($this->getApplication() instanceof ConsoleApplication) {
            return self::PHOTO_BUDGET_CLI;
        }

        $limit = (int) ini_get('max_execution_time');

        if ($limit <= 0) {
            // Unlimited, which is normal under php-fpm. The web ceiling still
            // applies: this is a shared worker, not a dedicated process.
            return self::PHOTO_BUDGET_WEB;
        }

        $elapsed   = microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        $remaining = $limit - $elapsed - self::PHOTO_SLICE_SECONDS;

        return max(0.0, min(self::PHOTO_BUDGET_WEB, $remaining));
    }
}
