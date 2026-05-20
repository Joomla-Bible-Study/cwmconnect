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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Users\Administrator\Model\UserModel;
use Joomla\Registry\Registry;

/**
 * Site member item model — loads the directory record, the linked user's
 * profile/articles, and serves the enquiry form.
 *
 * @since  2.0.0
 */
class MemberModel extends FormModel
{
    /** @var string Model context for state caching. */
    protected $context = 'com_cwmconnect.member';

    /** @var array<int, object|false>|null Per-id item cache. */
    protected ?array $item = null;

    /** @var object|null Extended member info (articles + profile). */
    protected ?object $member = null;

    /**
     * Auto-populate state from request and the "non-editors see published" gate.
     *
     * @since  2.0.0
     */
    protected function populateState(): void
    {
        $app = Factory::getApplication();

        $this->setState('member.id', $app->getInput()->getInt('id'));
        $this->setState('params', $app->getParams());

        $user = $app->getIdentity();

        if ($user && !$user->authorise('core.edit.state', 'com_cwmconnect') && !$user->authorise('core.edit', 'com_cwmconnect')) {
            $this->setState('filter.published', 1);
            $this->setState('filter.archived', 2);
        }
    }

    /**
     * Load the enquiry form. Removes the optional `member_email_copy` field
     * when the active member's params don't enable the copy-back feature.
     *
     * @return  Form|false
     *
     * @since   2.0.0
     */
    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm('com_cwmconnect.member', 'member', ['control' => 'jform', 'load_data' => true]);

        if (empty($form)) {
            return false;
        }

        $id = (int) $this->getState('member.id');

        if ($id && isset($this->item[$id])) {
            $params = $this->getState('params');
            $params->merge($this->item[$id]->params);

            if (!$params->get('show_email_copy', 0)) {
                $form->removeField('member_email_copy');
            }
        }

