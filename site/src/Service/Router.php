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
 * View hierarchy (parent → child):
 *   categories → category → member
 *
 * Plus three flat views: home, directory, featured.
 *
 * @since  2.0.0
 */
class Router extends RouterView
{
    /** @var bool When true, drop numeric ids from segments and rely on aliases (sef_ids param). */
    protected $noIDs = false;

    private CategoryFactoryInterface $categoryFactory;

    private DatabaseInterface $db;

    public function __construct(
        SiteApplication $app,
        AbstractMenu $menu,
        CategoryFactoryInterface $categoryFactory,
        DatabaseInterface $db,
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->db              = $db;
        $this->noIDs           = (bool) ComponentHelper::getParams('com_cwmconnect')->get('sef_ids');

        $categories = new RouterViewConfiguration('categories')->setKey('id');
        $this->registerView($categories);

        $category = new RouterViewConfiguration('category')
            ->setKey('id')
            ->setParent($categories, 'catid')
            ->setNestable();
        $this->registerView($category);

        $member = new RouterViewConfiguration('member')
            ->setKey('id')
            ->setParent($category, 'catid');
        $this->registerView($member);

        $this->registerView(new RouterViewConfiguration('featured'));
        $this->registerView(new RouterViewConfiguration('directory'));
        $this->registerView(new RouterViewConfiguration('home'));

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Segments for a category — the full ancestor path so nested categories
     * round-trip cleanly.
     *
     * @param   int|string                   $id     Category id.
     * @param   array<string, mixed>         $query  The (mutable) URL query.
     *
     * @return  array<int|string, string>
     *
     * @since   2.0.0
     */
    public function getCategorySegment(int|string $id, array $query): array
    {
        $category = $this->categoryFactory
            ->createCategory(['extension' => 'com_cwmconnect'])
            ->get($id);

        if (!$category) {
            return [];
        }

        $path    = array_reverse($category->getPath(), true);
        $path[0] = '1:root';

        if ($this->noIDs) {
            foreach ($path as &$segment) {
                [, $segment] = explode(':', $segment, 2);
            }
        }

        return $path;
    }

    /** Alias used by RouterView to resolve the categories-view child segment. */
    public function getCategoriesSegment(int|string $id, array $query): array
    {
        return $this->getCategorySegment($id, $query);
    }

    /**
     * Segment for a member — always `id:alias`, dropping the id when noIDs is on.
     *
     * @return  array<int|string, string>
     *
     * @since   2.0.0
     */
    public function getMemberSegment(int|string $id, array $query): array
    {
        if (!str_contains((string) $id, ':')) {
            $dbquery = $this->db->getQuery(true)
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
     * Resolve a category segment back to its id by walking the parent's children.
     *
     * @return  int|false
     *
     * @since   2.0.0
     */
    public function getCategoryId(string $segment, array $query): int|false
    {
        if (!isset($query['id'])) {
            return false;
        }

        $category = $this->categoryFactory
            ->createCategory(['extension' => 'com_cwmconnect'])
            ->get($query['id']);

        if (!$category) {
            return false;
        }

        foreach ($category->getChildren() as $child) {
            if ($this->noIDs) {
                if ($child->alias === $segment) {
                    return (int) $child->id;
                }
            } elseif ((int) $child->id === (int) $segment) {
                return (int) $child->id;
            }
        }

        return false;
    }

    /** Alias used by RouterView to resolve the categories-view child segment. */
    public function getCategoriesId(string $segment, array $query): int|false
    {
        return $this->getCategoryId($segment, $query);
    }

    /**
     * Resolve a member segment back to its id. Looks up alias-keyed rows when
     * noIDs is on; falls back to the leading integer otherwise.
     *
     * @return  int
     *
     * @since   2.0.0
     */
    public function getMemberId(string $segment, array $query): int
    {
        if ($this->noIDs) {
            $dbquery = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__cwmconnect_details'))
                ->where($this->db->quoteName('alias') . ' = :alias')
                ->where($this->db->quoteName('catid') . ' = :catid')
                ->bind(':alias', $segment)
                ->bind(':catid', $query['id'], ParameterType::INTEGER);

            return (int) $this->db->setQuery($dbquery)->loadResult();
        }

        return (int) $segment;
    }
}