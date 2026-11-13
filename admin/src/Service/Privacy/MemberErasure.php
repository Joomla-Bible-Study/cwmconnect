<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Privacy;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\CachedPhotos;
use CWM\Component\Cwmconnect\Administrator\Service\Search\SearchIndexInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Erases a member under a privacy request.
 *
 * The row stays, blanked and archived: it is what an administrator restores a
 * Planning Center member from, and the sync is kept from refilling it (see
 * {@see PrivacySuppressionsInterface}). Everything that says who the person is
 * goes: every personal column, their custom-field values, the versions
 * content history kept of the row, their geocoding status and their cached
 * photos.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class MemberErasure
{
    /**
     * Every personal column, with the value that blanks it: '' or 0 where the
     * column is NOT NULL, null only where it allows it.
     *
     * @var    array<string, string|int|null>
     * @since  __DEPLOY_VERSION__
     */
    public const array BLANK = [
        // The member form requires both names, so the row can still be saved.
        'name'              => 'Removed',
        'lname'             => 'Removed',
        'surname'           => '',
        'fname'             => '',
        'mname'             => '',
        'nickname'          => '',
        'suffix'            => '',
        'sortname1'         => '',
        'sortname2'         => '',
        'sortname3'         => '',
        'created_by_alias'  => '',
        'email_to'          => null,
        'telephone'         => null,
        'mobile'            => '',
        'fax'               => null,
        'skype'             => '',
        'yahoo_msg'         => '',
        'webpage'           => '',
        'pc_social'         => '',
        'address'           => null,
        'suburb'            => null,
        'state'             => null,
        'postcode'          => null,
        'postcodeaddon'     => null,
        'country'           => null,
        'lat'               => 0,
        'lng'               => 0,
        'image'             => null,
        'image_filename'    => null,
        'image_hash'        => null,
        'gender'            => '',
        'mstatus'           => 0,
        'is_child'          => 0,
        'spouse'            => '',
        'children'          => '',
        'funitid'           => 0,
        'con_position'      => '',
        'pc_positions'      => '',
        'pc_ministry_teams' => '',
        'pc_office_role'    => '',
        'is_board'          => 0,
        'is_leader'         => 0,
        'pc_membership'     => '',
        'pc_shared_info'    => null,
        'misc'              => null,
        'params'            => '',
        'attribs'           => '',
        'metakey'           => '',
        'metadesc'          => '',
        'metadata'          => '',
        'xreference'        => '',
        'contact_id'        => 0,
        'user_id'           => null,
    ];

    /**
     * Columns a removal leaves as they are: identity, bookkeeping, and
     * settings that say nothing about the person. With {@see self::BLANK} and
     * the archived state {@see self::removedRow()} adds, these cover every
     * column, which a test holds to the install SQL so a new column gets a
     * decision.
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    public const array KEEP = [
        'id',
        'pc_person_id',
        'pc_last_synced_at',
        'directory_scope',
        'default_con',
        'checked_out',
        'checked_out_time',
        'ordering',
        'catid',
        'kmlid',
        'access',
        'language',
        'created',
        'created_by',
        'modified',
        'modified_by',
        'featured',
        'publish_up',
        'publish_down',
        'version',
        'hits',
    ];

    /**
     * @param   DatabaseInterface             $db            Database driver.
     * @param   PrivacySuppressionsInterface  $suppressions  Keeps the sync from refilling the row.
     * @param   CachedPhotos|null             $photos        The member photo cache, when it is in reach.
     * @param   SearchIndexInterface|null     $searchIndex   Smart Search, which keeps its own copy
     *                                                       of the member. Null leaves it.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private DatabaseInterface $db,
        private PrivacySuppressionsInterface $suppressions,
        private ?CachedPhotos $photos = null,
        private ?SearchIndexInterface $searchIndex = null,
    ) {}

    /**
     * Erase one member row.
     *
     * @param   int  $memberId  Member row id.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function erase(int $memberId): void
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('pc_person_id'))
            ->from($this->db->quoteName('#__cwmconnect_details'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $memberId, ParameterType::INTEGER);

        $pcPersonId = (int) $this->db->setQuery($query)->loadResult();

        // First, so that if anything below fails the sync still won't refill them.
        if ($pcPersonId > 0) {
            $this->suppressions->suppress($pcPersonId);
        }

        $row = (object) (['id' => $memberId] + self::removedRow($this->db, $pcPersonId, $memberId) + [
            'modified' => gmdate('Y-m-d H:i:s'),
        ]);

        $this->db->updateObject('#__cwmconnect_details', $row, 'id', true);

        $memberFields = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__fields'))
            ->where($this->db->quoteName('context') . ' = ' . $this->db->quote('com_cwmconnect.member'));

        $itemId = (string) $memberId;
        $query  = $this->db->createQuery()
            ->delete($this->db->quoteName('#__fields_values'))
            ->where($this->db->quoteName('item_id') . ' = :itemId')
            ->where($this->db->quoteName('field_id') . ' IN (' . $memberFields . ')')
            ->bind(':itemId', $itemId, ParameterType::STRING);

        $this->db->setQuery($query)->execute();

        // A version would put the whole row back if restored.
        $historyId = 'com_cwmconnect.member.' . $memberId;
        $query     = $this->db->createQuery()
            ->delete($this->db->quoteName('#__history'))
            ->where($this->db->quoteName('item_id') . ' = :historyId')
            ->bind(':historyId', $historyId, ParameterType::STRING);

        $this->db->setQuery($query)->execute();

        $query = $this->db->createQuery()
            ->delete($this->db->quoteName('#__cwmconnect_geoupdate'))
            ->where($this->db->quoteName('member_id') . ' = :id')
            ->bind(':id', $memberId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();

        if ($pcPersonId > 0) {
            $this->photos?->forget($pcPersonId);
        }

        $this->searchIndex?->membersRemoved([$memberId]);
    }

    /**
     * What a removed member's row holds: every personal column blank, and the
     * row archived and hidden, with the reason the Members list shows.
     *
     * @param   DatabaseInterface  $db          For the driver's null date.
     * @param   int                $pcPersonId  Planning Center person id, or 0.
     * @param   int                $memberId    Row id, or 0 for a row not created yet.
     *
     * @return  array<string, string|int|null>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function removedRow(DatabaseInterface $db, int $pcPersonId, int $memberId = 0): array
    {
        return self::BLANK + [
            // Unique within the category, as the member form requires, and
            // made from neither name.
            'alias'                => $pcPersonId > 0 ? 'removed-pc' . $pcPersonId : 'removed-' . $memberId,
            'birthdate'            => $db->getNullDate(),
            'anniversary'          => $db->getNullDate(),
            'published'            => 2,
            'display_in_directory' => 0,
            'hidden_reason'        => 'privacy',
        ];
    }
}
