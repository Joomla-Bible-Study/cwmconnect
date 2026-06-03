<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Controller;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\DatabaseMemberPairing;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\CampusMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\CampusSync;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client as PcClient;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseCampusRepository;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseFieldMapRepository;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseHouseholdRepository;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseMemberRepository;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\AuthenticationException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ConfigurationException as PcConfigurationException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\PcException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\FieldsHelperWriter;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MediaPhotoCache;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine as PcSyncEngine;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Control panel controller.
 *
 * In addition to the default view, exposes two AJAX-style task endpoints
 * that the Cpanel UI calls from JS to drive Planning Center operations
 * without a full page reload:
 *
 *  - `task=cpanel.pcTestConnection` — calls `Client::me()` to verify the
 *    configured PAT works. Returns JSON `{name, organisation}` on success.
 *  - `task=cpanel.pcSync` — runs one `SyncEngine` pass and returns the
 *    `SyncReport` as JSON.
 *
 * Both endpoints require `core.admin` on com_cwmconnect plus a valid
 * session token (CSRF). On failure they short-circuit to a JSON error
 * envelope so the JS client always receives a parseable response.
 *
 * @since  2.0.0
 */
class CpanelController extends BaseController
{
    /**
     * Default view for the control panel.
     *
     * @var    string
     * @since  2.0.0
     */
    protected $default_view = 'cpanel';

    /**
     * Display the control panel view.
     *
     * @param   bool   $cachable   If true, the view output will be cached.
     * @param   array  $urlparams  An array of safe URL parameters.
     *
     * @return  static  This object to support chaining.
     *
     * @since   2.0.0
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $this->input->set('view', 'cpanel');

        return parent::display($cachable, $urlparams);
    }

    /**
     * AJAX endpoint: probe Planning Center with the configured token by
     * calling `/people/v2/me`. Returns JSON success / error.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function pcTestConnection(): void
    {
        $this->assertAdminAjax();

        try {
            /** @var PcClient $client */
            $client = $this->createPcClient();
            $me     = $client->me();

