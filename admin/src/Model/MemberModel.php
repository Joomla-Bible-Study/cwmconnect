<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Categories\CategoriesHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Item model for a Member.
 *
 * @since  2.0.0
 */
class MemberModel extends AdminModel
{
    use VersionableModelTrait;

    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  2.0.0
     */
    public $typeAlias = 'com_churchdirectory.member';

    /**
     * The context used for the associations table
     *
     * @var    string
     * @since  2.0.0
     */
    protected $associationsContext = 'com_churchdirectory.item';

    /**
     * Batch copy/move command.
     *
     * @var  string
     * @since  2.0.0
     */
    protected $batch_copymove = 'category_id';

    /**
     * Allowed batch commands.
     *
     * @var array
     * @since 2.0.0
     */
    protected $batch_commands = [
        'assetgroup_id' => 'batchAccess',
        'language_id'   => 'batchLanguage',
        'tag'           => 'batchTag',
        'user_id'       => 'batchUser',
    ];

    /**
     * Method to perform batch operations on an item or a set of items.
     *
     * @param   array  $commands  An array of commands to perform.
     * @param   array  $pks       An array of item ids.
     * @param   array  $contexts  An array of item contexts.
     *
     * @return  bool
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function batch($commands, $pks, $contexts): bool
    {
        $pks = array_unique($pks);
        ArrayHelper::toInteger($pks);

        if (\array_search(0, $pks, true) !== false) {
            unset($pks[\array_search(0, $pks, true)]);
        }

        if (empty($pks)) {
            Factory::getApplication()->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'error');

            return false;
        }

        $done = false;

        if (!empty($commands['category_id'])) {
            $cmd = ArrayHelper::getValue($commands, 'move_copy', 'c');

            if ($cmd === 'c') {
                $result = $this->batchCopy($commands['category_id'], $pks, $contexts);

                if (\is_array($result)) {
                    $pks = $result;
                } else {
                    return false;
                }
            } elseif ($cmd === 'm' && !$this->batchMove($commands['category_id'], $pks, $contexts)) {
                return false;
            }

            $done = true;
        }

        if (!empty($commands['assetgroup_id'])) {
            if (!$this->batchAccess($commands['assetgroup_id'], $pks, $contexts)) {
                return false;
            }

            $done = true;
        }

        if (!empty($commands['language_id'])) {
            if (!$this->batchLanguage($commands['language_id'], $pks, $contexts)) {
                return false;
            }

            $done = true;
        }

        if (isset($commands['user_id']) && \strlen((string) $commands['user_id']) > 0) {
            if (!$this->batchUser($commands['user_id'], $pks, $contexts)) {
                return false;
            }

            $done = true;
        }

        if (!$done) {
            Factory::getApplication()->enqueueMessage(
                Text::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'),
                'error'
            );

            return false;
        }

        $this->cleanCache();

        return true;
    }

    /**
     * Batch copy items to a new category or current.
     *
     * @param   int    $value     The new category.
     * @param   array  $pks       An array of row IDs.
     * @param   array  $contexts  An array of item contexts.
     *
     * @return  array|false  An array of new IDs on success, false on failure.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function batchCopy($value, $pks, $contexts)
    {
        $categoryId = (int) $value;
        $newIds     = [];

        /** @var \CWM\Component\Churchdirectory\Administrator\Table\MemberTable $table */
        $table = $this->getTable();

        if (!parent::checkCategoryId($categoryId)) {
            return false;
        }

        if ($categoryId) {
            $categoryTable = Table::getInstance('Category');

            if (!$categoryTable->load($categoryId)) {
                if ($error = $categoryTable->getError()) {
                    Factory::getApplication()->enqueueMessage($error, 'error');

                    return false;
                }

                $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'));

                return false;
            }
        }

        if (empty($categoryId)) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'));

