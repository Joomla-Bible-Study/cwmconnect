<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\Geocode\GeocoderFactory;
use CWM\Component\Cwmconnect\Administrator\Service\Geocode\GeocoderInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Geocoding worker model.
 *
 * Walks every member row that has a postal address and asks the configured
 * geocoding provider (Nominatim or Google — see {@see GeocoderFactory}) for
 * lat/lng coordinates, persisting either the result on the member row or the
 * failure on `#__cwmconnect_geoupdate`. The queue is paged through the user's
 * session so the browser can drive the pass forward in slices.
 *
 * @since  2.0.0
 */
class GeoupdateModel extends BaseDatabaseModel
{
    /**
     * Soft per-slice budget in seconds — keeps requests well under the
     * web server's hard limit so the browser can request the next slice.
     *
     * @since 2.0.0
     */
    private const float SLICE_BUDGET = 2.0;

    /**
     * Session namespace for serialized queue state.
     *
     * @since 2.0.0
     */
    private const string SESSION_NAMESPACE = 'cwmconnect';

    /**
     * Session key for the serialized queue state.
     *
     * @since 2.0.0
     */
    private const string SESSION_KEY = 'geoupdate_stack';

    /**
     * Time the current slice started (microseconds, float).
     *
     * @var float|null
     * @since 2.0.0
     */
    private ?float $startTime = null;

    /**
     * Members still to process in the current slice.
     *
     * @var array<int, object>
     * @since 2.0.0
     */
    private array $membersStack = [];

    /**
     * Total members enqueued in this pass.
     *
     * @var int
     * @since 2.0.0
     */
    public int $totalMembers = 0;

    /**
     * Members already processed in this pass.
     *
     * @var int
     * @since 2.0.0
     */
    public int $doneMembers = 0;

    /**
     * Geocoder, built lazily from the component params.
     *
     * @var    GeocoderInterface|null
     * @since  __DEPLOY_VERSION__
     */
    private ?GeocoderInterface $geocoder = null;

    /**
     * Reset and start a fresh scan. If $id is set, only that single row is
     * processed.
     *
     * @param   int|null  $id  Member id to limit the scan to.
     *
     * @return  bool  True if more work remains after the first slice.
     *
     * @throws  \JsonException
     * @since   2.0.0
     */
    public function startScanning(?int $id = null): bool
    {
        $this->resetStack();
        $this->resetTimer();
        $this->loadMembers($id);

        if (empty($this->membersStack)) {
            $this->membersStack = [];
        }

        asort($this->membersStack);
        $this->saveStack();

        if (!$this->haveEnoughTime()) {
            return true;
        }

        return $this->run(false, $id);
    }

    /**
     * Process queue entries until the slice budget is exhausted.
     *
     * @param   bool      $resetTimer  Reset the slice timer before running.
     * @param   int|null  $id          Optional single member id to process.
     *
     * @return  bool  True if more work remains, false when the queue is empty.
     *
     * @throws  \JsonException
     * @since   2.0.0
     */
    public function run(bool $resetTimer = true, ?int $id = null): bool
    {
        if ($resetTimer) {
            $this->resetTimer();
        }

        $this->loadStack();

        $result = true;

        while ($result && $this->haveEnoughTime()) {
            $result = $this->processSlice($id);
        }

        $this->saveStack();

        // The queue has drained, so every address that could resolve has. Now
        // give the addressless members of a household their household's point.
        if ($result === false && $id === null) {
            $this->propagateHouseholdCoordinates();
        }

        return $result;
    }

