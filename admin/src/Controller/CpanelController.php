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
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client as PcClient;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseFieldMapRepository;
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
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function pcSync(): void
    {
        $this->assertAdminAjax();

        try {
            $params   = ComponentHelper::getParams('com_cwmconnect');
            $statuses = $this->parseStatusList((string) $params->get('pc_membership_statuses', ''));

            /** @var PcSyncEngine $engine */
            $engine = $this->createSyncEngine();
            $report = $engine->run($statuses);

            $this->logSyncResult($report->toArray(), $report->success());

            $this->sendJsonAndClose(
                new JsonResponse(
                    $report->toArray(),
                    Text::_($report->success() ? 'COM_CWMCONNECT_PC_SYNC_OK' : 'COM_CWMCONNECT_PC_SYNC_PARTIAL'),
                    !$report->success(),
                ),
            );
        } catch (PcConfigurationException $e) {
            $this->sendJsonAndClose(
                new JsonResponse(Text::_('COM_CWMCONNECT_PC_NOT_CONFIGURED'), 'warning', true),
            );
        } catch (\Throwable $e) {
            $this->sendJsonAndClose(
                new JsonResponse($e->getMessage(), 'error', true),
            );
        }
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
        try {
            $factory = Factory::getApplication()->bootComponent('com_actionlogs')->getMVCFactory();

            /** @var ActionlogModel $model */
            $model = $factory->createModel('Actionlog', 'Administrator');
            $model->addLog(
                [$data],
                $success ? 'COM_CWMCONNECT_ACTIONLOG_SYNC_OK' : 'COM_CWMCONNECT_ACTIONLOG_SYNC_PARTIAL',
                'com_cwmconnect.sync',
            );
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
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        return new PcSyncEngine(
            client: $this->createPcClient(),
            repository: new DatabaseMemberRepository($db),
            mapper: new PersonMapper(),
            fieldMapRepo: new DatabaseFieldMapRepository($db),
            fieldWriter: new FieldsHelperWriter(),
            photoCache: new MediaPhotoCache(
                http: HttpFactory::getHttp(),
                cacheRoot: JPATH_ROOT . '/media/com_cwmconnect/photos',
            ),
            pairing: new DatabaseMemberPairing($db),
        );
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
