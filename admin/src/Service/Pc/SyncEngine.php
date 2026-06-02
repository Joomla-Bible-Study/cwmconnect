<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\PcException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Orchestrates one Planning Center sync pass: paginated fetch → per-row
 * map + upsert → sweep step → report.
 *
 * Spec mapping:
 *  - §5.3 Per-person sync: each PC person → PersonMapper → repo upsert
 *  - §5.4 Sweep step:      after the page walk, archive any local rows whose
 *                          pc_person_id wasn't seen this run
 *  - §5.5 Audit:           caller (controller / scheduled task) logs the
 *                          returned {@see SyncReport} to com_actionlogs.
 *                          The engine itself only emits PSR-3 info/error via
 *                          the injected logger.
 *
 * Phase C scope: people + status filter only.
 * Phase D extends this with custom-field writes:
 *  - `field_data` + `field_data.field_definition` requested via `?include=`
 *  - {@see PersonMapper::extractFieldData()} pulls the (pc_field_id, value)
 *    pairs off each person
 *  - {@see FieldMapRepositoryInterface} resolves each pc_field_id to a
 *    Joomla custom-field id (admin-managed mapping table)
 *  - {@see CustomFieldWriterInterface} performs the actual write
 *
 * Phase E adds avatar caching:
 *  - {@see PersonMapper::extractAvatarUrl()} pulls the `avatar` URL
 *  - {@see PhotoCacheInterface} downloads + caches under
 *    `media/com_cwmconnect/photos/{pc_person_id}.<ext>` when the URL hash
 *    differs from the stored `image_hash`
 *  - The new (relative path, hash) is written back via
 *    {@see MemberRepositoryInterface::updateImageByPcPersonId()}.
 *
 * Phase H adds identity-binding pair attempts (spec §8.2 trigger #1):
 *  - After each per-person upsert, when the row is unpaired and the PC
 *    person has an email, {@see MemberPairingInterface} looks up the
 *    matching unblocked Joomla user and binds `user_id`. Ambiguous and
 *    no-match emails are silently skipped — there's no error to log.
 *
 * Household + campus FK resolution remain deferred to later phases.
 *
 * @since  __DEPLOY_VERSION__
 */
final class SyncEngine
{
    /**
     * Max pages we'll walk in a single run. Defensive guard so a runaway
     * `links.next` chain (PC bug / corrupted response) doesn't loop forever.
     * For a ~1000-person org this is 10x headroom at the default per_page=100.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const MAX_PAGES = 200;

    /**
     * Includes we pass to PC. `field_data` + `field_data.field_definition`
     * land in Phase D so {@see PersonMapper::extractFieldData()} has the
     * full FieldDatum + its FieldDefinition relationship in `included`.
     * Households / primary_campus FK resolution still defers to a later
     * phase but stay requested so the response shape is stable.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const PEOPLE_INCLUDES = 'emails,phone_numbers,addresses,households,primary_campus,field_data,field_data.field_definition';

    /**
     * Per-run cache for the PC→Joomla field mapping table. Loaded lazily on
     * the first person that carries any field_data so a sync with no
     * mapped fields never pays the lookup cost.
     *
     * @var    array<int, array{id: int, pc_field_id: int, pc_field_slug: string, pc_field_name: string, joomla_field_id: int}>|null
     * @since  __DEPLOY_VERSION__
     */
    private ?array $fieldMapCache = null;

    /**
     * Constructor.
     *
     * @param   Client                              $client          Authenticated PC client.
     * @param   MemberRepositoryInterface           $repository      Member persistence.
     * @param   PersonMapper                        $mapper          Pure PC→row mapper.
     * @param   FieldMapRepositoryInterface|null    $fieldMapRepo    Phase D: PC→Joomla
     *                                                                 custom-field mappings.
     *                                                                 Null = custom-field
     *                                                                 sync is skipped this
     *                                                                 run (Phase C parity).
     * @param   CustomFieldWriterInterface|null     $fieldWriter     Phase D: writer for
     *                                                                 custom-field values.
     *                                                                 Required when
     *                                                                 `$fieldMapRepo` is
     *                                                                 supplied; null
     *                                                                 otherwise.
     * @param   LoggerInterface                     $logger          Optional PSR-3 logger.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly Client $client,
        private readonly MemberRepositoryInterface $repository,
        private readonly PersonMapper $mapper,
        private readonly ?FieldMapRepositoryInterface $fieldMapRepo = null,
        private readonly ?CustomFieldWriterInterface $fieldWriter = null,
        private readonly ?PhotoCacheInterface $photoCache = null,
        private readonly ?MemberPairingInterface $pairing = null,
        private readonly ?HouseholdRepositoryInterface $households = null,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Run one sync pass. Always returns a report — failures are captured
     * per-person rather than thrown out, so a single bad payload doesn't
     * abort the whole run (spec §5.5).
     *
     * Auth / config / transport failures encountered while *starting* the
     * walk (e.g. token rejected before the first page lands) do throw,
     * because they mean "the run never began."
     *
     * @param   list<string>   $membershipStatuses  Values to pass to PC via
     *                                               `where[membership]`. Empty
     *                                               list = no filter (include
     *                                               all statuses; not
     *                                               recommended).
     * @param   \Closure|null  $onProgress          Called after each PC page
     *                                               with (int $pagesCompleted,
     *                                               int $totalSeen, string
     *                                               $phase). Null = no
     *                                               progress reporting.
     *
     * @return  SyncReport
     *
     * @throws  PcException  Only for fatal start-up failures (auth/transport);
     *                       per-person failures land in the report.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function run(array $membershipStatuses = [], ?\Closure $onProgress = null): SyncReport
    {
        $report      = new SyncReport();
        $seenIds     = [];
        $nextUrl     = null;
        $pagesWalked = 0;

        $this->logger->info('PC sync started.', ['statuses' => $membershipStatuses]);

        do {
            $pagesWalked++;

            if ($pagesWalked > self::MAX_PAGES) {
                $this->logger->warning('PC sync hit MAX_PAGES guard.', ['pages' => $pagesWalked]);
                $report->recordError(null, \sprintf('Pagination cap reached at %d pages.', self::MAX_PAGES));
                break;
            }

            $page = $nextUrl === null
                ? $this->fetchFirstPage($membershipStatuses)
                : $this->client->getJsonAbsolute($nextUrl);

            $data     = \is_array($page['data'] ?? null) ? $page['data'] : [];
            $included = \is_array($page['included'] ?? null) ? $page['included'] : [];

            $filterLocally = \count($membershipStatuses) > 1;

            foreach ($data as $person) {
                if (!\is_array($person)) {
                    $report->recordError(null, 'Skipping non-array entry in PC response data.');
                    continue;
                }

                if ($filterLocally) {
                    $personMembership = (string) ($person['attributes']['membership'] ?? '');

                    if (!\in_array($personMembership, $membershipStatuses, true)) {
                        continue;
                    }
                }

                $report->seen++;

                $pcPersonId = isset($person['id']) ? (int) $person['id'] : null;

                try {
                    $attrs = $this->mapper->map($person, $included);

                    if ($this->households !== null) {
                        $funitid = $this->linkHousehold($person, $included, $report);

                        if ($funitid !== null) {
                            $attrs['funitid'] = $funitid;
                            $report->householdsLinked++;
                        }
                    }

                    $outcome = $this->repository->upsertByPcPersonId($attrs);

                    match ($outcome) {
                        UpsertOutcome::Added      => $report->added++,
                        UpsertOutcome::Updated    => $report->updated++,
                        UpsertOutcome::Unarchived => $report->unarchived++,
                    };

                    if ($pcPersonId !== null && $pcPersonId > 0) {
                        $seenIds[] = $pcPersonId;
                        $this->writeCustomFields($pcPersonId, $person, $included, $report);
                        $this->cacheAvatar($pcPersonId, $person, $report);
                        $this->tryPairByEmail($pcPersonId, $attrs, $report);
                    }
                } catch (\Throwable $e) {
                    $report->recordError($pcPersonId, $e->getMessage());
                    $this->logger->error('PC sync error on person.', [
                        'pcPersonId' => $pcPersonId,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            if ($onProgress !== null) {
                $onProgress($pagesWalked, $report->seen, 'fetching');
            }

            $nextUrl = $this->extractNextLink($page);
        } while ($nextUrl !== null);

        if ($onProgress !== null) {
            $onProgress($pagesWalked, $report->seen, 'sweeping');
        }

        try {
            $report->archived = $this->repository->archiveMissingPcPersonIds(
                array_values(array_unique($seenIds)),
            );
        } catch (\Throwable $e) {
            $report->recordError(null, 'Sweep step failed: ' . $e->getMessage());
            $this->logger->error('PC sync sweep failed.', ['error' => $e->getMessage()]);
        }

        $report->finish();

        $this->logger->info('PC sync finished.', $report->toArray());

        return $report;
    }

    /**
     * Resolve the person's PC household to a local family-unit id, upserting
     * the family-unit row on the way. Returns null when the person has no
     * household (or its resource wasn't included on this page). Failures are
     * recorded on the report rather than thrown — a bad household must not
     * abort the member sync.
     *
     * @param   array<string, mixed>             $person    Raw PC Person row.
     * @param   array<int, array<string, mixed>> $included  JSON:API included.
     * @param   SyncReport                       $report    Mutated in-place.
     *
     * @return  int|null  Local `#__cwmconnect_familyunit.id`, or null.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function linkHousehold(array $person, array $included, SyncReport $report): ?int
    {
        try {
            $mapped = $this->mapper->extractHousehold($person, $included);

            if ($mapped === null) {
                return null;
            }

            return $this->households->upsertByPcHouseholdId($mapped);
        } catch (\Throwable $e) {
            $report->recordError(
                isset($person['id']) ? (int) $person['id'] : null,
                'Household link failed: ' . $e->getMessage(),
            );

            return null;
        }
    }

    /**
     * Phase D: per-person custom-field write step. No-op when the engine
     * was constructed without a mapping repo + writer (Phase C parity).
     *
     * Mapping-table lookup is cached for the duration of the run: a single
     * org typically has <50 PC custom fields, so loading the full mapping
     * once and reusing it across pages is cheaper than re-querying per
     * person. Per-field errors don't abort the run — they're appended to
     * the report.
     *
     * @param   int                                $pcPersonId
     * @param   array<string, mixed>               $person      Raw PC Person row.
     * @param   array<int, array<string, mixed>>   $included    JSON:API included.
     * @param   SyncReport                         $report      Mutated in-place.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function writeCustomFields(int $pcPersonId, array $person, array $included, SyncReport $report): void
    {
        if ($this->fieldMapRepo === null || $this->fieldWriter === null) {
            return;
        }

        $extracted = $this->mapper->extractFieldData($person, $included);

        if ($extracted === []) {
            return;
        }

        $this->fieldMapCache ??= $this->fieldMapRepo->allKeyedByPcFieldId();

        if ($this->fieldMapCache === []) {
            return;
        }

        $memberId = $this->repository->findIdByPcPersonId($pcPersonId);

        if ($memberId === null) {
            $report->recordError($pcPersonId, 'Custom-field write skipped: local member id not found after upsert.');

            return;
        }

        foreach ($extracted as $datum) {
            $mapping = $this->fieldMapCache[$datum['pc_field_id']] ?? null;

            if ($mapping === null) {
                continue;
            }

            try {
                if ($this->fieldWriter->setFieldValue($memberId, $mapping['joomla_field_id'], $datum['value'])) {
                    $report->customFieldsWritten++;
                }
            } catch (\Throwable $e) {
                $report->recordError($pcPersonId, \sprintf(
                    'Custom-field write failed (pc_field_id=%d): %s',
                    $datum['pc_field_id'],
                    $e->getMessage(),
                ));
            }
        }
    }

    /**
     * Phase E: per-person avatar cache step. No-op when the engine was
     * constructed without a photo cache (Phase C/D parity).
     *
     * Decision matrix lives in {@see PhotoCacheInterface::cache()}; this
     * method just plumbs the URL + stored hash through and persists the
     * cache's return value. Cache exceptions are captured per-person so
     * a single bad URL doesn't abort the run.
     *
     * @param   int                              $pcPersonId
     * @param   array<string, mixed>             $person
     * @param   SyncReport                       $report
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function cacheAvatar(int $pcPersonId, array $person, SyncReport $report): void
    {
        if ($this->photoCache === null) {
            return;
        }

        $avatarUrl = $this->mapper->extractAvatarUrl($person);

        if ($avatarUrl === null) {
            return;
        }

        try {
            $currentHash = $this->repository->findImageHashByPcPersonId($pcPersonId);
            $result      = $this->photoCache->cache($pcPersonId, $avatarUrl, $currentHash);

            if ($result === null) {
                return;
            }

            if ($result->downloaded) {
                $this->repository->updateImageByPcPersonId(
                    $pcPersonId,
                    $result->relativePath,
                    $result->hash,
                );
                $report->photosDownloaded++;
            } else {
                $report->photosUnchanged++;
            }
        } catch (\Throwable $e) {
            $report->recordError($pcPersonId, 'Photo cache failed: ' . $e->getMessage());
            $this->logger->error('PC sync photo cache error.', [
                'pcPersonId' => $pcPersonId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Phase H: per-person identity-bind step (spec §8.2 trigger #1). No-op
     * when the engine was constructed without a pairing service (Phase
     * C/D/E parity), when the PC person has no email, or when no
     * unblocked Joomla user matches that email.
     *
     * The pair call is a guarded UPDATE — already-paired rows are left
     * alone, so this runs idempotently on every sync without ever
     * overwriting an admin's manual binding.
     *
     * @param   int                   $pcPersonId
     * @param   array<string, mixed>  $attrs   Mapped attrs returned by
     *                                          PersonMapper::map(); read for
     *                                          `email_to`.
     * @param   SyncReport            $report  Mutated in-place.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function tryPairByEmail(int $pcPersonId, array $attrs, SyncReport $report): void
    {
        if ($this->pairing === null) {
            return;
        }

        $email = isset($attrs['email_to']) && \is_string($attrs['email_to']) ? trim($attrs['email_to']) : '';

        if ($email === '') {
            return;
        }

        try {
            $memberId = $this->repository->findIdByPcPersonId($pcPersonId);

            if ($memberId === null) {
                return;
            }

            $userId = $this->pairing->findJoomlaUserIdByEmail($email);

            if ($userId === null) {
                return;
            }

            if ($this->pairing->pairMemberToUser($memberId, $userId)) {
                $report->paired++;
            }
        } catch (\Throwable $e) {
            $report->recordError($pcPersonId, 'Pair-by-email failed: ' . $e->getMessage());
            $this->logger->error('PC sync pair-by-email error.', [
                'pcPersonId' => $pcPersonId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Issue the first PC `/people/v2/people` GET with filter + includes.
     *
     * @param   list<string>  $membershipStatuses
     *
     * @return  array<string, mixed>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function fetchFirstPage(array $membershipStatuses): array
    {
        $query = [
            'include'  => self::PEOPLE_INCLUDES,
            'per_page' => '100',
        ];

        if (\count($membershipStatuses) === 1) {
            $query['where[membership]'] = $membershipStatuses[0];
        }

        return $this->client->getJson('/people/v2/people', $query);
    }

    /**
     * Pull the `links.next` URL from a PC paginated response, or null when
     * there is no next page.
     *
     * @param   array<string, mixed>  $page
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function extractNextLink(array $page): ?string
    {
        $links = $page['links'] ?? null;

        if (!\is_array($links)) {
            return null;
        }

        $next = $links['next'] ?? null;

        return \is_string($next) && $next !== '' ? $next : null;
    }
}
