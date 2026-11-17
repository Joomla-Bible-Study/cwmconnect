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

/**
 * The details a member can keep from the general membership.
 *
 * A member's choices are stored in `hidden_fields` as a comma-separated list
 * of the keys below. Some details belong to the whole household (its address,
 * home phone and anniversary): one of those is hidden for every member of the
 * household when any of them hides it, or a household-mate's entry would show
 * it anyway. Who sees past the choices is decided by {@see DirectoryViewer}.
 *
 * @since  __DEPLOY_VERSION__
 */
final class MemberVisibility
{
    /**
     * Each detail a member can hide, and the columns it covers.
     *
     * @var    array<string, list<string>>
     * @since  __DEPLOY_VERSION__
     */
    public const DETAILS = [
        'email'       => ['email_to'],
        'phone'       => ['telephone', 'fax'],
        'mobile'      => ['mobile'],
        'address'     => ['address', 'suburb', 'state', 'postcode', 'country', 'lat', 'lng'],
        'birthdate'   => ['birthdate'],
        'anniversary' => ['anniversary'],
        'photo'       => ['image'],
    ];

    /**
     * The details the whole household shares.
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    public const SHARED = ['phone', 'address', 'anniversary'];

    /**
     * The known keys in a stored or submitted choice, in DETAILS order.
     *
     * @param   mixed  $choice  A comma-separated string, a list of keys, or null.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function parse(mixed $choice): array
    {
        $keys = \is_array($choice) ? $choice : explode(',', (string) $choice);
        $keys = array_map(static fn(mixed $key): string => trim((string) $key), $keys);

        return array_values(array_intersect(array_keys(self::DETAILS), $keys));
    }

    /**
     * A choice as it is stored: the known keys, comma-separated, in DETAILS order.
     *
     * @param   mixed  $choice  A comma-separated string, a list of keys, or null.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function encode(mixed $choice): string
    {
        return implode(',', self::parse($choice));
    }

    /**
     * The household-wide details any of a household's members hides.
     *
     * @param   iterable<mixed>  $choices  The stored choices of a household's members.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function sharedHidden(iterable $choices): array
    {
        $hidden = [];

        foreach ($choices as $choice) {
            foreach (self::parse($choice) as $key) {
                $hidden[$key] = true;
            }
        }

        return array_values(array_intersect(self::SHARED, array_keys($hidden)));
    }

    /**
     * Everything to keep from the general membership about a member: their own
     * choices, plus the household details their household hides.
     *
     * @param   object        $member          A member row with `hidden_fields`.
     * @param   list<string>  $householdShared What {@see sharedHidden()} found for their household.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function hiddenFor(object $member, array $householdShared = []): array
    {
        $own = self::parse($member->hidden_fields ?? '');

        return array_values(array_intersect(array_keys(self::DETAILS), array_merge($own, $householdShared)));
    }

    /**
     * Blank a member row's hidden details in place, and record which ones were
     * hidden in `hidden_details`.
     *
     * Text columns become '', dates null, and the map position 0, so every
     * page treats them as never filled in.
     *
     * @param   object        $member  A member row.
     * @param   list<string>  $hidden  Keys from {@see hiddenFor()}.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function scrub(object $member, array $hidden): void
    {
        foreach ($hidden as $key) {
            foreach (self::DETAILS[$key] ?? [] as $column) {
                if (!property_exists($member, $column)) {
                    continue;
                }

                $member->{$column} = match ($column) {
                    'lat', 'lng'                => 0,
                    'birthdate', 'anniversary'  => null,
                    default                     => '',
                };
            }
        }

        $member->hidden_details = $hidden;
    }
}