    /**
     * Place members who have no address of their own at their household's
     * location.
     *
     * Most people with no pin are not geocoding failures — on the reference
     * dataset 85 of 91 have no address at all, because the address lives on
     * one member of the household and the rest inherit it socially rather than
     * in the data. They are invisible to {@see self::loadMembers()}, which only
     * queues rows that have something to geocode, so no number of re-runs will
     * ever place them.
     *
     * A member's own address always wins — 17 of 73 households on that same
     * dataset have members living at different addresses, so assuming one pin
     * per household would move real people to the wrong place. Only members
     * with a genuinely empty address inherit.
     *
     * Runs on every full pass rather than only filling blanks, so a household
     * that moves re-propagates: the address holder is re-geocoded, and the
     * inherited rows follow instead of keeping a stale pin forever.
     *
     * @return  int  Number of member rows moved onto a household point.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function propagateHouseholdCoordinates(): int
    {
        $db = $this->getDatabase();

        // One source per household: the lowest-id member who has both an
        // address of their own and a resolved point.
        $query = $db->createQuery()
            ->select($db->quoteName(['funitid', 'lat', 'lng']))
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('funitid') . ' > 0')
            ->where('TRIM(COALESCE(' . $db->quoteName('address') . ", '')) <> ''")
            ->where('CAST(' . $db->quoteName('lat') . ' AS DECIMAL(12,8)) <> 0')
            ->where('CAST(' . $db->quoteName('lng') . ' AS DECIMAL(12,8)) <> 0')
            ->order($db->quoteName('id') . ' ASC');

        $sources = [];

        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $row) {
            $sources[(int) $row->funitid] ??= $row;
        }

        $moved = 0;

        foreach ($sources as $funitid => $source) {
            $lat = (string) $source->lat;
            $lng = (string) $source->lng;

            $update = $db->createQuery()
                ->update($db->quoteName('#__cwmconnect_details'))
                ->set($db->quoteName('lat') . ' = :lat')
                ->set($db->quoteName('lng') . ' = :lng')
                ->where($db->quoteName('funitid') . ' = :funitid')
                ->where('TRIM(COALESCE(' . $db->quoteName('address') . ", '')) = ''")
                // Skip rows already on the point, so a no-op pass writes nothing.
                ->where(
                    '(COALESCE(' . $db->quoteName('lat') . ", '') <> :latCmp"
                    . ' OR COALESCE(' . $db->quoteName('lng') . ", '') <> :lngCmp)",
                )
                ->bind(':lat', $lat, ParameterType::STRING)
                ->bind(':lng', $lng, ParameterType::STRING)
                ->bind(':latCmp', $lat, ParameterType::STRING)
                ->bind(':lngCmp', $lng, ParameterType::STRING)
                ->bind(':funitid', $funitid, ParameterType::INTEGER);

            $db->setQuery($update)->execute();

            $moved += (int) $db->getAffectedRows();
        }

        return $moved;
    }

    /**
     * Pop one member off the stack and update its coordinates.
     *
     * @param   int|null  $id  Optional single member id (resets the stack).
     *
     * @return  bool  True if more rows remain, false when the queue drained.
     *
     * @throws  \JsonException
     * @since   2.0.0
     */
    private function processSlice(?int $id = null): bool
    {
        if ($id) {
            $this->resetStack();
            $this->loadMembers($id);
        }

        if (!empty($this->membersStack)) {
            while (!empty($this->membersStack) && $this->haveEnoughTime()) {
                $member = array_pop($this->membersStack);
                $this->doneMembers++;
                $this->update($member, $id);
            }
        }

        if (empty($this->membersStack)) {
            $this->resetStack();

            return false;
        }

        return true;
    }

