<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Front-end render helper: positions, family relations, addresses, phone fields,
 * birthday/anniversary lookups, search box, and assorted formatting utilities.
 *
 * Stateful around birthday and family-id iteration; instantiate per request.
 *
 * @since  2.0.0
 */
class RenderHelper
{
    /** @var int|null Year component used while iterating birthdays. */
    protected ?int $byear = null;

    /** @var int|null Month component used while iterating birthdays. */
    protected ?int $bmonth = null;

    /** @var int|null Day component used while iterating birthdays. */
    protected ?int $bday = null;

    /** @var int|null Last seen family unit id while iterating anniversaries. */
    protected ?int $f_id = null;

    /**
     * Resolve one or more position ids into either a bullet-separated label
     * string, or a boolean indicating whether the member is a team leader.
     *
     * @param   string       $conPosition  Single id or comma-separated ids.
     * @param   bool         $getInt       When true, returns true if any id matches the teamleader param.
     * @param   Registry|null $params       Parameter bag (required when $getInt is true).
     *
     * @return  string|bool
     *
     * @since   2.0.0
     */
    public function getPosition(string $conPosition, bool $getInt = false, ?Registry $params = null): string|bool
    {
        $positions = [];
        $db        = Factory::getContainer()->get(DatabaseInterface::class);

        if (str_contains($conPosition, ',')) {
            foreach (explode(',', $conPosition) as $id) {
                $query = $db->getQuery(true)
                    ->select($db->quoteName(['id', 'name']))
                    ->from($db->quoteName('#__churchdirectory_position'))
                    ->where($db->quoteName('id') . ' = ' . (int) $id);

                $positions[] = $db->setQuery($query)->loadObject();
            }
        } elseif ($conPosition !== '-1' && $conPosition !== '0' && $conPosition !== '') {
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'name']))
                ->from($db->quoteName('#__churchdirectory_position'))
                ->where($db->quoteName('id') . ' = ' . (int) $conPosition);

            $positions[] = $db->setQuery($query)->loadObject();
        }

        if ($getInt) {
            $teamleaders = $params?->get('teamleaders', '');

            foreach ($positions as $position) {
                if ($position && (string) $position->id === (string) $teamleaders) {
                    return true;
                }
            }

            return false;
        }

        $results = '';
        $n       = \count($positions);
        $pi      = 1;

        foreach ($positions as $position) {
            if (!$position) {
                continue;
            }

            $results .= '&bull; ' . $position->name . ($n !== $pi ? '<br />' : '');
            $pi++;
        }

        return $results;
    }

    /**
     * Load every member tied to a family unit, optionally filtered to a single
     * family position.
     *
     * @param   int     $fuId      Family unit id.
     * @param   string  $fm        Target family position; ignored when $children is true.
     * @param   bool    $children  When true, keep all members instead of filtering by $fm.
     *
     * @return  array<int, \stdClass>
     *
     * @since   2.0.0
     */
    public function getFamilyMembers(int $fuId, string $fm = '2', bool $children = false): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('members.*')
            ->from($db->quoteName('#__churchdirectory_details', 'members'))
            ->where($db->quoteName('members.funitid') . ' = ' . (int) $fuId)
            ->order($db->quoteName('members.name') . ' DESC');

        $items = $db->setQuery($query)->loadObjectList();

        foreach ($items as $i => $item) {
            $params        = new Registry();
            $params->loadString($item->params);
            $item->params = $params;

            $attribs       = new Registry();
            $attribs->loadString($item->attribs);
            $item->attribs = $attribs;

            if ((int) $item->attribs->get('familypostion') !== (int) $fm && !$children) {
                unset($items[$i]);
            }
        }

        return array_values($items);
    }

    /**
     * Build the comma/and-separated string of children for a family.
     *
     * @param   int|array<int, \stdClass>  $families         Family unit id or an already-loaded member list.
     * @param   bool                       $from             When true, render plain names; otherwise link them.
     * @param   string|null                $oldChildrenRc    Trailing free-form text.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function getChildren(int|array $families, bool $from = false, ?string $oldChildrenRc = null): string
    {
        if (is_int($families)) {
            $families = $this->getFamilyMembers($families, '2', true);
        }

        if (!is_array($families)) {
            $families = [0 => $families];
        }

        $n    = \count($families);
        $i    = $n;
        $name = '';

        foreach ($families as $member) {
            if (!isset($member->attribs) || (int) $member->attribs->get('familypostion') !== 2) {
                $i--;
                continue;
            }

            if (($n === $i && $n < 2) || ($n === 2 && $n === $i)) {
                $name .= $this->getMemberStatus($member, $from) . ' ';
            } elseif ($n > 2 && $i > 1) {
                $name .= $this->getMemberStatus($member, $from) . ', ';
            } elseif ($i === 1 && $n >= 2) {
                $name .= '&amp; ' . $this->getMemberStatus($member, $from);
            }

            $i--;
        }

        if ($name === '' && empty($oldChildrenRc)) {
            return '';
        }

        return '<span class="jicons-text">' . Text::_('COM_CHURCHDIRECTORY_CHILDREN') . ': </span>'
            . ($name !== '' ? $name . ' ' : '') . ($oldChildrenRc ?? '');
    }

    /**
     * Resolve a member's spouse by walking the family.
     *
     * @param   int   $fuId            Family unit id.
     * @param   int   $familyPosition  Family position of the querying member.
     * @param   bool  $from            When true, render plain name; otherwise link.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function getSpouse(int $fuId, int $familyPosition, bool $from = false): string
    {
        $fm      = $familyPosition === 1 ? '0' : '1';
        $members = $this->getFamilyMembers($fuId, $fm);
        $spouse  = '';

        foreach ($members as $member) {
            $spouse = $this->getMemberStatus($member, $from);
        }

        return $spouse;
    }

    /**
     * Render the link / plain name for a member according to their mstatus.
     *
     * @param   object  $member  Member record.
     * @param   bool    $from    When true, render plain name; otherwise link.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function getMemberStatus(object $member, bool $from = false): string
    {
        $href = 'index.php?option=com_churchdirectory&view=member&id=' . (int) $member->id;
        $name = $member->name;

        return match ((string) $member->mstatus) {
            '0', '1' => $from ? $name : '<a href="' . $href . '">' . $name . '</a>',
            '2'      => $from ? $name : '<a href="' . $href . '">( ' . $name . ' )</a>',
            '3'      => $from ? $name : '<span style="color: gray;"><a href="' . $href . '">( ' . $name . ' )</a></span>',
            default  => '',
        };
    }

    /**
     * @return  int  Bootstrap span width for the requested row count.
     * @since   2.0.0
     */
    public function rowWidth(int $rowsPerPage): int
    {
        return (int) (12 / max(1, $rowsPerPage));
    }

    /**
     * Group a list of objects by a property, preserving insertion order within
     * each bucket. Returns the buckets sorted by key.
     *
     * @param   array{items: array<int, object>, field: string, description?: string}  $args
     *
     * @return  array<string, array<int, object>>
     *
     * @since   2.0.0
     */
    public function groupit(array $args): array
    {
        $items  = $args['items'] ?? [];
        $field  = $args['field'] ?? '';
        $result = [];

        foreach ($items as $item) {
            $key = !empty($item->$field) ? (string) $item->$field : 'nomatch';

            $result[$key] ??= [];
            $result[$key][] = $item;
        }

        ksort($result);

        return $result;
    }

    /**
     * Split a member's name into a `(firstname, middlename, lastname, card_name)`
     * object. Accepts both "Last, First Middle" and "First Middle Last" forms.
     *
     * @param   string  $name  Raw name.
     *
     * @return  \stdClass
     *
     * @since   2.0.0
     */
    public function getName(string $name): \stdClass
    {
        $name      = trim($name);
        $nameArray = explode(',', $name);
        $middle    = '';

        if (\count($nameArray) > 1) {
            $lastname        = trim($nameArray[0]);
            $cardName        = $lastname;
            $nameAndMid      = trim($nameArray[1]);
            $firstname       = '';

            if ($nameAndMid !== '') {
                $parts     = explode(' ', $nameAndMid);
                $firstname = $parts[0];
                $middle    = $parts[1] ?? '';
                $cardName  = $firstname . ' ' . ($middle !== '' ? $middle . ' ' : '') . $cardName;
            }
        } else {
            $parts     = explode(' ', $name);
            $middle    = \count($parts) > 2 ? $parts[1] : '';
            $firstname = array_shift($parts);
            $lastname  = \count($parts) ? end($parts) : '';
            $cardName  = $firstname
                . ($middle !== '' ? ' ' . $middle : '')
                . ($lastname !== '' ? ' ' . $lastname : '');
        }

        $result             = new \stdClass();
        $result->firstname  = $firstname;
        $result->middlename = $middle;
        $result->lastname   = $lastname ?? '';
        $result->card_name  = $cardName;

        return $result;
    }

    /**
     * Return per-day birthday entries for the configured month.
     *
     * @param   Registry  $params  Component / menu parameter bag.
     *
     * @return  array<int, array{name: string, id: int, day: int, access: int}>
     *
     * @since   2.0.0
     */
    public function getBirthdays(Registry $params): array
    {
        $user   = Factory::getApplication()->getIdentity();
        $groups = implode(',', $user ? $user->getAuthorisedViewLevels() : [1]);
        $db     = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('a.*')
            ->from($db->quoteName('#__churchdirectory_details', 'a'))
            ->where('a.access IN (' . $groups . ')')
            ->join('INNER', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->where('c.access IN (' . $groups . ')')
            ->where('a.published = 1');

        $this->applyCategoryStateJoins($query, $db);

        $nullDate = $db->quote($db->getNullDate());
        $nowDate  = $db->quote(Factory::getDate()->toSql());

        $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
            ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');

        $month = $params->get('month', date('m'));

        if ((string) $month === '0') {
            $month = date('m');
        }

        $query->where('MONTH(a.birthdate) = ' . (int) $month)
            ->where('a.birthdate != ' . $db->quote('0000-00-00'))
            ->order('DAY(a.birthdate) ASC');

        $records = $db->setQuery($query)->loadObjectList();
        $results = [];

        foreach ($records as $record) {
            [$date]                                        = explode(' ', $record->birthdate);
            [$this->byear, $this->bmonth, $this->bday]     = explode('-', $date);

            $results[] = [
                'name'   => $record->name,
                'id'     => (int) $record->id,
                'day'    => (int) $this->bday,
                'access' => (int) $record->access,
            ];
        }

        return $results;
    }

    /**
     * Return per-day anniversary entries for the configured month.
     *
     * Family rows collapse multiple members into the family record; standalone
     * members surface as individuals.
     *
     * @param   Registry  $params  Component / menu parameter bag.
     *
     * @return  array<int, array{name: string, id: int, day: int, access: int}>
     *
     * @since   2.0.0
     */
    public function getAnniversary(Registry $params): array
    {
        $user   = Factory::getApplication()->getIdentity();
        $groups = implode(',', $user ? $user->getAuthorisedViewLevels() : [1]);
        $db     = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('a.*')
            ->select('f.name as f_name, f.id as f_id')
            ->from($db->quoteName('#__churchdirectory_details', 'a'))
            ->where('a.access IN (' . $groups . ')')
            ->join('INNER', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->where('c.access IN (' . $groups . ')')
            ->where('a.published = 1')
            ->join('LEFT OUTER', $db->quoteName('#__churchdirectory_familyunit', 'f') . ' ON f.id = a.funitid');

        $this->applyCategoryStateJoins($query, $db);

        $nullDate = $db->quote($db->getNullDate());
        $nowDate  = $db->quote(Factory::getDate()->toSql());

        $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
            ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');

        $month = $params->get('month', date('m'));

        if ((string) $month === '0') {
            $month = date('m');
        }

        $query->where('MONTH(a.anniversary) = ' . (int) $month)
            ->where('a.anniversary != ' . $db->quote('0000-00-00'))
            ->order('DAY(a.anniversary) ASC');

        $records = $db->setQuery($query)->loadObjectList();
        $results = [];

        foreach ($records as $i => $record) {
            $date         = HTMLHelper::_('date', $record->anniversary, Text::_('DATE_FORMAT_LC4'), false);
            [, , $bday]   = explode('-', $date);

            if ($record->f_name && (int) $record->f_id !== $this->f_id) {
                $this->f_id = (int) $record->f_id;
                $results[]  = [
                    'name'   => $record->f_name,
                    'id'     => (int) $record->f_id,
                    'day'    => (int) $bday,
                    'access' => (int) $record->access,
                ];
            } elseif (!$record->f_name) {
                $results[] = [
                    'name'   => $record->name,
                    'id'     => (int) $record->id,
                    'day'    => (int) $bday,
                    'access' => (int) $record->access,
                ];
            } else {
                $this->f_id = null;
                unset($records[$i]);
            }
        }

        return $results;
    }

    /**
     * Render the postal address block in microformat-friendly markup.
     *
     * @since   2.0.0
     */
    public function renderAddress(object $item, Registry $params): string
    {
        $hasAny = $item->address || $item->suburb || $item->state || $item->postcode;

        if (!($params->get('address_check') > 0) || !$hasAny) {
            return '';
        }

        $html  = '<div class="cd_address">';
        $html .= '<span class="' . $params->get('marker_class') . '">' . $params->get('marker_address') . '</span>';

        if ($item->address && $params->get('dr_show_street_address')) {
            $html .= '<address><span class="street-address">' . trim(nl2br($item->address)) . '</span><br>';
        }

        if ($item->suburb && $params->get('dr_show_suburb')) {
            $html .= '<span class="locality">' . $item->suburb . '</span>';
        }

        if ($item->state && $params->get('dr_show_state')) {
            $html .= ' <span class="region">, ' . $item->state . '</span>';
        }

        if ($item->postcode && $params->get('dr_show_postcode')) {
            $html .= '<span class="postal-code"> ' . $item->postcode . '</span>';
        }

        if ($item->country && $params->get('dr_show_country')) {
            $html .= '<span class="country-name"> ' . $item->country . '</span>';
        }

        return $html . '</address></div>';
    }

    /**
     * Render the contact-details block (email, telephone, fax, mobile, webpage).
     *
     * @since   2.0.0
     */
    public function renderPhonesNumbers(object $item, Registry $params, ?object $name = null): string
    {
        $namePrefix = $name ? $name->firstname . ' : ' : '';
        $hasAny     = $item->email_to || $item->telephone || $item->fax || $item->mobile || $item->webpage
                      || ($item->spouse ?? null) || ($item->children ?? null);

        if (!($params->get('other_check') > 0) || !$hasAny) {
            return '';
        }

        $marker = '<span class="' . $params->get('marker_class') . '">';
        $html   = '<div class="cd-info">';

        if ($item->email_to && $params->get('dr_show_email')) {
            $html .= $namePrefix . $marker . $params->get('marker_email') . '&nbsp;&nbsp;' . $item->email_to . '</span>';
        }

        if ($item->telephone && $params->get('dr_show_telephone')) {
            $html .= '<br/>' . $namePrefix . $marker . $params->get('marker_telephone') . '&nbsp;&nbsp;'
                . nl2br($item->telephone) . '</span>';
        }

        if ($item->fax && $params->get('dr_show_fax')) {
            $html .= '<br/>' . $namePrefix . $marker . $params->get('marker_fax') . '&nbsp;&nbsp;'
                . nl2br($item->fax) . '</span>';
        }

        if ($item->mobile && $params->get('dr_show_mobile')) {
            $html .= '<br/>' . $namePrefix . $marker . $params->get('marker_mobile') . '&nbsp;&nbsp;'
                . nl2br($item->mobile) . '</span>';
        }

        if ($item->webpage && $params->get('dr_show_webpage')) {
            $html .= '<br/>' . $namePrefix . $marker . 'Site:&nbsp;&nbsp;<a href="'
                . $item->webpage . '" target="_blank">' . Text::_('COM_CHURCHDIRECTORY_WEBPAGE') . '</a></span>';
        }

        return $html . '</div>';
    }

    /**
     * Render the search-field form for the directory view.
     *
     * @since   2.0.0
     */
    public function getSearchField(Registry $params): string
    {
        $route  = 'index.php?option=com_churchdirectory&view=directory&layout=search';
        $params->def('field_size', 20);
        $suffix = $params->get('moduleclass_sfx');

        $input  = '<input type="text" name="filter[search]" id="filter_search" size="'
            . $params->get('field_size', 20) . '" placeholder="' . Text::_('Search') . '" data-original-title=""/>';

        $showLabel  = $params->get('show_label', 1);
        $labelClass = (!$showLabel ? 'element-invisible ' : '') . 'finder' . $suffix;
        $label      = '<label for="filter_search" class="' . $labelClass . '">'
            . $params->get('alt_label', Text::_('JSEARCH_FILTER_SUBMIT')) . '</label>';

        $output = match ($params->get('label_pos', 'left')) {
            'top'    => $label . '<br />' . $input,
            'bottom' => $input . '<br />' . $label,
            'right'  => $input . $label,
            default  => $label . ' ' . $input,
        };

        if ($params->get('show_button')) {
            $button = '<button class="btn btn-primary hasTooltip ' . $suffix . ' finder' . $suffix
                . '" type="submit" title="' . Text::_('COM_CHURCHDIRECTORY_FILTER_SUBMIT')
                . '"><span class="icon-search icon-white"></span>' . Text::_('JSEARCH_FILTER_SUBMIT') . '</button>';

            $output = match ($params->get('button_pos', 'left')) {
                'top'    => $button . '<br />' . $output,
                'bottom' => $output . '<br />' . $button,
                'right'  => $output . $button,
                default  => $button . $output,
            };
        }

        $itemid = Factory::getApplication()->getInput()->getInt('Itemid', 0);

        $render  = '<form id="com_churchdirectory_search" action="' . Route::_($route)
            . '" method="get" class="form-search"><div class="search' . $suffix . '">';
        $render .= $output;
        $render .= '<input type="hidden" name="option" value="com_churchdirectory">';
        $render .= '<input type="hidden" name="view" value="directory">';
        $render .= '<input type="hidden" name="layout" value="search">';
        $render .= '<input type="hidden" name="Itemid" value="' . $itemid . '">';
        $render .= '</div></form>';

        return $render;
    }

    /**
     * Generate a random alphanumeric password. Not cryptographically strong —
     * used only for placeholder defaults that are immediately re-prompted.
     *
     * @since   2.0.0
     */
    public function randomPassword(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';

        return substr(str_shuffle($chars), 0, $length);
    }

    /**
     * Attach the "category-and-ancestors are published" subquery to a query.
     */
    private function applyCategoryStateJoins(\Joomla\Database\QueryInterface $query, DatabaseInterface $db): void
    {
        $query->select('c.published as cat_published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');

        $subquery  = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
        $subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
        $subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');
        $subquery .= ' AND parent.published != 1 GROUP BY cat.id';

        $query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');
    }
}