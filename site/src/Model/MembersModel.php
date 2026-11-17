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

use CWM\Component\Cwmconnect\Administrator\Service\Visibility\DirectoryPrivacy;
use CWM\Component\Cwmconnect\Administrator\Service\Visibility\DirectoryViewer;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\QueryInterface;

/**
 * Phase G: paginated, members-only directory list. Backs `view=members`.
 *
 * Wraps a single SELECT against `#__cwmconnect_details` filtered by:
 *  - `published = 1` AND `display_in_directory = 1` (spec §7.2); church
 *    leadership, with the "See all member details" permission, also sees
 *    the members who hid themselves
 *  - optional search string against name/lname/surname, and email unless
 *    the member hid it
 *
 * Each row then has what the viewer may not see blanked
 * ({@see DirectoryPrivacy}), so the pages, the PDF and the map feed built from
 * this list all follow members' choices.
 *  - optional category / dirheader / household filters
 *
 * Photos resolve to `media/com_cwmconnect/photos/{image}` per the Phase E
 * cache; the template falls back to an initials placeholder when `image`
 * is empty.
 *
 * @since  __DEPLOY_VERSION__
 */
class MembersModel extends ListModel
{
    /**
     * Viewers already looked up, by user id ('session' for the current user).
     *
     * @var    array<int|string, DirectoryViewer>
     * @since  __DEPLOY_VERSION__
     */
    private array $viewers = [];

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'lname', 'a.lname',
                'surname', 'a.surname',
                'catid', 'a.catid',
                'kmlid', 'a.kmlid',
                'funitid', 'a.funitid',
                'sortname1', 'a.sortname1',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.surname', $direction = 'asc'): void
    {
        $app = Factory::getApplication();

        $this->setState('filter.search', $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.category_id', (int) $app->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', 0, 'int'));
        $this->setState('filter.dirheader_id', (int) $app->getUserStateFromRequest($this->context . '.filter.dirheader_id', 'filter_dirheader_id', 0, 'int'));
        $this->setState('filter.household_id', (int) $app->getUserStateFromRequest($this->context . '.filter.household_id', 'filter_household_id', 0, 'int'));
        $this->setState('list.layout', $app->getUserStateFromRequest($this->context . '.list.layout', 'layout_mode', 'grid', 'cmd'));

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.dirheader_id');
        $id .= ':' . $this->getState('filter.household_id');
        $id .= ':' . $this->getState('viewer.user_id');