            return false;
        }

        $user = Factory::getApplication()->getIdentity();

        if (!$user || !$user->authorise('core.create', 'com_churchdirectory.category.' . $categoryId)) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));

            return false;
        }

        $i = 0;

        while (!empty($pks)) {
            $pk = array_shift($pks);

            $table->reset();

            if (!$table->load($pk)) {
                if ($error = $table->getError()) {
                    $this->setError($error);

                    return false;
                }

                $this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
                continue;
            }

            $data        = $this->generateNewTitle($categoryId, $table->alias, $table->name);
            $table->name  = $data[0];
            $table->alias = $data[1];

            $table->id        = 0;
            $table->catid     = $categoryId;
            $table->published = 0;

            if (!$table->check()) {
                $this->setError($table->getError());

                return false;
            }

            if (!$table->store()) {
                $this->setError($table->getError());

                return false;
            }

            $newIds[$i++] = (int) $table->get('id');
        }

        $this->cleanCache();

        return $newIds;
    }

    /**
     * Batch change a linked user.
     *
     * @param   int    $value     The new value matching a User ID.
     * @param   array  $pks       An array of row IDs.
     * @param   array  $contexts  An array of item contexts.
     *
     * @return  bool
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function batchUser($value, $pks, $contexts): bool
    {
        $user  = Factory::getApplication()->getIdentity();
        $table = $this->getTable();

        foreach ($pks as $pk) {
            if ($user && $user->authorise('core.edit', $contexts[$pk])) {
                $table->reset();
                $table->load($pk);
                $table->user_id = (int) $value;

                if (!$table->store()) {
                    $this->setError($table->getError());

                    return false;
                }
            } else {
                Factory::getApplication()->enqueueMessage(
                    Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'),
                    'error'
                );

                return false;
            }
        }

        $this->cleanCache();

        return true;
    }

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    protected function canDelete($record): bool
    {
        if (!empty($record->id)) {
            if ((int) $record->published !== -2) {
                return false;
            }

            return $this->getCurrentUser()->authorise(
                'core.delete',
                'com_churchdirectory.category.' . (int) $record->catid
            );
        }

        return false;
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param   object  $record  A record object.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    protected function canEditState($record): bool
    {
        if (!empty($record->catid)) {
            return $this->getCurrentUser()->authorise(
                'core.edit.state',
                'com_churchdirectory.category.' . (int) $record->catid
            );
        }

        return parent::canEditState($record);
    }

    /**
     * Save data.
     *
     * @param   array  $data  The form data.
     *
     * @return  bool
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function save($data): bool
    {
        $input = Factory::getApplication()->getInput();

        $catid = (int) ($data['catid'] ?? 0);

        if ($catid > 0) {
            $catid = CategoriesHelper::validateCategoryId($data['catid'], 'com_churchdirectory');
        }

        if ($catid === 0 && $this->canCreateCategory()) {
            $table = [
                'title'     => $data['catid'],
                'parent_id' => 1,
                'extension' => 'com_churchdirectory',
                'language'  => $data['language'] ?? '*',
                'published' => 1,
            ];

            $data['catid'] = CategoriesHelper::createCategory($table);
        }

        // Alter the name for save as copy.
        if ($input->get('task') === 'save2copy') {
            $origTable = clone $this->getTable();
            $origTable->load($input->getInt('id'));

            if (($data['name'] ?? '') === $origTable->name) {
                [$name, $alias] = $this->generateNewTitle(
                    (int) $data['catid'],
                    $data['alias'] ?? '',
                    $data['name']
                );
                $data['name']  = $name;
                $data['alias'] = $alias;
            } elseif (($data['alias'] ?? '') === $origTable->alias) {
                $data['alias'] = '';
            }

            $data['published'] = 0;
        }

        if (!empty($data['params']) && \is_array($data['params'])) {
            foreach (['linka', 'linkb', 'linkc', 'linkd', 'linke'] as $link) {
                if (!empty($data['params'][$link])) {
                    $data['params'][$link] = PunycodeHelper::urlToPunycode($data['params'][$link]);
                }
            }
        }

        $save = parent::save($data);

        if ($save) {
            $memberId = (int) ($data['id'] ?? $this->getState($this->getName() . '.id'));

            if ($memberId > 0) {
                try {
                    $factory = Factory::getApplication()
                        ->bootComponent('com_churchdirectory')
                        ->getMVCFactory();

                    /** @var GeoupdateModel $geoupdate */
                    $geoupdate = $factory->createModel('Geoupdate', 'Administrator', ['ignore_request' => true]);
                    $geoupdate->run(true, $memberId);
                } catch (\Throwable $e) {
                    Factory::getApplication()->enqueueMessage(
                        Text::sprintf('COM_CHURCHDIRECTORY_GEOCODE_FAILED', $e->getMessage()),
                        'warning'
                    );
                }
            }
        }

        return $save;
    }

    /**
     * Returns a Table object, always creating it.
     *
     * @param   string  $name     The table name.
     * @param   string  $prefix   The class prefix.
     * @param   array   $options  Configuration array.
     *
     * @return  Table
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getTable($name = 'Member', $prefix = '', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get the row form.
     *
     * @param   array  $data      Data for the form.
     * @param   bool   $loadData  True to load the form data.
     *
     * @return  Form|false
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getForm($data = [], $loadData = true): mixed
    {
        $form = $this->loadForm(
            'com_churchdirectory.member',
            'member',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        if (!$this->canEditState((object) $data)) {
            $form->setFieldAttribute('featured', 'disabled', 'true');
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('published', 'disabled', 'true');

            $form->setFieldAttribute('featured', 'filter', 'unset');
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get a single record.
     *
     * @param   int|null  $pk  The id of the primary key.
     *
     * @return  object|false
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item === false) {
            return false;
        }

        $registry        = new Registry();
        $registry->loadString((string) ($item->attribs ?? ''));
        $item->attribs   = $registry->toArray();

        $registry        = new Registry();
        $registry->loadString((string) ($item->metadata ?? ''));
        $item->metadata  = $registry->toArray();

        if (Associations::isEnabled()) {
            $item->associations = [];

            if (!empty($item->id)) {
                $associations = Associations::getAssociations(
                    'com_churchdirectory',
                    '#__churchdirectory_details',
                    'com_churchdirectory.item',
                    (int) $item->id
                );

                foreach ($associations as $tag => $association) {
                    $item->associations[$tag] = $association->id;
                }
            }
        }

        if (!empty($item->id)) {
            $item->tags = new TagsHelper();
            $item->tags->getTagIds($item->id, 'com_churchdirectory.member');
        }

        return $item;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function loadFormData(): mixed
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_churchdirectory.edit.member.data', []);

        if (empty($data)) {
            $data = $this->getItem();

            if ($data && !empty($data->con_position) && !\is_array($data->con_position)) {
                $data->con_position = explode(',', (string) $data->con_position);
            }

            if ($data && (int) $this->getState('member.id', 0) === 0) {
                $catid = $app->getInput()->getInt(
                    'catid',
                    (int) $app->getUserState('com_churchdirectory.members.filter.category_id', 0)
                );

                if ($catid && \is_object($data)) {
                    $data->catid = $catid;
                }
            }
        }

        $this->preprocessData('com_churchdirectory.member', $data);

        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  Table object.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function prepareTable($table): void
    {
        $date = Factory::getDate()->toSql();

        $table->name  = htmlspecialchars_decode((string) $table->name, ENT_QUOTES);
        $table->alias = ApplicationHelper::stringURLSafe((string) $table->alias);

        $table->generateAlias();

        if (empty($table->id)) {
            $table->created = $date;

            if (empty($table->ordering)) {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select('MAX(' . $db->quoteName('ordering') . ')')
                    ->from($db->quoteName('#__churchdirectory_details'));

                $db->setQuery($query);
                $max = (int) $db->loadResult();

                $table->ordering = $max + 1;
            }
        } else {
            $table->modified    = $date;
            $table->modified_by = (int) (Factory::getApplication()->getIdentity()?->id ?? 0);
        }

        if (isset($table->version)) {
            $table->version++;
        }
    }

    /**
     * A protected method to get a set of ordering conditions.
     *
     * @param   Table  $table  Table object.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    protected function getReorderConditions($table): array
    {
        $db = $this->getDatabase();

        return [$db->quoteName('catid') . ' = ' . (int) $table->catid];
    }

    /**
     * Method to toggle the featured setting of members.
     *
     * @param   array  $pks    The ids to toggle.
     * @param   int    $value  The value to toggle to.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public function featured($pks, $value = 0): bool
    {
        $pks = ArrayHelper::toInteger((array) $pks);

        if (empty($pks)) {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_NO_ITEM_SELECTED'));

            return false;
        }

        $table = $this->getTable();

        try {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__churchdirectory_details'))
                ->set($db->quoteName('featured') . ' = ' . (int) $value)
                ->whereIn($db->quoteName('id'), $pks);
            $db->setQuery($query);

            $db->execute();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $table->reorder();
        $this->cleanCache();

        return true;
    }

    /**
     * Preprocess the form.
     *
     * @param   Form    $form   Form object.
     * @param   mixed   $data   Data object.
     * @param   string  $group  Group name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        if ($this->getState('member.id')) {
            $form->setFieldAttribute('catid', 'action', 'core.edit');
        } else {
            $form->setFieldAttribute('catid', 'action', 'core.create');
        }

        if ($this->canCreateCategory()) {
            $form->setFieldAttribute('catid', 'allowAdd', 'true');
        }

        if (Associations::isEnabled()) {
            $languages = LanguageHelper::getContentLanguages(false, true, null, 'ordering', 'asc');

            if (\count($languages) > 1) {
                $addform  = new \SimpleXMLElement('<form />');
                $fields   = $addform->addChild('fields');
                $fields->addAttribute('name', 'associations');
                $fieldset = $fields->addChild('fieldset');
                $fieldset->addAttribute('name', 'item_associations');

                foreach ($languages as $language) {
                    $field = $fieldset->addChild('field');
                    $field->addAttribute('name', $language->lang_code);
                    $field->addAttribute('type', 'modal_contact');
                    $field->addAttribute('language', $language->lang_code);
                    $field->addAttribute('label', $language->title);
                    $field->addAttribute('translate_label', 'false');
                    $field->addAttribute('select', 'true');
                    $field->addAttribute('new', 'true');
                    $field->addAttribute('edit', 'true');
                    $field->addAttribute('clear', 'true');
                }

                $form->load($addform, false);
            }
        }

        parent::preprocessForm($form, $data, $group);
    }

    /**
     * Method to change the title and alias.
     *
     * @param   int     $categoryId  The id of the parent.
     * @param   string  $alias       The alias.
     * @param   string  $name        The title.
     *
     * @return  array  [name, alias]
     *
     * @since   2.0.0
     */
    protected function generateNewTitle($categoryId, $alias, $name): array
    {
        $table = $this->getTable();

        while ($table->load(['alias' => $alias, 'catid' => $categoryId])) {
            if ($name === $table->name) {
                $name = StringHelper::increment($name);
            }

            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$name, $alias];
    }

    /**
     * Is the user allowed to create an on-the-fly category?
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    private function canCreateCategory(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return $user !== null && $user->authorise('core.create', 'com_churchdirectory');
    }

    /**
     * Custom clean the cache of com_churchdirectory and the birthday/anniversary module.
     *
     * @param   string|null  $group     Cache group.
     * @param   int          $clientId  Client id.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function cleanCache($group = null, $clientId = 0): void
    {
        parent::cleanCache('com_churchdirectory');
        parent::cleanCache('mod_birthdayanniversary');
    }
}
