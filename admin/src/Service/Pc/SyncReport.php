<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Outcome of a single sync run.
 *
 * Populated by {@see SyncEngine::run()} and emitted to action logs / returned
 * to the Cpanel UI as JSON. Per-row errors are captured (one entry per
 * offending PC person id) so a single bad payload doesn't abort the run —
 * see spec §5.5.
 *
 * @since  __DEPLOY_VERSION__
 */
final class SyncReport
{
    /**
     * Total PC people seen across all paginated responses, including ones
     * that subsequently errored.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $seen = 0;

    /**
     * Local rows newly inserted this run.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $added = 0;

    /**
     * Local rows updated in place this run.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $updated = 0;

    /**
     * Local rows archived by the sweep step (display_in_directory = 0).
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $archived = 0;

    /**
     * Rows previously archived that came back in this run's PC result and
     * were re-enabled. Tracked separately so the run summary surfaces
     * "people who returned."
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $unarchived = 0;

    /**
     * Phase D: count of `FieldsHelper::setFieldValue()` calls completed this
     * run. Includes both "value changed" and "value rewritten to same" — we
     * don't currently diff before writing.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $customFieldsWritten = 0;

    /**
     * Phase E: avatars actually downloaded this run (URL hash differed
     * from the stored value, or no hash was stored yet).
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $photosDownloaded = 0;

    /**
     * Phase E: avatars whose URL hash matched the stored value, so the
     * existing cache file was kept. Tracked separately so the run
     * summary distinguishes "we saved bandwidth" from "we wrote bytes."
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $photosUnchanged = 0;

    /**
     * Phase H: member rows newly paired to a Joomla user this run via the
     * email-match heuristic (spec §8.2 trigger #1). Members already paired,
     * unmatched emails, and ambiguous matches do NOT increment this — only
     * successful state changes from unpaired → paired count.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $paired = 0;

    /**
     * Per-person error list, one entry per failure.
     *
     * @var    list<array{pcPersonId: int|null, message: string}>
     * @since  __DEPLOY_VERSION__
     */
    public array $errors = [];

    /**
     * @var    \DateTimeImmutable
     * @since  __DEPLOY_VERSION__
     */
    public \DateTimeImmutable $startedAt;

    /**
     * @var    \DateTimeImmutable|null
     * @since  __DEPLOY_VERSION__
     */
    public ?\DateTimeImmutable $finishedAt = null;

    /**
     * Constructor.
     *
     * @param   \DateTimeImmutable|null  $startedAt  Start timestamp. Defaults to "now"
     *                                               so production callers don't need
     *                                               to wire a clock.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(?\DateTimeImmutable $startedAt = null)
    {
        $this->startedAt = $startedAt ?? new \DateTimeImmutable();
    }

    /**
     * Append one error entry. Does not throw — the report is meant to
     * survive partial failures.
     *
     * @param   int|null  $pcPersonId  PC person id, or null if the error is
     *                                  pre-row (e.g. malformed page envelope).
     * @param   string    $message     Human-readable error message.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function recordError(?int $pcPersonId, string $message): void
    {
        $this->errors[] = ['pcPersonId' => $pcPersonId, 'message' => $message];
    }

    /**
     * Mark the run as finished — stamps the finishedAt timestamp.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function finish(): void
    {
        $this->finishedAt = new \DateTimeImmutable();
    }

    /**
     * Count of recorded errors.
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    public function errorCount(): int
    {
        return \count($this->errors);
    }

    /**
     * Whether the run completed with zero errors.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function success(): bool
    {
        return $this->errors === [];
    }

    /**
     * Elapsed seconds between start and finish, or null if {@see finish()}
     * was never called.
     *
     * @return  float|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function durationSeconds(): ?float
    {
        if ($this->finishedAt === null) {
            return null;
        }

        return (float) ($this->finishedAt->format('U.u')) - (float) ($this->startedAt->format('U.u'));
    }

    /**
     * Serialise to a JSON-friendly array. Used by the Cpanel sync endpoint
     * to render results in the admin UI.
     *
     * @return  array<string, mixed>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function toArray(): array
    {
        return [
            'seen'             => $this->seen,
            'added'            => $this->added,
            'updated'          => $this->updated,
            'archived'         => $this->archived,
            'unarchived'       => $this->unarchived,
            'customFieldsWritten' => $this->customFieldsWritten,
            'photosDownloaded'    => $this->photosDownloaded,
            'photosUnchanged'     => $this->photosUnchanged,
            'paired'              => $this->paired,
            'errorCount'       => $this->errorCount(),
            'errors'           => $this->errors,
            'startedAt'        => $this->startedAt->format(\DateTimeImmutable::ATOM),
            'finishedAt'       => $this->finishedAt?->format(\DateTimeImmutable::ATOM),
            'durationSeconds'  => $this->durationSeconds(),
            'success'          => $this->success(),
        ];
    }
}
