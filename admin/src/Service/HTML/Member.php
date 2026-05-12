<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\HTML;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Helper\CwmconnectHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

/**
 * Member-row HTML helpers — language-association tooltip, featured-toggle
 * link, and the member-status select source.
 *
 * Registered as `cwmconnect.member.<method>`.
 *
 * @since  2.0.0
 */
class Member
{
    /**
     * Render the language-association tooltip for a member row.
     *
     * @param   int  $memberId  The member item id.
     *
     * @return  string  The rendered tooltip markup, or an empty string when
     *                  no associations exist.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function association(int $memberId): string
    {
        $associations = CwmconnectHelper::getAssociations($memberId);

        if ($associations === false || $associations === []) {
            return '';
        }

        $ids = [];

        foreach ($associations as $tag => $associated) {
            $ids[$tag] = (int) ($associated->id ?? 0);
        }

        $ids = array_filter($ids);

        if ($ids === []) {
            return '';
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['c.id', 'c.name', 'c.alias', 'c.catid', 'c.language']))
            ->select($db->quoteName('cat.title', 'category_title'))
            ->select($db->quoteName('l.image'))
            ->select($db->quoteName('l.title', 'language_title'))
            ->from($db->quoteName('#__cwmconnect_details', 'c'))
            ->join('LEFT', $db->quoteName('#__categories', 'cat') . ' ON ' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('c.catid'))
            ->join('LEFT', $db->quoteName('#__languages', 'l') . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('c.language'))
            ->whereIn($db->quoteName('c.id'), array_values($ids));
        $db->setQuery($query);
        $items = $db->loadObjectList('id') ?: [];

        $text = [];

        foreach ($ids as $associatedId) {
            if ($associatedId === $memberId || !isset($items[$associatedId])) {
                continue;
            }

            $row    = $items[$associatedId];
            $image  = HTMLHelper::_(
                'image',
                'mod_languages/' . $row->image . '.gif',
                $row->language_title,
                ['title' => $row->language_title],
                true
            );
            $text[] = Text::sprintf(
                'COM_CWMCONNECT_TIP_ASSOCIATED_LANGUAGE',
                $image,
                $row->name,
                $row->category_title
            );
        }

        if ($text === []) {
            return '';
        }

        return HTMLHelper::_(
            'tooltip',
            implode('<br />', $text),
            Text::_('COM_CWMCONNECT_TIP_ASSOCIATION'),
            'admin/icon-16-links.png'
        );
    }

    /**
     * Render the featured-toggle anchor for a member row.
     *
     * @param   int   $value      Current featured value (0|1).
     * @param   int   $i          Row index.
     * @param   bool  $canChange  Whether the current user can toggle.
     *
     * @return  string  The rendered anchor markup.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function featured(int $value = 0, int $i = 0, bool $canChange = true): string
    {
        $states = [
            0 => ['unfeatured', 'members.featured', 'COM_CWMCONNECT_UNFEATURED', 'COM_CWMCONNECT_TOGGLE_TO_FEATURE'],
            1 => ['featured', 'members.unfeatured', 'JFEATURED', 'COM_CWMCONNECT_TOGGLE_TO_UNFEATURE'],
        ];
        $state  = $states[$value] ?? $states[1];
        $icon   = $state[0];

        if ($canChange) {
            return sprintf(
                '<a href="#" onclick="return Joomla.listItemTask(\'cb%d\', \'%s\')" class="btn btn-sm hasTooltip%s" title="%s"><span class="icon-%s"></span></a>',
                $i,
                $state[1],
                $value === 1 ? ' active' : '',
                HTMLHelper::_('tooltipText', $state[3]),
                $icon
            );
        }

        return sprintf(
            '<a class="btn btn-sm hasTooltip disabled%s" title="%s"><span class="icon-%s"></span></a>',
            $value === 1 ? ' active' : '',
            HTMLHelper::_('tooltipText', $state[2]),
            $icon
        );
    }

    /**
     * Member-status options for select fields.
     *
     * @return  array<int, array{value: int, text: string}>
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function status(): array
    {
        return [
            ['value' => 0, 'text' => Text::_('COM_CWMCONNECT_ACTIVE_MEMBER')],
            ['value' => 1, 'text' => Text::_('COM_CWMCONNECT_INACTIVE')],
            ['value' => 2, 'text' => Text::_('COM_CWMCONNECT_ACTIVE_ATTENDEE')],
            ['value' => 3, 'text' => Text::_('COM_CWMCONNECT_NONE_MEMBER')],
        ];
    }
}
