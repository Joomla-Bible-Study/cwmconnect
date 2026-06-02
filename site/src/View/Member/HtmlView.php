<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Member;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\HouseholdVisibility;
use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use CWM\Component\Cwmconnect\Site\Model\CategoryModel;
use CWM\Component\Cwmconnect\Site\Model\MemberModel;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

/**
 * HTML view for a single member's profile page.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var \Joomla\Registry\Registry|null */
    protected mixed $state = null;

    /** @var Form|null */
    protected ?Form $form = null;

    /** @var object|false */
    protected mixed $item = false;

    /** @var object|false Alias of $item for legacy template compatibility. */
    protected mixed $member = false;

    /** @var array<int, object> Sibling members in the same category. */
    protected array $members = [];

    /** @var Registry */
    protected Registry $params;

    /** @var User|null */
    protected ?User $user = null;

    /** @var string Sanitized page-class suffix. */
    protected string $pageclass_sfx = '';

    /** @var string Empty placeholder used by legacy templates. */
    protected string $return = '';

    /**
     * Phase G + spec §7.2: scope of the current viewer relative to the
     * target member's household. Drives whether children's names render
     * (same household) or only an aggregate count (everyone else).
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    public string $householdScope = HouseholdVisibility::OTHER_HOUSEHOLD;

    /**
     * Phase G: members sharing the target's household. Includes hidden /
     * child rows ONLY when the viewer is in the same household; for all
     * other viewers we list adult members and surface a count for the
     * hidden ones via {@see $hiddenHouseholdCount}.
     *
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $householdMembers = [];

    /**
     * Phase G: count of hidden household members the current viewer
     * isn't allowed to see by name. Used for the "…and N children"
     * aggregate per spec §7.2.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    public int $hiddenHouseholdCount = 0;

    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        /** @var MemberModel $model */
        $model      = $this->getModel();
        $state      = $model->getState();
        $item       = $model->getItem();
        $this->form = $model->getForm();

        if (!$item) {
            // Nothing to show — an unknown/missing id, a member who isn't
            // listed, or a logged-in viewer whose account has no member
            // profile linked. Send them back to the directory with a friendly
            // notice rather than a raw 404 error page.
            $requestedId = (int) $model->getState('member.id', 0);
            $app->enqueueMessage(
                Text::_(
                    $requestedId > 0
                        ? 'COM_CWMCONNECT_MEMBER_PROFILE_UNAVAILABLE'
                        : 'COM_CWMCONNECT_MEMBER_NO_LINKED_PROFILE',
                ),
                'info',
            );
            $app->redirect(Route::_('index.php?option=com_cwmconnect&view=members', false));

            return;
        }

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $params = ComponentHelper::getParams('com_cwmconnect');
        $params->merge($item->params);

        $groups = $user ? $user->getAuthorisedViewLevels() : [1];

        // The category access check only applies to categorised members. PC-
        // synced members carry catid=0 (no Joomla category), so the LEFT join
        // leaves category_access NULL — that must not deny access; only the
        // member's own access level gates an uncategorised row.
        $categoryDenied = (int) $item->catid > 0 && !\in_array($item->category_access, $groups, false);

        if (!\in_array($item->access, $groups, false) || $categoryDenied) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $app->setHeader('status', 403, true);
            throw new GenericDataException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        // Sibling list — same-category members for the "other members" widget.
        $categoryModel = $app->bootComponent('com_cwmconnect')
            ->getMVCFactory()
            ->createModel('Category', 'Site', ['ignore_request' => true]);

        if ($categoryModel instanceof CategoryModel) {
            $categoryModel->setState('category.id', $item->catid);
            $categoryModel->setState('list.ordering', 'a.name');
            $categoryModel->setState('list.direction', 'asc');
            $categoryModel->setState('filter.published', 1);
            $this->members = $categoryModel->getItems() ?: [];
        }

        if ($item->email_to && $params->get('show_email')) {
            $item->email_to = HTMLHelper::_('email.cloak', $item->email_to);
        }

        $this->loadHouseholdContext($item, $user);

        $hasAddress = !empty($item->address) || !empty($item->suburb) || !empty($item->state)
            || !empty($item->country) || !empty($item->postcode);

        $params->set(
            'address_check',
            (int) (
                $hasAddress && (
                    $params->get('show_street_address') || $params->get('show_suburb')
                    || $params->get('show_state') || $params->get('show_postcode')
                    || $params->get('show_country')
                )
            )
        );

        $this->applyIconMarkers($params);

        if ($params->get('show_cwmconnect_list') && \count($this->members) > 1) {
            foreach ($this->members as $contact) {
                $contact->link = Route::_(RouteHelper::getMemberRoute($contact->slug, $contact->catid));
            }

            $item->link = Route::_(RouteHelper::getMemberRoute($item->slug, $item->catid));
        }

        PluginHelper::importPlugin('content');
        $offset = $state->get('list.offset');

        $item->text = !empty($item->misc) ? $item->misc : null;
        $app->triggerEvent('onContentPrepare', ['com_cwmconnect.member', &$item, &$params, $offset]);

        $item->event                     = new \stdClass();
        $item->event->afterDisplayTitle  = trim(implode("\n", $app->triggerEvent('onContentAfterTitle', ['com_cwmconnect.member', &$item, &$params, $offset]) ?: []));
        $item->event->beforeDisplayContent = trim(implode("\n", $app->triggerEvent('onContentBeforeDisplay', ['com_cwmconnect.member', &$item, &$params, $offset]) ?: []));
        $item->event->afterDisplayContent  = trim(implode("\n", $app->triggerEvent('onContentAfterDisplay', ['com_cwmconnect.member', &$item, &$params, $offset]) ?: []));

        if ($item->text) {
            $item->misc = $item->text;
        }

        $this->pageclass_sfx = htmlspecialchars((string) $params->get('pageclass_sfx', ''));
        $this->member        = $item;
        $this->item          = $item;
        $this->params        = $params;
        $this->state         = $state;
        $this->user          = $user;

        $item->tags = new TagsHelper();
        $item->tags->getItemTags('com_cwmconnect.member', $this->item->id);

        // Honor alternate menu-item layouts.
        $active = $app->getMenu()?->getActive();

        if (
            !$active
            || !str_contains((string) $active->link, 'view=member')
            || !str_contains((string) $active->link, '&id=' . (string) $this->item->id)
        ) {
            if ($layout = $params->get('cwmconnect_layout')) {
                $this->setLayout($layout);
            }
        } elseif (isset($active->query['layout'])) {
            $this->setLayout($active->query['layout']);
        }

        $model->hit();
        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Set page title, description, keywords, and breadcrumb pathway.
     *
     * @since  2.0.0
     */
    protected function prepareDocument(): void
    {
        $app     = Factory::getApplication();
        $pathway = $app->getPathway();
        $menu    = $app->getMenu()?->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_CWMCONNECT_DEFAULT_PAGE_TITLE'));
        }

        $title = (string) $this->params->get('page_title', '');
        $id    = (int) ($menu->query['id'] ?? 0);

        if (
            $menu
            && (
                ($menu->query['option'] ?? '') !== 'com_cwmconnect'
                || ($menu->query['view']   ?? '') !== 'member'
                || $id !== (int) $this->item->id
            )
        ) {
            if (!empty($this->item->name)) {
                $title = $this->item->name;
            }

            $path     = [['title' => $this->member->name, 'link' => '']];
            $category = Categories::getInstance('Cwmconnect')->get($this->member->catid);

            while (
                $category
                && (
                    ($menu->query['option'] ?? '') !== 'com_cwmconnect'
                    || ($menu->query['view']   ?? '') === 'member'
                    || $id !== (int) $category->id
                )
                && $category->id > 1
            ) {
                $path[]   = [
                    'title' => $category->title,
                    'link'  => RouteHelper::getCategoryRoute($this->member->catid),
                ];
                $category = $category->getParent();
            }

            foreach (array_reverse($path) as $crumb) {
                $pathway->addItem($crumb['title'], $crumb['link']);
            }
        }

        if ($title === '') {
            $title = $app->get('sitename');
        } elseif ((int) $app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ((int) $app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        if ($title === '') {
            $title = $this->item->name;
        }

        $this->getDocument()->setTitle($title);

        $metadesc = $this->item->metadesc ?: $this->params->get('menu-meta_description');

        if ($metadesc) {
            $this->getDocument()->setDescription($metadesc);
        }

        $metakey = $this->item->metakey ?: $this->params->get('menu-meta_keywords');

        if ($metakey) {
            $this->getDocument()->setMetaData('keywords', $metakey);
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }

        if ($this->item->metadata instanceof Registry) {
            foreach ($this->item->metadata->toArray() as $k => $v) {
                if ($v) {
                    $this->getDocument()->setMetaData($k, $v);
                }
            }
        }
    }

    /**
     * Resolve the per-field icon/text markers used by the contact-detail templates.
     *
     * @since  2.0.0
     */
    private function applyIconMarkers(Registry $params): void
    {
        switch ((int) $params->get('cwmconnect_icons')) {
            case 1:
                $params->set('marker_address', Text::_('COM_CWMCONNECT_ADDRESS') . ': ');
                $params->set('marker_email', Text::_('JGLOBAL_EMAIL') . ': ');
                $params->set('marker_telephone', Text::_('COM_CWMCONNECT_TELEPHONE') . ': ');
                $params->set('marker_fax', Text::_('COM_CWMCONNECT_FAX') . ': ');
                $params->set('marker_mobile', Text::_('COM_CWMCONNECT_MOBILE') . ': ');
                $params->set('marker_misc', Text::_('COM_CWMCONNECT_OTHER_INFORMATION') . ': ');
                $params->set('marker_class', 'jicons-text');
                break;

            case 2:
                foreach (['marker_address', 'marker_email', 'marker_telephone', 'marker_fax', 'marker_mobile', 'marker_misc'] as $key) {
                    $params->set($key, '');
                }
                $params->set('marker_class', 'jicons-none');
                break;

            default:
                $this->applyIconMarkersImage($params);
                break;
        }
    }

    /**
     * Default branch: render each marker as an HTMLHelper image lookup.
     */
    private function applyIconMarkersImage(Registry $params): void
    {
        $icons = [
            'marker_address'   => ['icon_address',   'con_address.png',  'COM_CWMCONNECT_ADDRESS'],
            'marker_email'     => ['icon_email',     'emailButton.png',  'JGLOBAL_EMAIL'],
            'marker_telephone' => ['icon_telephone', 'con_tel.png',      'COM_CWMCONNECT_TELEPHONE'],
            'marker_fax'       => ['icon_fax',       'con_fax.png',      'COM_CWMCONNECT_FAX'],
            'marker_misc'      => ['icon_misc',      'con_info.png',     'COM_CWMCONNECT_OTHER_INFORMATION'],
            'marker_mobile'    => ['icon_mobile',    'con_mobile.png',   'COM_CWMCONNECT_MOBILE'],
        ];

        foreach ($icons as $marker => [$iconKey, $default, $alt]) {
            $custom = (string) $params->get($iconKey, '');
            $params->set(
                $marker,
                $custom !== ''
                    ? HTMLHelper::_('image', $custom, Text::_($alt) . ': ', null, false)
                    : HTMLHelper::_('image', 'contacts/' . $default, Text::_($alt) . ': ', null, true)
            );
        }

        $params->set('marker_class', 'jicons-icons');
    }

    /**
     * Phase G + spec §7.2: load the household block for the profile view.
     *
     * Resolves the viewer's `funitid` from their `user_id` link (if any),
     * compares it against the target's household, and populates
     *  - `$householdScope` — for templates that want the raw enum
     *  - `$householdMembers` — siblings to render by name. Includes
     *    hidden / child rows ONLY when the viewer is in the same
     *    household per spec §7.2.
     *  - `$hiddenHouseholdCount` — aggregate count for "…and N children"
     *    when the viewer is NOT in the same household.
     *
     * @param   object               $item  The loaded target member.
     * @param   \Joomla\CMS\User\User|null  $user  The current viewer (may be null).
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function loadHouseholdContext(object $item, ?User $user): void
    {
        $targetHouseholdId = (int) ($item->funitid ?? 0);
        $viewerHouseholdId = null;

        $viewerUserId = (int) ($user?->id ?? 0);

        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        if ($viewerUserId > 0) {
            $query = $db->createQuery()
                ->select($db->quoteName('funitid'))
                ->from($db->quoteName('#__cwmconnect_details'))
                ->where($db->quoteName('user_id') . ' = ' . $viewerUserId);

            $viewerHouseholdId = (int) ($db->setQuery($query)->loadResult() ?? 0) ?: null;
        }

        $this->householdScope = HouseholdVisibility::scope($viewerHouseholdId, $targetHouseholdId ?: null);

        if ($targetHouseholdId === 0) {
            return;
        }

        $sameHousehold = $this->householdScope === HouseholdVisibility::SAME_HOUSEHOLD;

        $query = $db->createQuery()
            ->select([
                $db->quoteName('id'),
                $db->quoteName('name'),
                $db->quoteName('lname'),
                $db->quoteName('surname'),
                $db->quoteName('alias'),
                $db->quoteName('image'),
                $db->quoteName('display_in_directory'),
            ])
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('funitid') . ' = ' . $targetHouseholdId)
            ->where($db->quoteName('published') . ' IN (1, 2)')
            ->where($db->quoteName('id') . ' <> ' . (int) $item->id);

        if (!$sameHousehold) {
            // Out-of-household viewers don't see hidden rows by name.
            $query->where($db->quoteName('display_in_directory') . ' = 1');
        }

        $query->order($db->quoteName('surname') . ' ASC, ' . $db->quoteName('name') . ' ASC');

        $rows = $db->setQuery($query)->loadObjectList() ?: [];

        $this->householdMembers = $rows;

        if (!$sameHousehold) {
            $hiddenQuery = $db->createQuery()
                ->select('COUNT(*)')
                ->from($db->quoteName('#__cwmconnect_details'))
                ->where($db->quoteName('funitid') . ' = ' . $targetHouseholdId)
                ->where($db->quoteName('published') . ' IN (1, 2)')
                ->where($db->quoteName('display_in_directory') . ' = 0')
                ->where($db->quoteName('id') . ' <> ' . (int) $item->id);

            $this->hiddenHouseholdCount = (int) ($db->setQuery($hiddenQuery)->loadResult() ?? 0);
        }
    }
}