    /**
     * Populate the queue from the database.
     *
     * @param   int|null  $id  Optional single member id.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function loadMembers(?int $id = null): void
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'name', 'address', 'suburb', 'state', 'postcode', 'lat', 'lng', 'country']))
            ->from($db->quoteName('#__cwmconnect_details'));

        if ($id) {
            $query->where($db->quoteName('id') . ' = ' . (int) $id);
        } else {
            // Full scan: only members that still need coordinates and actually
            // have an address — so a re-run retries failures + new members
            // without re-geocoding everyone or churning on blank addresses.
            $query->where(
                '(' . $db->quoteName('lat') . ' = 0 OR ' . $db->quoteName('lng') . ' = 0'
                . ' OR ' . $db->quoteName('lat') . ' IS NULL OR ' . $db->quoteName('lng') . ' IS NULL)',
            )
                ->where($db->quoteName('address') . ' IS NOT NULL')
                ->where('TRIM(' . $db->quoteName('address') . ") <> ''");
        }

        $db->setQuery($query);
        $members = $db->loadObjectList() ?: [];

        $this->membersStack = array_merge($this->membersStack, $members);
        $this->totalMembers += \count($members);
    }

    /**
     * Geocode a single member row and persist either the coordinates or the
     * failure to the geoupdate error table.
     *
     * @param   object|null  $row  The row to update.
     * @param   int|null     $id   Optional member id (alternative to $row).
     *
     * @return  bool  True while geocode is pending, false when done.
     *
     * @since   2.0.0
     */
    private function update(?object $row = null, ?int $id = null): bool
    {
        if ($row === null && $id === null) {
            return false;
        }

        $db = $this->getDatabase();

        if ($id) {
            $memberId = $id;
            $query    = $db->createQuery()
                ->select('*')
                ->from($db->quoteName('#__cwmconnect_details'))
                ->where($db->quoteName('id') . ' = ' . (int) $id);
            $db->setQuery($query);
            $row = $db->loadObject();

            if (!\is_object($row)) {
                return false;
            }
        } else {
            $memberId = (int) $row->id;
        }

        if (empty($row->address)) {
            return false;
        }

        // A sibling at the same address may have resolved since the queue was
        // built, back-filling this row. The queue is a snapshot, so re-read the
        // current state and skip the lookup rather than paying for a second
        // call that can only return the same point. Not applied to a forced
        // single-row run ($id), where the caller wants a fresh lookup because
        // the address itself may have just changed.
        if ($id === null && $this->hasCoordinates($memberId)) {
            return false;
        }

        $geocoder = $this->geocoder();
        $result   = $geocoder->geocode(
            (string) $row->address,
            (string) ($row->suburb ?? ''),
            (string) ($row->state ?? ''),
            (string) ($row->country ?? ''),
        );

        // Bounded back-off retry on a rate-limit, staying inside the slice
        // budget; the provider also self-throttles where its policy requires it.
        $delay    = 0;
        $attempts = 0;

        while ($result->rateLimited && $attempts < 5 && $this->haveEnoughTime()) {
            $delay = min($delay + 200_000, (int) (self::SLICE_BUDGET * 1_000_000));
            usleep($delay);

            $result = $geocoder->geocode(
                (string) $row->address,
                (string) ($row->suburb ?? ''),
                (string) ($row->state ?? ''),
                (string) ($row->country ?? ''),
            );
            $attempts++;
        }

        if ($result->found) {
            $this->storeCoordinates($memberId, (float) $result->lat, (float) $result->lng);

            return false;
        }

        $this->logGeocodeError($memberId, $result->status, $result->message);

        return $result->rateLimited;
    }

    /**
     * Build (once) the configured geocoder.
     *
     * @return  GeocoderInterface
     *
     * @since   __DEPLOY_VERSION__
     */
    private function geocoder(): GeocoderInterface
    {
        return $this->geocoder ??= GeocoderFactory::fromParams(
            ComponentHelper::getParams('com_cwmconnect'),
            (string) Factory::getApplication()->get('mailfrom', ''),
        );
    }

