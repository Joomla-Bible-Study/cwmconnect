<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Geocoding worker model.
 *
 * Walks every member row that has a postal address and asks the Google
 * geocoding API for lat/lng coordinates, persisting either the result on
 * the member row or the failure on `#__churchdirectory_geoupdate`. The
 * queue is paged through the user's session so the browser can drive
 * the pass forward in slices.
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
    private const string SESSION_NAMESPACE = 'churchdirectory';

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

        return $result;
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
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'address', 'suburb', 'state', 'postcode', 'lat', 'lng', 'country']))
            ->from($db->quoteName('#__churchdirectory_details'));

        if ($id) {
            $query->where($db->quoteName('id') . ' = ' . (int) $id);
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

        $db  = $this->getDatabase();
        $key = ComponentHelper::getParams('com_churchdirectory')->get('apikey');

        if ($id) {
            $memberId = $id;
            $query    = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__churchdirectory_details'))
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

        $baseUrl = 'https://maps.googleapis.com/maps/api/geocode/xml?address=';
        $address = str_replace(' ', '+', (string) $row->address);
        $suburb  = str_replace(' ', '+', (string) ($row->suburb ?? ''));
        $state   = (string) ($row->state ?? '');

        $requestUrl = $baseUrl . $address . ',+' . $suburb . ',+' . $state
            . '&sensor=true&key=' . urlencode((string) $key);

        $delay           = 0;
        $geocodePending  = true;

        while ($geocodePending) {
            $body = @file_get_contents($requestUrl);

            if ($body === false) {
                return true;
            }

            $xml = @simplexml_load_string($body);

            if (!$xml instanceof \SimpleXMLElement) {
                return true;
            }

            $status = (string) $xml->status;

            if ($status === 'OK') {
                $geocodePending = false;

                foreach ($xml->result as $data) {
                    $ulat  = (string) $data->geometry->location->lat;
                    $ulng  = (string) $data->geometry->location->lng;

                    $update = $db->getQuery(true)
                        ->update($db->quoteName('#__churchdirectory_details'))
                        ->set($db->quoteName('lat') . ' = ' . $db->quote($ulat))
                        ->set($db->quoteName('lng') . ' = ' . $db->quote($ulng))
                        ->where($db->quoteName('id') . ' = ' . (int) $row->id);
                    $db->setQuery($update)->execute();

                    // Drop any prior error row for this member.
                    $check = $db->getQuery(true)
                        ->select($db->quoteName('member_id'))
                        ->from($db->quoteName('#__churchdirectory_geoupdate'))
                        ->where($db->quoteName('member_id') . ' = ' . (int) $memberId);
                    $db->setQuery($check);

                    if ($db->loadResult()) {
                        $delete = $db->getQuery(true)
                            ->delete($db->quoteName('#__churchdirectory_geoupdate'))
                            ->where($db->quoteName('member_id') . ' = ' . (int) $memberId);
                        $db->setQuery($delete)->execute();
                    }
                }
            } elseif ($status === 'OVER_QUERY_LIMIT') {
                // Cap incremental backoff so a sustained rate-limit can't
                // accumulate an unbounded usleep past the slice budget.
                $delay = min($delay + 100000, (int) (self::SLICE_BUDGET * 1_000_000));
            } else {
                $geocodePending = false;
                $errorMessage   = isset($xml->result->error_message)
                    ? (string) $xml->result->error_message
                    : '';
                $info = sprintf(
                    'Status: %s<br /><div style="float:left; padding:5px;">Error Message:</div><div style="float:left; padding:5px;">%s</div>',
                    $status,
                    $errorMessage
                );

                $check = $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('#__churchdirectory_geoupdate'))
                    ->where($db->quoteName('member_id') . ' = ' . (int) $row->id);
                $db->setQuery($check);

                if ($db->loadResult()) {
                    $update = $db->getQuery(true)
                        ->update($db->quoteName('#__churchdirectory_geoupdate'))
                        ->set($db->quoteName('status') . ' = ' . $db->quote($info))
                        ->where($db->quoteName('member_id') . ' = ' . (int) $row->id);
                    $db->setQuery($update)->execute();
                } else {
                    $insert = $db->getQuery(true)
                        ->insert($db->quoteName('#__churchdirectory_geoupdate'))
                        ->set($db->quoteName('member_id') . ' = ' . (int) $row->id)
                        ->set($db->quoteName('status') . ' = ' . $db->quote($info));
                    $db->setQuery($insert)->execute();
                }
            }

            if ($delay > 0) {
                usleep($delay);
            }

            // Bubble back to the outer slice when over budget; the row
            // stays pending and the next tick will retry it. Without this
            // a sustained OVER_QUERY_LIMIT response would spin past
            // SLICE_BUDGET because the inner loop never re-checks time.
            if ($geocodePending && !$this->haveEnoughTime()) {
                break;
            }
        }

        return $geocodePending;
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

        $this->membersStack = (array) ($decoded['members'] ?? []);
        $this->totalMembers = (int)   ($decoded['total']   ?? 0);
        $this->doneMembers  = (int)   ($decoded['done']    ?? 0);

        return true;
    }
}
