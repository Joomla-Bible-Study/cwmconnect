<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Object\CMSObject;
use Joomla\Database\DatabaseInterface;

/**
 * Churchdirectory administration helper.
 *
 * @since  2.0.0
 */
class ChurchdirectoryHelper
{
    /**
     * Component option.
     *
     * @var string
     * @since 2.0.0
     */
    public static string $extension = 'com_churchdirectory';

    /**
     * Gets a list of the actions that can be performed.
     *
     * Mirrors the J5+ helper pattern (e.g. com_content) — use ContentHelper
     * for general lookups; this is kept for component-specific calls that
     * still want a `CMSObject` shaped result.
     *
     * @param   string  $component  The component name.
     * @param   string  $section    The access section name.
     * @param   int     $id         The item ID.
     *
     * @return  CMSObject
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public static function getActions(string $component = '', string $section = '', int $id = 0): CMSObject
    {
        $user   = Factory::getApplication()->getIdentity();
        $result = new CMSObject();

        if ($component === '') {
            $component = self::$extension;
        }

        $assetName = $component;

        if ($section !== '' && $id > 0) {
            $assetName .= '.' . $section . '.' . $id;
        }

        $accessFile = JPATH_ADMINISTRATOR . '/components/' . $component . '/access.xml';
        $actions    = Access::getActionsFromFile($accessFile, "/access/section[@name='component']/");

        if ($actions === false) {
            return $result;
        }

        foreach ($actions as $action) {
            $result->set($action->name, $user?->authorise($action->name, $assetName));
        }

        return $result;
    }

    /**
     * Compute a member's age in whole years from a birth date.
     *
     * @param   string|null  $birthDate  Birth date in any format DateTime can parse.
     *
     * @return  int  Age in years; 0 if the date is empty or matches the current year.
     *
     * @since   2.0.0
     */
    public static function getAge(?string $birthDate): int
    {
        if ($birthDate === null || $birthDate === '') {
            return 0;
        }

        try {
            $date     = new \DateTime($birthDate);
            $now      = new \DateTime();
            $interval = $now->diff($date);
        } catch (\Exception) {
            return 0;
        }

        if ($interval->y === (int) date('Y')) {
            return 0;
        }

        return (int) $interval->y;
    }

    /**
     * Get all language associations for a member record.
     *
     * @param   int  $pk  Member id.
     *
     * @return  array<string, object>|false
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public static function getAssociations(int $pk): array|false
    {
        if (!Associations::isEnabled()) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->from($db->quoteName('#__churchdirectory_details', 'c'))
            ->join(
                'INNER',
                $db->quoteName('#__associations', 'a')
                . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('c.id')
                . ' AND ' . $db->quoteName('a.context') . ' = ' . $db->quote('com_churchdirectory.item')
            )
            ->join(
                'INNER',
                $db->quoteName('#__associations', 'a2')
                . ' ON ' . $db->quoteName('a.key') . ' = ' . $db->quoteName('a2.key')
            )
            ->join(
                'INNER',
                $db->quoteName('#__churchdirectory_details', 'c2')
                . ' ON ' . $db->quoteName('a2.id') . ' = ' . $db->quoteName('c2.id')
            )
            ->join(
                'INNER',
                $db->quoteName('#__categories', 'ca')
                . ' ON ' . $db->quoteName('c2.catid') . ' = ' . $db->quoteName('ca.id')
                . ' AND ' . $db->quoteName('ca.extension') . ' = ' . $db->quote('com_churchdirectory')
            )
            ->where($db->quoteName('c.id') . ' = ' . (int) $pk)
            ->select([
                $db->quoteName('c2.language'),
                $query->concatenate([$db->quoteName('c2.id'), $db->quoteName('c2.alias')], ':') . ' AS id',
                $query->concatenate([$db->quoteName('ca.id'), $db->quoteName('ca.alias')], ':') . ' AS catid',
            ]);

        try {
            $db->setQuery($query);
            $rows = $db->loadObjectList('language');
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            return false;
        }

        $associations = [];

        foreach ((array) $rows as $tag => $item) {
            $associations[$tag] = $item;
        }

        return $associations;
    }

    /**
     * Adds count items for the category manager.
     *
     * @param   array  $items  The category objects.
     *
     * @return  array
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public static function countItems(array &$items): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($items as $item) {
            $item->count_trashed     = 0;
            $item->count_archived    = 0;
            $item->count_unpublished = 0;
            $item->count_published   = 0;

            $query = $db->getQuery(true)
                ->select($db->quoteName('published', 'state'))
                ->select('COUNT(*) AS ' . $db->quoteName('count'))
                ->from($db->quoteName('#__churchdirectory_details'))
                ->where($db->quoteName('catid') . ' = ' . (int) $item->id)
                ->group($db->quoteName('published'));

            $db->setQuery($query);
            $rows = $db->loadObjectList();

            foreach ((array) $rows as $row) {
                match ((int) $row->state) {
                    1       => $item->count_published   = (int) $row->count,
                    0       => $item->count_unpublished = (int) $row->count,
                    2       => $item->count_archived    = (int) $row->count,
                    -2      => $item->count_trashed     = (int) $row->count,
                    default => null,
                };
            }
        }

        return $items;
    }

    /**
     * Returns a short marker for the given member status code.
     *
     * @param   int|string  $status  Member status code.
     *
     * @return  string  Short marker text in the form "(X)".
     *
     * @since   2.0.0
     */
    public static function memberStatusShort(int|string $status): string
    {
        return match ((int) $status) {
            0       => '(A)',
            1       => '(I)',
            2       => '(A.AT.)',
            3       => '(NM)',
            4       => '(T)',
            default => '(O)',
        };
    }
}