    /**
     * Persist resolved coordinates and clear any prior error row.
     *
     * @param   int    $memberId  Member row id.
     * @param   float  $lat       Latitude.
     * @param   float  $lng       Longitude.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function storeCoordinates(int $memberId, float $lat, float $lng): void
    {
        $db     = $this->getDatabase();
        $latStr = sprintf('%.8F', $lat);
        $lngStr = sprintf('%.8F', $lng);

        $update = $db->createQuery()
            ->update($db->quoteName('#__cwmconnect_details'))
            ->set($db->quoteName('lat') . ' = :lat')
            ->set($db->quoteName('lng') . ' = :lng')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':lat', $latStr, ParameterType::STRING)
            ->bind(':lng', $lngStr, ParameterType::STRING)
            ->bind(':id', $memberId, ParameterType::INTEGER);
        $db->setQuery($update)->execute();

        $delete = $db->createQuery()
            ->delete($db->quoteName('#__cwmconnect_geoupdate'))
            ->where($db->quoteName('member_id') . ' = :id')
            ->bind(':id', $memberId, ParameterType::INTEGER);
        $db->setQuery($delete)->execute();

        $this->backfillSameAddress($memberId, $latStr, $lngStr);
    }

    /**
     * Does this member already hold real coordinates?
     *
     * @param   int  $memberId  Member row id.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function hasCoordinates(int $memberId): bool
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('id') . ' = :id')
            ->where('CAST(' . $db->quoteName('lat') . ' AS DECIMAL(12,8)) <> 0')
            ->where('CAST(' . $db->quoteName('lng') . ' AS DECIMAL(12,8)) <> 0')
            ->bind(':id', $memberId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    /**
     * Copy a freshly resolved point onto every other member at the same
     * address who is still missing coordinates.
     *
     * Households are entered per person, so the same street address recurs
     * across the directory — 454 members share 322 distinct addresses on the
     * reference dataset. Geocoding each of them separately buys nothing but
     * billable lookups, and because providers can return marginally different
     * points for identical input it also produced households whose members sat
     * at slightly different pins.
     *
     * Only blank rows are filled: a member who already resolved (or was
     * corrected by hand) is never overwritten by a namesake address.
     *
     * @param   int     $memberId  The member that was just geocoded.
     * @param   string  $latStr    Latitude, pre-formatted for the column.
     * @param   string  $lngStr    Longitude, pre-formatted for the column.
     *
     * @return  int  Number of sibling rows filled in.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function backfillSameAddress(int $memberId, string $latStr, string $lngStr): int
    {
        $db  = $this->getDatabase();
        $key = static fn(string $col): string
            => 'TRIM(LOWER(COALESCE(' . $db->quoteName($col) . ", '')))";

        $source = $db->createQuery()
            ->select([$db->quoteName('address'), $db->quoteName('suburb'), $db->quoteName('postcode')])
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $memberId, ParameterType::INTEGER);

        $row = $db->setQuery($source)->loadObject();

        if (!\is_object($row) || trim((string) $row->address) === '') {
            return 0;
        }

        $address  = trim(strtolower((string) $row->address));
        $suburb   = trim(strtolower((string) ($row->suburb ?? '')));
        $postcode = trim(strtolower((string) ($row->postcode ?? '')));

        $update = $db->createQuery()
            ->update($db->quoteName('#__cwmconnect_details'))
            ->set($db->quoteName('lat') . ' = :lat')
            ->set($db->quoteName('lng') . ' = :lng')
            ->where($db->quoteName('id') . ' <> :id')
            ->where($key('address') . ' = :address')
            ->where($key('suburb') . ' = :suburb')
            ->where($key('postcode') . ' = :postcode')
            ->where(
                '(CAST(' . $db->quoteName('lat') . ' AS DECIMAL(12,8)) = 0'
                . ' OR CAST(' . $db->quoteName('lng') . ' AS DECIMAL(12,8)) = 0'
                . ' OR ' . $db->quoteName('lat') . ' IS NULL'
                . ' OR ' . $db->quoteName('lng') . ' IS NULL)',
            )
            ->bind(':lat', $latStr, ParameterType::STRING)
            ->bind(':lng', $lngStr, ParameterType::STRING)
            ->bind(':id', $memberId, ParameterType::INTEGER)
            ->bind(':address', $address, ParameterType::STRING)
            ->bind(':suburb', $suburb, ParameterType::STRING)
            ->bind(':postcode', $postcode, ParameterType::STRING);

        $db->setQuery($update)->execute();

        return (int) $db->getAffectedRows();
    }

    /**
     * Upsert the per-member geocode failure on `#__cwmconnect_geoupdate` so the
     * Geo status report can surface what went wrong.
     *
     * @param   int     $memberId  Member row id.
     * @param   string  $status    Short status code.
     * @param   string  $message   Detail.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function logGeocodeError(int $memberId, string $status, string $message): void
    {
        $db   = $this->getDatabase();
        $info = trim($status . ($message !== '' ? ': ' . $message : ''));

        $exists = $db->createQuery()
            ->select($db->quoteName('member_id'))
            ->from($db->quoteName('#__cwmconnect_geoupdate'))
            ->where($db->quoteName('member_id') . ' = :id')
            ->bind(':id', $memberId, ParameterType::INTEGER);

        if ($db->setQuery($exists)->loadResult()) {
            $query = $db->createQuery()
                ->update($db->quoteName('#__cwmconnect_geoupdate'))
                ->set($db->quoteName('status') . ' = :info')
                ->where($db->quoteName('member_id') . ' = :id')
                ->bind(':info', $info, ParameterType::STRING)
                ->bind(':id', $memberId, ParameterType::INTEGER);
        } else {
            $query = $db->createQuery()
                ->insert($db->quoteName('#__cwmconnect_geoupdate'))
                ->columns($db->quoteName(['member_id', 'status']))
                ->values(':id, :info')
                ->bind(':id', $memberId, ParameterType::INTEGER)
                ->bind(':info', $info, ParameterType::STRING);
        }

        $db->setQuery($query)->execute();
    }

    /**
     * Reset the slice timer.
     *
     * @return void
     *
     * @since 2.0.0
     */
    private function resetTimer(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * Whether the current slice still has time budget left.
     *
     * @return bool
     *
     * @since 2.0.0
     */
    private function haveEnoughTime(): bool
    {
        if ($this->startTime === null) {
            return false;
        }

        return abs(microtime(true) - $this->startTime) < self::SLICE_BUDGET;
    }

    /**
     * Persist the current queue state to the user's session.
     *
     * @return  void
     *
     * @throws  \JsonException
     * @since   2.0.0
     */
    private function saveStack(): void
    {
        $stack = json_encode([
            'members' => $this->membersStack,
            'total'   => $this->totalMembers,
            'done'    => $this->doneMembers,
        ], JSON_THROW_ON_ERROR);

        if (\function_exists('gzdeflate')) {
            $stack = gzdeflate($stack, 9);
        }

        $stack = base64_encode($stack);

        Factory::getSession()->set(self::SESSION_KEY, $stack, self::SESSION_NAMESPACE);
    }

    /**
     * Forget the current queue state.
     *
     * @return void
     *
     * @since 2.0.0
     */
    private function resetStack(): void
    {
        Factory::getSession()->set(self::SESSION_KEY, '', self::SESSION_NAMESPACE);
        $this->membersStack = [];
        $this->totalMembers = 0;
        $this->doneMembers  = 0;
    }

    /**
     * Restore the queue state from the user's session.
     *
     * @return  bool  True if a stack was found and loaded.
     *
     * @throws  \JsonException
     * @since   2.0.0
     */
    public function loadStack(): bool
    {
        $stack = (string) Factory::getSession()->get(self::SESSION_KEY, '', self::SESSION_NAMESPACE);

        if ($stack === '') {
            $this->membersStack = [];
            $this->totalMembers = 0;
            $this->doneMembers  = 0;

            return false;
        }

        $stack = base64_decode($stack, true);

        if ($stack === false) {
            $this->resetStack();

            return false;
        }

        if (\function_exists('gzinflate')) {
            $inflated = @gzinflate($stack);

            if ($inflated !== false) {
                $stack = $inflated;
            }
        }

        $decoded = json_decode($stack, true, 512, JSON_THROW_ON_ERROR);

        // The queue is JSON-decoded associatively, so each member comes back as
        // an array; re-hydrate to stdClass to match loadMembers() and the
        // object-typed update() the worker calls.
        $this->membersStack = array_map(
            static fn($member): object => (object) $member,
            (array) ($decoded['members'] ?? []),
        );
        $this->totalMembers = (int) ($decoded['total'] ?? 0);
        $this->doneMembers  = (int) ($decoded['done']  ?? 0);

        return true;
    }
}