        return $form;
    }

    /**
     * Restore in-flight enquiry form data from user state.
     *
     * @since   2.0.0
     */
    protected function loadFormData(): array
    {
        $data = (array) Factory::getApplication()->getUserState('com_cwmconnect.member.data', []);
        $this->preprocessData('com_cwmconnect.member', $data);

        return $data;
    }

    /**
     * Load the member record, decode all Registry-encoded fields, apply the
     * published filter, and attach articles + profile.
     *
     * @return  object|false  The member item, or false when not found / not visible.
     *
     * @since   2.0.0
     */
    public function &getItem($pk = null)
    {
        $pk = $pk ?: (int) $this->getState('member.id');
        $this->item ??= [];

        if (!isset($this->item[$pk])) {
            try {
                $app   = Factory::getApplication();
                $db    = $this->getDatabase();
                $query = $db->getQuery(true);

                $caseSlug    = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0')
                    . ' THEN ' . $query->concatenate([$query->castAsChar('a.id'), 'a.alias'], ':')
                    . ' ELSE ' . $query->castAsChar('a.id') . ' END as slug';
                $caseCatslug = ' CASE WHEN ' . $query->charLength('c.alias', '!=', '0')
                    . ' THEN ' . $query->concatenate([$query->castAsChar('c.id'), 'c.alias'], ':')
                    . ' ELSE ' . $query->castAsChar('c.id') . ' END as catslug';

                $query->select($this->getState('item.select', 'a.*') . ', ' . $caseSlug . ', ' . $caseCatslug)
                    ->from($db->quoteName('#__cwmconnect_details', 'a'))
                    ->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access')
                    ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
                    ->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
                    ->join('LEFT', $db->quoteName('#__categories', 'parent') . ' ON parent.id = c.parent_id')
                    ->select('fu.name as fu_name, fu.id as fu_id, fu.description as fu_description')
                    ->join('LEFT', $db->quoteName('#__cwmconnect_familyunit', 'fu') . ' ON fu.id = a.funitid')
                    ->where('a.id = ' . (int) $pk);

                $nullDate = $db->quote($db->getNullDate());
                $nowDate  = $db->quote(Factory::getDate()->toSql());

                $published = $this->getState('filter.published');
                $archived  = $this->getState('filter.archived');

                if (is_numeric($published)) {
                    $query->where('(a.published = ' . (int) $published . ' OR a.published = ' . (int) $archived . ')')
                        ->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
                        ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
                }

                // Phase G §7.2: hide opted-out / child rows from the profile view.
                $query->where('a.display_in_directory = 1');

                $data = $db->setQuery($query)->loadObject();

                if (empty($data)) {
                    $app->enqueueMessage(Text::_('COM_CWMCONNECT_ERROR_MEMBER_NOT_FOUND'), 'error');
                    $this->item[$pk] = false;
                    return $this->item[$pk];
                }

                if (
                    (is_numeric($published) || is_numeric($archived))
                    && (int) $data->published !== (int) $published
                    && (int) $data->published !== (int) $archived
                ) {
                    $app->enqueueMessage(Text::_('COM_CWMCONNECT_ERROR_MEMBER_NOT_FOUND'), 'error');
                }

                foreach (['params', 'metadata', 'attribs'] as $col) {
                    $reg = new Registry();
                    $reg->loadString((string) $data->$col);
                    $data->$col = $col === 'params' ? (clone $this->getState('params'))->merge($reg) ?? $reg : $reg;
                }

                // Coerce params back to its merged Registry (the merge above returned the merged registry).
                $merged = clone $this->getState('params');
                $merged->merge(new Registry((string) ($data->params instanceof Registry ? $data->params : '')));
                $data->params = $merged;

                $user   = $app->getIdentity();
                $groups = $user ? $user->getAuthorisedViewLevels() : [1];

                if ($this->getState('filter.access')) {
                    $data->params->set('access-view', true);
                } elseif ($data->catid == 0 || $data->category_access === null) {
                    $data->params->set('access-view', \in_array($data->access, $groups, false));
                } else {
                    $data->params->set(
                        'access-view',
                        \in_array($data->access, $groups, false) && \in_array($data->category_access, $groups, false)
                    );
                }

                $this->item[$pk] = $data;
            } catch (\Exception $e) {
                $this->setError($e);
                $this->item[$pk] = false;
            }
        }

        if ($this->item[$pk] && ($extended = $this->getChurchDirectoryQuery($pk))) {
            $this->item[$pk]->articles = $extended->articles ?? [];
            $this->item[$pk]->profile  = $extended->profile  ?? null;
        }

        return $this->item[$pk];
    }

    /**
     * Sidecar query: linked-user articles + profile form.
     *
     * @return  object|false
     *
     * @since   2.0.0
     */
    protected function getChurchDirectoryQuery(int $pk = 0): object|false
    {
        $db   = $this->getDatabase();
        $user = Factory::getApplication()->getIdentity();
        $pk   = $pk ?: (int) $this->getState('member.id');

        if (!$pk) {
            return false;
        }

        $query = $db->getQuery(true)
            ->select('a.*, cc.access as category_access, cc.title as category_name')
            ->from($db->quoteName('#__cwmconnect_details', 'a'))
            ->join('INNER', $db->quoteName('#__categories', 'cc') . ' ON cc.id = a.catid')
            ->where('a.id = ' . (int) $pk);

        if (is_numeric($this->getState('filter.published'))) {
            $query->where('a.published IN (1, 2)')
                ->where('cc.published IN (1, 2)');
        }

        // Phase G §7.2: hide opted-out / child rows from front-end queries.
        $query->where('a.display_in_directory = 1');

        $groups = implode(',', $user ? $user->getAuthorisedViewLevels() : [1]);
        $query->where('a.access IN (' . $groups . ')');

        try {
            $result = $db->setQuery($query)->loadObject();

            if (empty($result)) {
                return false;
            }

            if ($this->getState('params')->get('show_member_list')) {
                $reg = new Registry();
                $reg->loadString((string) $result->params);
                $this->getState('params')->merge($reg);
            }
        } catch (\Exception $e) {
            $this->setError($e);
            return false;
        }

        // Articles authored by the linked user.
        $articleQuery = $db->getQuery(true)
            ->select(['a.id', 'a.title', 'a.state', 'a.access', 'a.created'])
            ->from($db->quoteName('#__content', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON a.catid = c.id')
            ->where('a.created_by = ' . (int) $result->user_id)
            ->where('a.access IN (' . $groups . ')')
            ->order('a.state DESC, a.created DESC');

        if (Factory::getApplication()->getLanguageFilter()) {
            $articleQuery->where(
                'a.language = ' . $db->quote(Factory::getApplication()->getLanguage()->getTag())
                . ' OR a.language = ' . $db->quote('*')
            );
        }

        if (is_numeric($this->getState('filter.published'))) {
            $articleQuery->where('a.state IN (1, 2)');
        }

        $db->setQuery($articleQuery, 0, 10);
        $result->articles = $db->loadObjectList();

        // Profile form via com_users.
        try {
            $userModel = Factory::getApplication()
                ->bootComponent('com_users')
                ->getMVCFactory()
                ->createModel('User', 'Administrator', ['ignore_request' => true]);

            if ($userModel instanceof UserModel) {
                $userData = $userModel->getItem((int) $result->user_id);
                $form     = new Form('com_users.profile');

                PluginHelper::importPlugin('user');
                Factory::getApplication()->triggerEvent('onContentPrepareForm', [$form, $userData]);
                Factory::getApplication()->triggerEvent('onContentPrepareData', ['com_users.profile', $userData]);

                $form->bind($userData);
                $result->profile = $form;
            }
        } catch (\Throwable $e) {
            // Profile is non-essential; swallow.
            $result->profile = null;
        }

        $this->member = $result;

        return $result;
    }

    /**
     * Bump the hit counter for the active member.
     *
     * @since   2.0.0
     */
    public function hit(int $pk = 0): bool
    {
        $input    = Factory::getApplication()->getInput();
        $hitcount = $input->getInt('hitcount', 1);

        if (!$hitcount) {
            return true;
        }

        $pk = $pk ?: (int) $this->getState('member.id');
        $db = $this->getDatabase();

        try {
            $db->setQuery('UPDATE #__cwmconnect_details SET hits = hits + 1 WHERE id = ' . (int) $pk)
                ->execute();
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }

        return true;
    }
}