        return parent::getStoreId($id);
    }

    /**
     * Who the list is for. The current user, unless `viewer.user_id` names
     * someone else: a map feed's token owner, who comes without a session.
     *
     * @return  DirectoryViewer
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getViewer(): DirectoryViewer
    {
        $userId = $this->getState('viewer.user_id');

        return $this->viewers[$userId === null ? 'session' : (int) $userId] ??= $userId === null
            ? DirectoryViewer::forUser($this->getCurrentUser(), $this->getDatabase())
            : DirectoryViewer::forUserId((int) $userId, $this->getDatabase());
    }

    /**
     * The members on the list, with what the viewer may not see blanked.
     *
     * @return  array<int, object>|false
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function getItems(): array|false
    {
        $store = $this->getStoreId();

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $items = parent::getItems();

        if ($items) {
            new DirectoryPrivacy($this->getDatabase())->apply($items, $this->getViewer());
        }

        return $this->cache[$store] = $items;
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.name'),
            $db->quoteName('a.lname'),
            $db->quoteName('a.surname'),
            $db->quoteName('a.fname'),
            $db->quoteName('a.nickname'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.email_to'),
            $db->quoteName('a.telephone'),
            $db->quoteName('a.mobile'),
            $db->quoteName('a.image'),
            $db->quoteName('a.catid'),
            $db->quoteName('a.kmlid'),
            $db->quoteName('a.funitid'),
            $db->quoteName('a.sortname1'),
            $db->quoteName('a.published'),
            $db->quoteName('a.lat'),
            $db->quoteName('a.lng'),
            $db->quoteName('a.address'),
            $db->quoteName('a.suburb'),
            $db->quoteName('a.state'),
            $db->quoteName('a.postcode'),
            $db->quoteName('a.country'),
            $db->quoteName('a.con_position'),
            $db->quoteName('a.is_board'),
            $db->quoteName('a.is_leader'),
            $db->quoteName('a.pc_positions'),
            $db->quoteName('a.pc_office_role'),
            $db->quoteName('a.pc_social'),
            $db->quoteName('a.is_child'),
            $db->quoteName('a.spouse'),
            $db->quoteName('a.children'),
            $db->quoteName('a.fax'),
            $db->quoteName('a.misc'),
            $db->quoteName('a.anniversary'),
            $db->quoteName('a.hidden_fields'),
            $db->quoteName('a.display_in_directory'),
        ])
        ->select($db->quoteName('c.title', 'category_title'))
        ->select($db->quoteName('c.params', 'category_params'))
        ->select($db->quoteName('d.name', 'dirheader_name'))
        ->select($db->quoteName('fu.name', 'household_name'))
        ->from($db->quoteName('#__cwmconnect_details', 'a'))
        ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
        ->join('LEFT', $db->quoteName('#__cwmconnect_dirheader', 'd') . ' ON d.id = a.kmlid')
        ->join('LEFT', $db->quoteName('#__cwmconnect_familyunit', 'fu') . ' ON fu.id = a.funitid')
        ->where($db->quoteName('a.published') . ' = 1')
        // Minors appear under their family unit, not as their own listing.
        ->where($db->quoteName('a.is_child') . ' = 0');

        // Church leadership sees the members who hid themselves too.
        $seesAll = $this->getViewer()->seesAll;

        if (!$seesAll) {
            $query->where($db->quoteName('a.display_in_directory') . ' = 1');
        }

        if ($catId = (int) $this->getState('filter.category_id')) {
            $query->where($db->quoteName('a.catid') . ' = ' . $catId);
        }

        if ($dirId = (int) $this->getState('filter.dirheader_id')) {
            $query->where($db->quoteName('a.kmlid') . ' = ' . $dirId);
        }

        if ($huId = (int) $this->getState('filter.household_id')) {
            $query->where($db->quoteName('a.funitid') . ' = ' . $huId);
        }

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%');

            // A hidden email can't be found by searching for it, or the
            // search would confirm it.
            $email = $db->quoteName('a.email_to') . ' LIKE ' . $like;

            if (!$seesAll) {
                $email = '(' . $email . " AND FIND_IN_SET('email', " . $db->quoteName('a.hidden_fields') . ') = 0)';
            }

            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('a.lname') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('a.surname') . ' LIKE ' . $like
                . ' OR ' . $email . ')',
            );
        }

        $orderCol  = $this->state->get('list.ordering', 'a.surname');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Build a NetworkLink KML document that auto-refreshes the directory feed
     * for an already-issued token. Token creation lives in
     * {@see \CWM\Component\Cwmconnect\Site\Model\MyprofileModel::createFeed()};
     * this is purely the wrapper document Google Earth polls.
     *
     * @param   string  $cleartext  The feed token to bake into the refresh URL.
     *
     * @return  string  Complete KML XML document.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function buildNetworkLinkDocument(string $cleartext): string
    {
        $dataUrl = Uri::root() . 'index.php?option=com_cwmconnect&view=members&format=kml&token=' . urlencode($cleartext);

        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
        $lines[] = '<Document>';
        $lines[] = '  <name>' . $esc(Text::_('COM_CWMCONNECT_KML_DOCUMENT_NAME')) . '</name>';
        $lines[] = '  <NetworkLink>';
        $lines[] = '    <name>' . $esc(Text::_('COM_CWMCONNECT_KML_NETWORKLINK_NAME')) . '</name>';
        $lines[] = '    <refreshVisibility>1</refreshVisibility>';
        $lines[] = '    <Link>';
        $lines[] = '      <href>' . $esc($dataUrl) . '</href>';
        $lines[] = '      <refreshMode>onInterval</refreshMode>';
        $lines[] = '      <refreshInterval>900</refreshInterval>';
        $lines[] = '    </Link>';
        $lines[] = '  </NetworkLink>';
        $lines[] = '</Document>';
        $lines[] = '</kml>';

        return implode("\n", $lines);
    }
}