            $this->sendJsonAndClose(
                new JsonResponse([
                    'attributes' => $me['attributes'] ?? [],
                    'id'         => $me['id'] ?? null,
                ], Text::_('COM_CWMCONNECT_PC_TEST_OK'), false),
            );
        } catch (PcConfigurationException $e) {
            $this->sendJsonAndClose(
                new JsonResponse(Text::_('COM_CWMCONNECT_PC_NOT_CONFIGURED'), 'warning', true),
            );
        } catch (AuthenticationException $e) {
            $this->sendJsonAndClose(
                new JsonResponse(Text::_('COM_CWMCONNECT_PC_AUTH_FAILED'), 'error', true),
            );
        } catch (\Throwable $e) {
            $this->sendJsonAndClose(
                new JsonResponse($e->getMessage(), 'error', true),
            );
        }
    }

    /**
     * AJAX endpoint: run one `SyncEngine` pass against PC, using the configured
     * membership-status filter. Returns the `SyncReport::toArray()` payload.
     *
     * While the sync runs, a progress callback writes intermediate state to a
     * cache file so the `pcSyncProgress` endpoint can serve it to the polling
     * JS client.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function pcSync(): void
    {
        $this->assertAdminAjax();

        // Release the session write-lock now that auth + CSRF have been checked.
        // The sync runs for 20-30s; without this the PHP session file stays
        // exclusively locked for the whole request and every concurrent
        // `pcSyncProgress` poll blocks until the sync ends — so the progress
        // spinner would never update mid-run. Nothing below writes to session.
        $this->app->getSession()->close();

        $progressFile = $this->progressFilePath();

        try {
            $params      = ComponentHelper::getParams('com_cwmconnect');
            $rawStatuses = $params->get('pc_membership_statuses', []);
            $statuses    = \is_array($rawStatuses)
                ? array_values(array_filter($rawStatuses))
                : $this->parseStatusList((string) $rawStatuses);

            $this->writeProgress($progressFile, 'starting', 0, 0);

            // Auxiliary: refresh campus data (cover church name/address) from
            // PC. Failures here must not abort the people sync.
            try {
                $this->createCampusSync()->run();
            } catch (\Throwable) {
                // Campus sync is best-effort; the people sync is the point.
            }

            /** @var PcSyncEngine $engine */
            $engine     = $this->createSyncEngine();
            $onProgress = function (int $pagesCompleted, int $totalSeen, string $phase) use ($progressFile): void {
                $this->writeProgress($progressFile, $phase, $pagesCompleted, $totalSeen);
            };

            $report = $engine->run($statuses, $onProgress);

            @unlink($progressFile);

            $this->logSyncResult($report->toArray(), $report->success());

            $this->sendJsonAndClose(
                new JsonResponse(
                    $report->toArray(),
                    Text::_($report->success() ? 'COM_CWMCONNECT_PC_SYNC_OK' : 'COM_CWMCONNECT_PC_SYNC_PARTIAL'),
                    !$report->success(),
                ),
            );
        } catch (PcConfigurationException $e) {
            @unlink($progressFile);
            $this->sendJsonAndClose(
                new JsonResponse(Text::_('COM_CWMCONNECT_PC_NOT_CONFIGURED'), 'warning', true),
            );
        } catch (\Throwable $e) {
            @unlink($progressFile);
            $this->sendJsonAndClose(
                new JsonResponse($e->getMessage(), 'error', true),
            );
        }
    }

    /**
     * AJAX endpoint: return the current sync progress from the cache file.
     * Called by the JS client on a ~1 s poll while `pcSync` is running.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function pcSyncProgress(): void
    {
        $this->assertAdminAjax();

        $file = $this->progressFilePath();

        if (!is_file($file)) {
            $this->sendJsonAndClose(
                new JsonResponse(['running' => false], '', false),
            );

            return;
        }

        $raw = @file_get_contents($file);

        if ($raw === false) {
            $this->sendJsonAndClose(
                new JsonResponse(['running' => false], '', false),
            );

            return;
        }

        $data = @json_decode($raw, true);

        if (!\is_array($data)) {
            $this->sendJsonAndClose(
                new JsonResponse(['running' => false], '', false),
            );

            return;
        }

        $data['running'] = true;

        $this->sendJsonAndClose(new JsonResponse($data, '', false));
    }

    /**
     * Enforce the access-control and CSRF guards both PC AJAX endpoints need.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function assertAdminAjax(): void
    {
        if (!$this->app->getIdentity()?->authorise('core.admin', 'com_cwmconnect')) {
            $this->sendJsonAndClose(
                new JsonResponse(Text::_('JERROR_ALERTNOAUTHOR'), 'error', true),
                403,
            );
        }

        $this->checkToken();
    }

    /**
     * Split a textarea value (one status per line, blank lines tolerated)
     * into a clean list. Phase C accepts a free-form text list; a future
     * phase may swap this for a dynamic dropdown that fetches from PC.
     *
     * @param   string  $raw
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function parseStatusList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        $out = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * Emit a JsonResponse and terminate. Joomla's standard pattern is
     * `echo new JsonResponse(...); jexit();` for AJAX task endpoints — this
     * helper centralises the convention so both endpoints stay consistent.
     *
     * @param   JsonResponse  $response
     * @param   int           $httpStatus
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    /**
     * Write a sync result to com_actionlogs.
     *
     * @param   array<string, mixed>  $data     SyncReport data array.
     * @param   bool                  $success  Whether the sync fully succeeded.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function logSyncResult(array $data, bool $success): void
    {
        // Detailed run summary + every per-person error go to a dedicated
        // Joomla log file (administrator/logs/com_cwmconnect.sync.php).
        $this->writeSyncLogFile($data);

        // The action log keeps only a concise audit record — the counts, not
        // the (potentially hundreds of) individual error lines, which would
        // bloat one #__action_logs row into an unreadable JSON blob.
        $summary = array_diff_key($data, ['errors' => null]);

        try {
            $factory = Factory::getApplication()->bootComponent('com_actionlogs')->getMVCFactory();

            /** @var ActionlogModel $model */
            $model = $factory->createModel('Actionlog', 'Administrator');
            $model->addLog(
                [$summary],
                $success ? 'COM_CWMCONNECT_ACTIONLOG_SYNC_OK' : 'COM_CWMCONNECT_ACTIONLOG_SYNC_PARTIAL',
                'com_cwmconnect.sync',
            );
        } catch (\Throwable) {
        }
    }

    /**
     * Write the sync run to a dedicated Joomla log file: one summary line plus
     * one line per recorded error. Keeps the full error detail out of the
     * action log while still persisting it for diagnosis.
     *
     * @param   array<string, mixed>  $data  The SyncReport::toArray() payload.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function writeSyncLogFile(array $data): void
    {
        try {
            Log::addLogger(
                ['text_file' => 'com_cwmconnect.sync.php'],
                Log::ALL,
                ['com_cwmconnect.sync'],
            );

            $errorCount = (int) ($data['errorCount'] ?? 0);

            Log::add(
                \sprintf(
                    'PC sync finished: seen=%d added=%d updated=%d deleted=%d '
                    . 'households=%d photos=%d fields=%d paired=%d errors=%d',
                    (int) ($data['seen'] ?? 0),
                    (int) ($data['added'] ?? 0),
                    (int) ($data['updated'] ?? 0),
                    (int) ($data['deleted'] ?? 0),
                    (int) ($data['householdsLinked'] ?? 0),
                    (int) ($data['photosDownloaded'] ?? 0),
                    (int) ($data['customFieldsWritten'] ?? 0),
                    (int) ($data['paired'] ?? 0),
                    $errorCount,
                ),
                $errorCount > 0 ? Log::WARNING : Log::INFO,
                'com_cwmconnect.sync',
            );

            foreach ($data['errors'] ?? [] as $error) {
                Log::add(
                    \sprintf(
                        'PC person %s: %s',
                        $error['pcPersonId'] ?? '-',
                        (string) ($error['message'] ?? ''),
                    ),
                    Log::ERROR,
                    'com_cwmconnect.sync',
                );
            }
        } catch (\Throwable) {
        }
    }

    /**
     * @return  PcClient
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createPcClient(): PcClient
    {
        $params  = ComponentHelper::getParams('com_cwmconnect');
        $token   = (string) $params->get('pc_personal_access_token', '');
        $appId   = (string) $params->get('pc_application_id', '');
        $baseUrl = (string) $params->get('pc_api_base_url', PcClient::DEFAULT_BASE_URL);

        if ($token === '') {
            throw new PcConfigurationException('Planning Center is not configured: personal access token is empty.');
        }

        return new PcClient(
            http: HttpFactory::getHttp(),
            personalAccessToken: $token,
            applicationId: $appId,
            baseUrl: $baseUrl !== '' ? $baseUrl : PcClient::DEFAULT_BASE_URL,
        );
    }

    /**
     * @return  PcSyncEngine
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createSyncEngine(): PcSyncEngine
    {
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $params = ComponentHelper::getParams('com_cwmconnect');

        // PC field-definition slugs for the directory-role fields are admin-
        // configurable (they are custom fields, so the slug differs per org); a
        // blank value disables that role.
        $roleFieldSlugs = [
            'board'          => (string) $params->get('pc_field_board', PersonMapper::DEFAULT_ROLE_FIELDS['board']),
            'positions'      => (string) $params->get('pc_field_positions', PersonMapper::DEFAULT_ROLE_FIELDS['positions']),
            'ministry_teams' => (string) $params->get('pc_field_ministry_teams', PersonMapper::DEFAULT_ROLE_FIELDS['ministry_teams']),
            'leader'         => (string) $params->get('pc_field_leader', PersonMapper::DEFAULT_ROLE_FIELDS['leader']),
        ];

        return new PcSyncEngine(
            client: $this->createPcClient(),
            repository: new DatabaseMemberRepository($db),
            mapper: new PersonMapper($roleFieldSlugs),
            fieldMapRepo: new DatabaseFieldMapRepository($db),
            fieldWriter: new FieldsHelperWriter(),
            photoCache: new MediaPhotoCache(
                http: HttpFactory::getHttp(),
                cacheRoot: JPATH_ROOT . '/media/com_cwmconnect/photos',
            ),
            pairing: new DatabaseMemberPairing($db),
            households: new DatabaseHouseholdRepository($db),
            householdPhotoCache: new MediaPhotoCache(
                http: HttpFactory::getHttp(),
                cacheRoot: JPATH_ROOT . '/media/com_cwmconnect/photos/households',
            ),
        );
    }

    /**
     * Build a campus sync (K.6) wired to the PC client + dirheader repository.
     *
     * @return  CampusSync
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createCampusSync(): CampusSync
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        return new CampusSync(
            client: $this->createPcClient(),
            mapper: new CampusMapper(),
            repository: new DatabaseCampusRepository($db),
        );
    }

    /**
     * Cache-file path for the current user's sync progress.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function progressFilePath(): string
    {
        $userId = (int) $this->app->getIdentity()?->id;

        return JPATH_ADMINISTRATOR . '/cache/com_cwmconnect_sync_' . $userId . '.json';
    }

    /**
     * Write a progress snapshot to the cache file.
     *
     * @param   string  $file            Absolute path.
     * @param   string  $phase           'starting', 'fetching', or 'sweeping'.
     * @param   int     $pagesCompleted  Pages processed so far.
     * @param   int     $totalSeen       People seen so far.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function writeProgress(string $file, string $phase, int $pagesCompleted, int $totalSeen): void
    {
        $payload = json_encode([
            'phase'          => $phase,
            'pagesCompleted' => $pagesCompleted,
            'totalSeen'      => $totalSeen,
        ], \JSON_THROW_ON_ERROR);

        @file_put_contents($file, $payload, \LOCK_EX);
    }

    private function sendJsonAndClose(JsonResponse $response, int $httpStatus = 200): void
    {
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->setHeader('status', (string) $httpStatus, true);
        $this->app->sendHeaders();

        echo $response;

        $this->app->close();
    }
}
