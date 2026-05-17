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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * List model for the directory home page — featured & published members the
 * current user can read, ordered by manual ordering.
 *
 * @since  2.0.0
 */
class HomeModel extends ListModel
{
    /** @var string Model context for state caching. */
    protected $context = 'com_cwmconnect.home';

    /**
     * Auto-populate state. Captures the return URL for post-login redirects
     * and loads the active menu's params.
     *
     * @param   string|null  $ordering   Ignored — featured ordering is fixed.
     * @param   string|null  $direction  Ignored.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app    = Factory::getApplication();
        $return = $app->getInput()->get('return', $this->setReturnPage(), 'base64');

        $this->setState('return_page', base64_decode($return));
        $this->setState('params', $app->getParams());
    }

    /**
     * Load the featured member list, decoding each row's params into a Registry.
     *
     * @return  array<int, object>|false
     *
     * @since   2.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (!\is_array($items)) {
            return $items;
        }

        foreach ($items as $item) {
            $params = new Registry();
            $params->loadString((string) $item->params);
            $item->params = $params;
        }

        return $items;
    }

    /**
     * SQL: featured + published members the user can see, manual ordering.
     *
     * @return  QueryInterface
     *
     * @since   2.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $user   = Factory::getApplication()->getIdentity();
        $groups = implode(',', $user ? $user->getAuthorisedViewLevels() : [1]);

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $caseSlug    = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('a.id'), 'a.alias'], ':')
            . ' ELSE ' . $query->castAsChar('a.id') . ' END as slug';
        $caseCatslug = ' CASE WHEN ' . $query->charLength('c.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('c.id'), 'c.alias'], ':')
            . ' ELSE ' . $query->castAsChar('c.id') . ' END as catslug';

        $query->select($this->getState('list.select', 'a.*') . ', ' . $caseSlug . ', ' . $caseCatslug)
            ->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author")
            ->select('ua.email AS author_email')
            ->from($db->quoteName('#__cwmconnect_details', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ua.id = a.created_by')
            ->join('LEFT', $db->quoteName('#__users', 'uam') . ' ON uam.id = a.modified_by')
            ->where('a.access IN (' . $groups . ')')
            ->where('a.published = 1')
            ->where('a.featured = 1')
            ->order($db->escape('a.ordering') . ' ASC');

        return $query;
    }

    /**
     * Return the cached return URL (post-login redirect target), base64-encoded.
     *
     * @since   2.0.0
     */
    public function getReturnPage(): string
    {
        return base64_encode((string) $this->getState('return_page'));
    }

    /**
     * Compute a default return URL from the active Itemid when the request
     * didn't supply one.
     *
     * @since   2.0.0
     */
    public function setReturnPage(): string
    {
        $itemId = Factory::getApplication()->getInput()->getInt('Itemid');
        $suffix = $itemId ? '&Itemid=' . $itemId : '';

        return base64_encode('index.php?option=' . $this->option . '&view=home' . $suffix);
    }
}
