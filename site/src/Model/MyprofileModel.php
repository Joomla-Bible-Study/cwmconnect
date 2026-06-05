<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Helper\PcLockedFields;
use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use CWM\Component\Cwmconnect\Administrator\Table\MemberTable;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Database\ParameterType;

/**
 * Phase H: self-service portal model. Resolves the current Joomla user's
 * paired member row, renders the edit form with PC-sourced fields locked
 * (spec §8), and rejects writes that would mutate locked columns (spec §8.3).
 *
 * @since  2.0.0
 */
class MyprofileModel extends FormModel
{
    /** @var string Model context for state caching and form events. */
    protected $context = 'com_cwmconnect.myprofile';

    /** @var object|false|null Cached member row for the current user. */
    private object|false|null $item = null;

    /**
     * Load the portal form. PC-sourced columns are marked readonly so the
     * browser respects the lock; {@see detectLockedFieldChanges()} provides
     * the server-side enforcement for URL-hacked payloads.
     *
     * @since  2.0.0
     */
    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm($this->context, 'myprofile', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $item   = $this->getItemForCurrentUser();
        $locked = PcLockedFields::forItem($item ?: null);

        foreach ($locked as $field) {
            $form->setFieldAttribute($field, 'readonly', 'true');
        }

        return $form;
    }

    /**
     * Hand back the persistent row as form data when one exists. Falls back to
     * the user-state buffer so a failed save round-trips the user's edits.
     *
     * @return  array<string, mixed>
     *
     * @since   2.0.0
     */
    protected function loadFormData(): array
    {
        $buffered = (array) Factory::getApplication()->getUserState('com_cwmconnect.myprofile.data', []);

        if ($buffered !== []) {
            return $buffered;
        }

        $item = $this->getItemForCurrentUser();

        if ($item === false || $item === null) {
            return [];
        }

        return get_object_vars($item);
    }

    /**
     * Load the member row paired to the current logged-in user. Returns false
     * when the viewer has no paired row (handled by the view — spec §8.1).
     *
     * @since  2.0.0
     */
    public function getItemForCurrentUser(): object|false
    {
        if ($this->item !== null) {
            return $this->item;
        }

        $userId = (int) ($this->getCurrentUser()->id ?? 0);

        if ($userId <= 0) {
            return $this->item = false;
        }

        return $this->item = $this->loadItemByUserId($userId);
    }

    /**
     * Direct DB lookup keyed on `user_id`. Schema invariant since Phase H.1:
     * `user_id` is nullable + UNIQUE, so at most one row can match.
     *
     * @since  2.0.0
     */
    protected function loadItemByUserId(int $userId): object|false
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->where($db->quoteName('published') . ' > -2')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();

