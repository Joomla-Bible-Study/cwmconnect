<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\DatabaseMemberPairing;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ConfigurationException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Single source of truth for "run one Planning Center sync pass". Builds the
 * fully-wired {@see SyncEngine} from component params and orchestrates the three
 * steps the admin Control Panel performs — campus refresh, people sync, office
 * list tagging — returning the {@see SyncReport}.
 *
 * Self-bootstrapping (resolves the database from the global container and reads
 * `com_cwmconnect` params) so it can run from any entry point that is not the
 * component's own DI scope: the admin AJAX controller, the `cwmconnect:sync`
 * CLI command, and the scheduled-task plugin all call {@see runFull()}.
 *
 * @since  __DEPLOY_VERSION__
 */
final class SyncRunner
{
    /**
     * @param   DatabaseInterface  $db      Joomla database.
     * @param   Registry           $params  `com_cwmconnect` component params.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly Registry $params,
    ) {}

    /**
     * Build a runner from the global container + component params.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function create(): self
    {
        return new self(
            Factory::getContainer()->get(DatabaseInterface::class),
            ComponentHelper::getParams('com_cwmconnect'),
        );
    }

    /**
     * Run one full sync pass: campus refresh (best-effort) → people sync →
     * office-list tagging (best-effort). The people sync is the point; the
     * auxiliary steps never abort it.
     *
     * @param   \Closure|null  $onProgress  Called after each PC page with
     *                                       (pagesCompleted, totalSeen, phase).
     *
     * @return  SyncReport  The people-sync report.
     *
     * @throws  ConfigurationException  When PC is not configured (empty token).
     *
     * @since   __DEPLOY_VERSION__
     */
    public function runFull(?\Closure $onProgress = null): SyncReport
    {
        // Auxiliary: refresh campus data (cover church name/address) from PC.
        try {
            $this->buildCampusSync()->run();
        } catch (\Throwable) {
            // Campus sync is best-effort; the people sync is the point.
        }

        $report = $this->buildEngine()->run($this->membershipStatuses(), $onProgress);

        // Tag members with their church office from the configured PC office
        // lists (Elders, Deacons…). Best-effort.
        try {
            $officeLists = $this->officeLists();

            if ($officeLists !== []) {
                new OfficeListSync($this->buildClient(), $this->db)->run($officeLists);
            }
        } catch (\Throwable) {
            // Office-list tagging must not abort the people sync.
        }

        return $report;
    }

    /**
     * The membership-status filter from component params, normalised to a list.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function membershipStatuses(): array
    {
        $raw = $this->params->get('pc_membership_statuses', []);

        if (\is_array($raw)) {
            return array_values(array_filter($raw));
        }

        $out = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) $raw) ?: [] as $line) {
            if (($line = trim($line)) !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * Build the authenticated PC client from component params.
     *
     * @return  Client
     *
     * @throws  ConfigurationException  When the personal access token is empty.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function buildClient(): Client
    {
        $token   = (string) $this->params->get('pc_personal_access_token', '');
        $appId   = (string) $this->params->get('pc_application_id', '');
        $baseUrl = (string) $this->params->get('pc_api_base_url', Client::DEFAULT_BASE_URL);

        if ($token === '') {
            throw new ConfigurationException(
                'Planning Center is not configured: personal access token is empty.',
            );
        }

        return new Client(
            http: HttpFactory::getHttp(),
            personalAccessToken: $token,
            applicationId: $appId,
            baseUrl: $baseUrl !== '' ? $baseUrl : Client::DEFAULT_BASE_URL,
        );
    }

    /**
     * Build the fully-wired people-sync engine.
     *
     * @return  SyncEngine
     *
     * @throws  ConfigurationException  When PC is not configured.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function buildEngine(): SyncEngine
    {
        // PC field-definition slugs are admin-configurable (custom fields differ
        // per org); a blank value disables that role.
        $roleFieldSlugs = [
            'board'          => (string) $this->params->get('pc_field_board', PersonMapper::DEFAULT_ROLE_FIELDS['board']),
            'positions'      => (string) $this->params->get('pc_field_positions', PersonMapper::DEFAULT_ROLE_FIELDS['positions']),
            'ministry_teams' => (string) $this->params->get('pc_field_ministry_teams', PersonMapper::DEFAULT_ROLE_FIELDS['ministry_teams']),
            'leader'         => (string) $this->params->get('pc_field_leader', PersonMapper::DEFAULT_ROLE_FIELDS['leader']),
        ];

        return new SyncEngine(
            client: $this->buildClient(),
            repository: new DatabaseMemberRepository($this->db),
            mapper: new PersonMapper($roleFieldSlugs, (int) $this->params->get('member_access', 2)),
            fieldMapRepo: new DatabaseFieldMapRepository($this->db),
            fieldWriter: new FieldsHelperWriter(),
            photoCache: new MediaPhotoCache(
                http: HttpFactory::getHttp(),
                cacheRoot: JPATH_ROOT . '/media/com_cwmconnect/photos',
            ),
            pairing: new DatabaseMemberPairing($this->db),
            households: new DatabaseHouseholdRepository($this->db),
            householdPhotoCache: new MediaPhotoCache(
                http: HttpFactory::getHttp(),
                cacheRoot: JPATH_ROOT . '/media/com_cwmconnect/photos/households',
            ),
        );
    }

    /**
     * Build the campus-sync service.
     *
     * @return  CampusSync
     *
     * @throws  ConfigurationException  When PC is not configured.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function buildCampusSync(): CampusSync
    {
        return new CampusSync(
            client: $this->buildClient(),
            mapper: new CampusMapper(),
            repository: new DatabaseCampusRepository($this->db),
        );
    }

    /**
     * Parse the configured PC office-list → role mapping (the `pdf_officer_lists`
     * subform) into a `list id => role label` array. Rows missing a list id or
     * role are dropped.
     *
     * @return  array<int, string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function officeLists(): array
    {
        $out = [];

        foreach ((array) $this->params->get('pdf_officer_lists', []) as $row) {
            $row    = (array) $row;
            $listId = (int) ($row['list_id'] ?? 0);
            $role   = trim((string) ($row['role'] ?? ''));

            if ($listId > 0 && $role !== '') {
                $out[$listId] = $role;
            }
        }

        return $out;
    }
}
