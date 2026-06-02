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

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * Phase G: paginated, members-only directory list. Backs `view=members`.
 *
 * Wraps a single SELECT against `#__cwmconnect_details` filtered by:
 *  - `published = 1` AND `display_in_directory = 1` (spec §7.2)
 *  - optional search string against name/lname/surname
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

        return parent::getStoreId($id);
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
            $db->quoteName('a.spouse'),
            $db->quoteName('a.children'),
            $db->quoteName('a.fax'),
            $db->quoteName('a.misc'),
            $db->quoteName('a.anniversary'),
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
        ->where($db->quoteName('a.display_in_directory') . ' = 1')
        // Minors appear under their family unit, not as their own listing.
        ->where($db->quoteName('a.is_child') . ' = 0');

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
            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('a.lname') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('a.surname') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('a.email_to') . ' LIKE ' . $like . ')',
            );
        }

        $orderCol  = $this->state->get('list.ordering', 'a.surname');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Build a NetworkLink KML document with the user's feed token baked in.
     * Auto-creates a token if the user doesn't have one yet.
     *
     * @param   int     $userId    Joomla user ID.
     * @param   string  $username  Display name for auto-created token label.
     *
     * @return  string  Complete KML XML document.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function buildKmlFeedFile(int $userId, string $username): string
    {
        $db        = $this->getDatabase();
        $service   = new FeedTokenService($db);
        $cleartext = $this->getOrCreateToken($db, $service, $userId, $username);
        $dataUrl   = Uri::root() . 'index.php?option=com_cwmconnect&view=members&format=kml&token=' . urlencode($cleartext);

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

    /**
     * Find an active token for the user, or create one.
     *
     * @param   \Joomla\Database\DatabaseInterface  $db        Database.
     * @param   FeedTokenService                    $service   Token service.
     * @param   int                                 $userId    Joomla user ID.
     * @param   string                              $username  For auto-created label.
     *
     * @return  string  Cleartext token.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function getOrCreateToken(
        \Joomla\Database\DatabaseInterface $db,
        FeedTokenService $service,
        int $userId,
        string $username,
    ): string {
        $query = $db->createQuery()
            ->select($db->quoteName('token_hash'))
            ->from($db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->where($db->quoteName('revoked_at') . ' IS NULL')
            ->bind(':uid', $userId, ParameterType::INTEGER)
            ->setLimit(1);

        $existingHash = $db->setQuery($query)->loadResult();

        if ($existingHash) {
            $pair = $service->generate();

            $update = $db->createQuery()
                ->update($db->quoteName('#__cwmconnect_feed_tokens'))
                ->set($db->quoteName('token_hash') . ' = ' . $db->quote($pair['hash']))
                ->where($db->quoteName('user_id') . ' = :uid')
                ->where($db->quoteName('token_hash') . ' = ' . $db->quote($existingHash))
                ->bind(':uid', $userId, ParameterType::INTEGER);

            $db->setQuery($update)->execute();

            return $pair['cleartext'];
        }

        $pair = $service->generate();
        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $row = (object) [
            'user_id'    => $userId,
            'token_hash' => $pair['hash'],
            'label'      => 'Auto — ' . $username,
            'created_at' => $now,
        ];

        $db->insertObject('#__cwmconnect_feed_tokens', $row);

        return $pair['cleartext'];
    }
}