        return $row ?: false;
    }

    /**
     * Persist a portal save. Short-circuits on locked-field tampering with the
     * spec §8.3 flash so URL-hacked POSTs can't sneak past the readonly UI.
     *
     * @param   array<string, mixed>  $data
     *
     * @since   2.0.0
     */
    public function save(array $data): bool
    {
        $item = $this->getItemForCurrentUser();

        if ($item === false) {
            $this->setError(Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_NOT_PAIRED'));

            return false;
        }

        $violations = self::detectLockedFieldChanges($item, $data);

        if ($violations !== []) {
            $this->setError(Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_LOCKED_FIELD'));

            return false;
        }

        $table = new MemberTable($this->getDatabase());

        if (!$table->load((int) $item->id)) {
            $this->setError(Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_LOAD_FAILED'));

            return false;
        }

        $allowed = self::editableColumns($item);
        $bind    = array_intersect_key($data, array_flip($allowed));

        if (!$table->bind($bind) || !$table->check() || !$table->store()) {
            $this->setError((string) $table->getError());

            return false;
        }

        return true;
    }

    /**
     * Pure helper: which locked columns does the incoming payload try to
     * mutate? Returns the list of column names whose new value differs from
     * the persistent value. Empty list ⇒ save is safe to proceed.
     *
     * @param   object                $item  Persistent row.
     * @param   array<string, mixed>  $data  Incoming form data.
     *
     * @return  list<string>
     *
     * @since   2.0.0
     */
    public static function detectLockedFieldChanges(object $item, array $data): array
    {
        $locked     = PcLockedFields::forItem($item);
        $violations = [];

        foreach ($locked as $column) {
            if (!\array_key_exists($column, $data)) {
                continue;
            }

            $current = $item->{$column} ?? null;

            if ((string) $data[$column] !== (string) $current) {
                $violations[] = $column;
            }
        }

        return $violations;
    }

    /**
     * The portal-writable column set for a given persistent row. PC-linked
     * rows get an inverse-of-locked allowlist; local-only rows get the full
     * portal column list.
     *
     * @return  list<string>
     *
     * @since   2.0.0
     */
    public static function editableColumns(object $item): array
    {
        $all = [
            'name', 'surname', 'lname',
            'email_to', 'telephone', 'mobile',
            'address', 'suburb', 'state', 'postcode', 'country',
            'birthdate', 'anniversary',
            'display_in_directory',
            'sortname1', 'sortname2', 'sortname3',
        ];

        $locked = PcLockedFields::forItem($item);

        return array_values(array_diff($all, $locked));
    }

    /**
     * Configured inactivity window (days) for live KML feeds; 0 = disabled.
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    public function feedInactivityDays(): int
    {
        return (int) ComponentHelper::getParams('com_cwmconnect')->get('kml_feed_inactivity_days', 90);
    }

    /**
     * Maximum number of live feeds a member may hold at once (>= 1).
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    public function maxFeedsPerMember(): int
    {
        return max(1, (int) ComponentHelper::getParams('com_cwmconnect')->get('kml_feed_max_per_member', 5));
    }

    /**
     * The current user's live feeds (revoked ones omitted), each tagged with a
     * computed status. Never exposes the token hash.
     *
     * @return  list<object>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getFeeds(): array
    {
        $userId = (int) ($this->getCurrentUser()->id ?? 0);

        if ($userId <= 0) {
            return [];
        }

        return new FeedTokenService($this->getDatabase())
            ->listForUser($userId, $this->feedInactivityDays(), false);
    }

    /**
     * Count the current user's *live* feeds (status active) — these occupy a
     * slot against {@see maxFeedsPerMember()}. Expired feeds do not count.
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    public function activeFeedCount(): int
    {
        $count = 0;

        foreach ($this->getFeeds() as $feed) {
            if (($feed->status ?? '') === FeedTokenService::STATUS_ACTIVE) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Issue a new named live feed for the current user.
     *
     * @param   string       $label      Friendly name; blank falls back to a default.
     * @param   string|null  $expiresAt  Optional absolute expiry 'Y-m-d H:i:s' (UTC).
     *
     * @return  array{cleartext: string, id: int}  One-time cleartext + new row id.
     *
     * @throws  \RuntimeException  When unauthenticated or the per-member cap is reached.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function createFeed(string $label, ?string $expiresAt = null): array
    {
        $userId = (int) ($this->getCurrentUser()->id ?? 0);

        if ($userId <= 0) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_NOT_PAIRED'));
        }

        if ($this->activeFeedCount() >= $this->maxFeedsPerMember()) {
            throw new \RuntimeException(
                Text::sprintf('COM_CWMCONNECT_MYPROFILE_FEED_CAP_REACHED', $this->maxFeedsPerMember()),
            );
        }

        $label = trim($label) !== ''
            ? trim($label)
            : Text::_('COM_CWMCONNECT_MYPROFILE_FEED_DEFAULT_LABEL');

        return new FeedTokenService($this->getDatabase())->issue($userId, $label, $expiresAt);
    }

    /**
     * Revoke one of the current user's feeds.
     *
     * @param   int  $id  Token row id.
     *
     * @return  void
     *
     * @throws  \RuntimeException  When the feed is not owned by the current user.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function revokeFeed(int $id): void
    {
        $this->assertOwnsFeed($id);

        new FeedTokenService($this->getDatabase())->revoke([$id]);
    }

    /**
     * Rotate one of the current user's feeds (e.g. they lost the .kml). Returns
     * the new one-time cleartext.
     *
     * @param   int  $id  Token row id.
     *
     * @return  string  New cleartext token.
     *
     * @throws  \RuntimeException  When the feed is not owned by the current user.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function regenerateFeed(int $id): string
    {
        $this->assertOwnsFeed($id);

        return new FeedTokenService($this->getDatabase())->regenerate($id);
    }

    /**
     * Revoke every active feed the current user holds (panic button).
     *
     * @return  int  Number of feeds revoked.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function revokeAllFeeds(): int
    {
        $userId = (int) ($this->getCurrentUser()->id ?? 0);

        if ($userId <= 0) {
            return 0;
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->where($db->quoteName('revoked_at') . ' IS NULL')
            ->bind(':uid', $userId, ParameterType::INTEGER);

        $ids = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);

        if ($ids === []) {
            return 0;
        }

        return new FeedTokenService($db)->revoke($ids);
    }

    /**
     * Ownership guard: the feed must belong to the current user. Prevents a
     * member acting on another member's feed by guessing ids (IDOR).
     *
     * @param   int  $id  Token row id.
     *
     * @return  void
     *
     * @throws  \RuntimeException
     *
     * @since   __DEPLOY_VERSION__
     */
    private function assertOwnsFeed(int $id): void
    {
        $userId = (int) ($this->getCurrentUser()->id ?? 0);
        $db     = $this->getDatabase();

        $query = $db->createQuery()
            ->select($db->quoteName('user_id'))
            ->from($db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $owner = (int) $db->setQuery($query)->loadResult();

        if ($userId <= 0 || $id <= 0 || $owner !== $userId) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_MYPROFILE_FEED_NOT_FOUND'));
        }
    }
}
