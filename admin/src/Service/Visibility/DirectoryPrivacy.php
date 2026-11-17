<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Visibility;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Database\DatabaseInterface;

/**
 * Applies members' choices to what a viewer is about to see.
 *
 * Pages read member rows straight from their models, so the rows are blanked
 * here, right after loading, before any page or feed can show them.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DirectoryPrivacy
{
    /**
     * @param   DatabaseInterface  $db  Database to read households' choices from.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly DatabaseInterface $db) {}

    /**
     * Blank, in place, what a viewer may not see on member rows.
     *
     * @param   iterable<object>  $members  Rows with `id`, `funitid` and `hidden_fields`.
     * @param   DirectoryViewer   $viewer   Who is looking.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function apply(iterable $members, DirectoryViewer $viewer): void
    {
        if ($viewer->seesAll) {
            return;
        }

        $restricted = [];

        foreach ($members as $member) {
            if (!$viewer->seesAllOf($member)) {
                $restricted[] = $member;
            }
        }

        if ($restricted === []) {
            return;
        }

        $choices = $this->householdChoices(array_map(static fn(object $member): int => (int) ($member->funitid ?? 0), $restricted));

        foreach ($restricted as $member) {
            $shared = MemberVisibility::sharedHidden($choices[(int) ($member->funitid ?? 0)] ?? []);

            MemberVisibility::scrub($member, MemberVisibility::hiddenFor($member, $shared));
        }
    }

    /**
     * What the general membership may not see of a member: their own choices
     * and their household's. For what is shared by everyone, such as the
     * Smart Search index.
     *
     * @param   object  $member  A row with `funitid` and `hidden_fields`.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function hiddenFromMembers(object $member): array
    {
        $household = (int) ($member->funitid ?? 0);
        $choices   = $this->householdChoices([$household]);

        return MemberVisibility::hiddenFor($member, MemberVisibility::sharedHidden($choices[$household] ?? []));
    }

    /**
     * The members of these households.
     *
     * @param   list<int>  $householdIds  Households; 0 means none.
     *
     * @return  list<int>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function householdMembers(array $householdIds): array
    {
        $householdIds = array_values(array_unique(array_filter($householdIds, static fn(int $id): bool => $id > 0)));

        if ($householdIds === []) {
            return [];
        }

        $db    = $this->db;
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__cwmconnect_details'))
            ->whereIn($db->quoteName('funitid'), $householdIds);

        return array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
    }

    /**
     * Of these households, the ones whose photo a viewer may not see: a
     * household photo shows everyone in it, so it is hidden when any of them
     * hides their own.
     *
     * @param   list<int>        $householdIds  Households to check.
     * @param   DirectoryViewer  $viewer        Who is looking.
     *
     * @return  list<int>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function hiddenHouseholdPhotos(array $householdIds, DirectoryViewer $viewer): array
    {
        if ($viewer->seesAll) {
            return [];
        }

        $hidden = [];

        foreach ($this->householdChoices($householdIds) as $household => $choices) {
            if (\in_array($household, $viewer->householdIds, true)) {
                continue;
            }

            foreach ($choices as $choice) {
                if (\in_array('photo', MemberVisibility::parse($choice), true)) {
                    $hidden[] = $household;

                    break;
                }
            }
        }

        return $hidden;
    }

    /**
     * The stored choices of the members of each household that hide anything.
     *
     * @param   list<int>  $householdIds  Households to look up; 0 means none.
     *
     * @return  array<int, list<string>>  Choices keyed by household id.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function householdChoices(array $householdIds): array
    {
        $householdIds = array_values(array_unique(array_filter($householdIds, static fn(int $id): bool => $id > 0)));

        if ($householdIds === []) {
            return [];
        }

        $db    = $this->db;
        $query = $db->createQuery()
            ->select([$db->quoteName('funitid'), $db->quoteName('hidden_fields')])
            ->from($db->quoteName('#__cwmconnect_details'))
            ->whereIn($db->quoteName('funitid'), $householdIds)
            ->where($db->quoteName('hidden_fields') . " <> ''");

        $choices = [];

        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $row) {
            $choices[(int) $row->funitid][] = (string) $row->hidden_fields;
        }

        return $choices;
    }
}
