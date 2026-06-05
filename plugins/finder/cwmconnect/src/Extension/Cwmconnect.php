<?php

/**
 * @package    Plg_Finder_Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Plugin\Finder\Cwmconnect\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

/**
 * Smart Search adapter for com_cwmconnect members.
 *
 * Indexes member rows from #__cwmconnect_details and wires their
 * contact-detail fields (address, telephone, etc.) into the Finder's
 * meta-context system so they're searchable as structured fields.
 *
 * @since  2.0.0
 */
final class Cwmconnect extends Adapter implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /** @var string Plugin identifier used to namespace Finder tables. */
    protected $context = 'Cwmconnect';

    /** @var string Component name passed to ComponentHelper::isEnabled() and route building. */
    protected $extension = 'com_cwmconnect';

    /** @var string Result-template sublayout (resolves to tmpl/member.php). */
    protected $layout = 'member';

    /** @var string Human-readable content type, shown in Finder admin. */
    protected $type_title = 'Church Member';

    /** @var string Source table for the indexer query. */
    protected $table = '#__cwmconnect_details';

    /** @var string Column the published state lives in (default `state` doesn't apply here). */
    protected $state_field = 'published';

    /** @var bool Auto-load the plugin's language strings. */
    protected $autoloadLanguage = true;

    /**
     * Event map: hooks into the standard Finder events plus the
     * Joomla\Event\Subscriber listener that {@see Adapter} defines.
     *
     * @return  array<string, string>
     *
     * @since   2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onFinderCategoryChangeState' => 'onFinderCategoryChangeState',
            'onFinderChangeState'         => 'onFinderChangeState',
            'onFinderAfterDelete'         => 'onFinderAfterDelete',
            'onFinderBeforeSave'          => 'onFinderBeforeSave',
            'onFinderAfterSave'           => 'onFinderAfterSave',
        ]);
    }

    /**
     * No special setup required — the base Adapter handles everything we
     * need now that the route helper resolves via PSR-4.
     *
     * @since  2.0.0
     */
    protected function setup(): bool
    {
        return true;
    }

    /**
     * Fan-out for category state changes: only ours, then delegate to base.
     *
     * @since  2.0.0
     */
    public function onFinderCategoryChangeState(FinderEvent\AfterCategoryChangeStateEvent $event): void
    {
        if ($event->getExtension() === 'com_cwmconnect') {
            $this->categoryStateChange($event->getPks(), $event->getValue());
        }
    }

    /**
     * Remove from the Finder index when a member (or a directly-indexed
     * Finder row) is deleted.
     *
     * @since  2.0.0
     */
    public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();

        $id = match ($context) {
            'com_cwmconnect.member' => $table->id,
            'com_finder.index'           => $table->link_id,
            default                      => null,
        };

        if ($id !== null) {
            $this->remove($id);
        }
    }

    /**
     * Reindex after save; cascade access-level changes to existing rows when
     * the member or its category was edited (not freshly created).
     *
     * @since  2.0.0
     */
    public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
    {
        $context = $event->getContext();
        $row     = $event->getItem();
        $isNew   = $event->getIsNew();

        if ($context === 'com_cwmconnect.member') {
            if (!$isNew && $this->old_access != $row->access) {
                $this->itemAccessChange($row);
            }
            $this->reindex($row->id);
        }

        if ($context === 'com_categories.category' && !$isNew && $this->old_cataccess != $row->access) {
            $this->categoryAccessChange($row);
        }
    }

    /**
     * Pre-save: snapshot the old access level so the After hook can detect
     * a change and rerun the access cascade.
     *
     * @since  2.0.0
     */
    public function onFinderBeforeSave(FinderEvent\BeforeSaveEvent $event): void
    {
        $context = $event->getContext();
        $row     = $event->getItem();
        $isNew   = $event->getIsNew();

        if ($isNew) {
            return;
        }

        if ($context === 'com_cwmconnect.member') {
            $this->checkItemAccess($row);
        } elseif ($context === 'com_categories.category') {
            $this->checkCategoryAccess($row);
        }
    }

    /**
     * Cascade publish/unpublish state to the Finder index. Also handles the
     * "plugin disabled" case for legacy cleanup.
     *
     * @since  2.0.0
     */
    public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event): void
    {
        $context = $event->getContext();
        $pks     = $event->getPks();
        $value   = $event->getValue();

        if ($context === 'com_cwmconnect.member') {
            $this->itemStateChange($pks, $value);
        } elseif ($context === 'com_plugins.plugin' && (int) $value === 0) {
            $this->pluginDisable($pks);
        }
    }

    /**
     * Project one member row onto a Finder Result and tell the indexer how
     * to weight each meta-context (address, phone, etc.).
     *
     * @since  2.0.0
     */
    protected function index(Result $item): void
    {
        if (ComponentHelper::isEnabled($this->extension) === false) {
            return;
        }

        $item->setLanguage();
        $item->context = 'com_cwmconnect.member';
        $item->params  = new Registry($item->params);

        $item->url   = $this->getUrl($item->id, $this->extension, $this->layout);
        $item->route = RouteHelper::getMemberRoute($item->slug, (int) $item->catid, $item->language);

        $title = $this->getItemMenuTitle($item->url);

        if (!empty($title) && $this->params->get('use_menu_title', true)) {
            $item->title = $title;
        }

        // Map per-field display flags onto Finder META_CONTEXT instructions.
        // The legacy code defaulted every flag to true; preserve that.
        $fields = [
            'show_street_address' => 'address',
            'show_suburb'         => 'city',
            'show_state'          => 'region',
            'show_country'        => 'country',
            'show_postcode'       => 'zip',
            'show_telephone'      => 'telephone',
            'show_fax'            => 'fax',
            'show_email'          => 'email',
            'show_mobile'         => 'mobile',
            'show_webpage'        => 'webpage',
            'show_children'       => 'children',
        ];

        foreach ($fields as $param => $context) {
            if ($item->params->get($param, true)) {
                $item->addInstruction(Indexer::META_CONTEXT, $context);
            }
        }

        $item->addInstruction(Indexer::META_CONTEXT, 'user');

        $item->addTaxonomy('Type', 'Church Member');
        $item->addTaxonomy('Category', $item->category, $item->cat_state, $item->cat_access);
        $item->addTaxonomy('Language', $item->language);

        if (!empty($item->region) && $this->params->get('tax_add_region', true)) {
            $item->addTaxonomy('Region', $item->region);
        }

        if (!empty($item->country) && $this->params->get('tax_add_country', true)) {
            $item->addTaxonomy('Country', $item->country);
        }

        Helper::getContentExtras($item);

        $this->indexer->index($item);
    }

    /**
     * Source-data query: pull every member row and shape its column aliases
     * to match what {@see index()} expects (city/region/zip/email/etc.).
     *
     * @since  2.0.0
     */
    protected function getListQuery($query = null): QueryInterface
    {
        $db = $this->getDatabase();

        $query = $query instanceof QueryInterface ? $query : $db->createQuery()
            ->select('a.id, a.name AS title, a.alias, a.address, a.created AS start_date')
            ->select('a.created_by_alias, a.modified, a.modified_by')
            ->select('a.metakey, a.metadesc, a.metadata, a.language')
            ->select('a.sortname1, a.sortname2, a.sortname3')
            ->select('a.publish_up AS publish_start_date, a.publish_down AS publish_end_date')
            ->select('a.suburb AS city, a.state AS region, a.country, a.postcode AS zip')
            ->select('a.telephone, a.fax, a.misc AS summary, a.email_to AS email, a.mobile')
            ->select('a.webpage, a.access, a.published AS state, a.ordering, a.params, a.catid')
            ->select('c.title AS category, c.published AS cat_state, c.access AS cat_access');

        $caseSlug    = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('a.id'), 'a.alias'], ':')
            . ' ELSE ' . $query->castAsChar('a.id') . ' END as slug';
        $caseCatslug = ' CASE WHEN ' . $query->charLength('c.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('c.id'), 'c.alias'], ':')
            . ' ELSE ' . $query->castAsChar('c.id') . ' END as catslug';

        $query->select($caseSlug)
            ->select($caseCatslug)
            ->select('u.name')
            ->from($db->quoteName('#__cwmconnect_details', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = a.user_id')
            ->where($db->quoteName('a.display_in_directory') . ' = 1');

        return $query;
    }
}
