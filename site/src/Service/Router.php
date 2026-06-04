<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Service;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Site router for com_cwmconnect.
 *
 * Flat scheme (Phase 1 of the legacy-view retirement): the v2 directory is
 * menu-driven (`members` / `myprofile` / `households`), and a single id-keyed
 * `profile` view carries the per-member SEF segment. The legacy
 * categories→category→member hierarchy and the home/directory/featured views
 * were dropped here ahead of deleting those view stacks.
 *
 * @since  2.0.0
 */
class Router extends RouterView
{
    /** @var bool When true, drop numeric ids from segments and rely on aliases (sef_ids param). */
    protected $noIDs = false;

    private DatabaseInterface $db;

    /**
     * The 4-arg signature is fixed by the core RouterFactory, which always
     * calls `new Router($app, $menu, $categoryFactory, $db)` positionally. The
     * category factory is no longer used (the category hierarchy was dropped in
     * Phase 1) but must stay in the signature so `$db` binds correctly.
     */
    public function __construct(
        SiteApplication $app,
        AbstractMenu $menu,
        CategoryFactoryInterface $categoryFactory,
        DatabaseInterface $db,
    ) {
        $this->db    = $db;
        $this->noIDs = (bool) ComponentHelper::getParams('com_cwmconnect')->get('sef_ids');

        // Single-member public profile, reached from the directory list.
        $this->registerView(new RouterViewConfiguration('profile')->setKey('id'));

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Segment for a profile — always `id:alias`, dropping the id when noIDs is on.
     *
     * @param   int|string            $id     Member id (optionally `id:alias`).
     * @param   array<string, mixed>  $query  The (mutable) URL query.
     *
     * @return  array<int|string, string>
     *
     * @since   2.0.0
     */
    public function getProfileSegment(int|string $id, array $query): array
    {
        if (!str_contains((string) $id, ':')) {
            $dbquery = $this->db->createQuery()
                ->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__cwmconnect_details'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);

            $alias = (string) $this->db->setQuery($dbquery)->loadResult();
            $id   .= ':' . $alias;
        }

        if ($this->noIDs) {
            [$rawId, $segment] = explode(':', (string) $id, 2);

            return [$rawId => $segment];
        }

        return [(int) $id => (string) $id];
    }

    /**
     * Resolve a profile segment back to its member id. Looks up the alias when
     * noIDs is on; otherwise takes the leading integer.
     *
     * @param   string                $segment  The URL segment.
     * @param   array<string, mixed>  $query    The (mutable) URL query.
     *
     * @return  int
     *
     * @since   2.0.0
     */
    public function getProfileId(string $segment, array $query): int
    {
        if ($this->noIDs) {
            $dbquery = $this->db->createQuery()
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__cwmconnect_details'))
                ->where($this->db->quoteName('alias') . ' = :alias')
                ->bind(':alias', $segment);

            return (int) $this->db->setQuery($dbquery)->loadResult();
        }

        return (int) $segment;
    }
}
