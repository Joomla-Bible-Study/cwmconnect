<?php

/**
 * @version		$Id: churchdirectory.php 22338 2011-11-04 17:24:53Z github_bot $
 * @package		Joomla.Site
 * @subpackage	com_churchdirectory
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modelform');
jimport('joomla.application.component.modelitem');
jimport('joomla.event.dispatcher');
jimport('joomla.plugin.helper');

/**
 * @package		Joomla.Site
 * @subpackage	com_churchdirectory
 * @since 2.5
 */
class ChurchDirectoryModelChurchDirectory extends JModelForm {

    /**
     * @since	1.6
     */
    protected $view_item = 'churchdirectory';
    protected $_item = null;

    /**
     * Model context string.
     *
     * @var		string
     */
    protected $_context = 'com_churchdirectory.churchdirectory';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since	1.6
     */
    protected function populateState() {
        $app = JFactory::getApplication('site');

        // Load state from the request.
        $pk = JRequest::getInt('id');
        $this->setState('churchdirectory.id', $pk);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);

        $user = JFactory::getUser();
        if ((!$user->authorise('core.edit.state', 'com_churchdirectory')) && (!$user->authorise('core.edit', 'com_churchdirectory'))) {
            $this->setState('filter.published', 1);
            $this->setState('filter.archived', 2);
        }
    }

    /**
     * Method to get the churchdirectory form.
     *
     * The base form is loaded from XML and then an event is fired
     *
     *
     * @param	array	$data		An optional array of data for the form to interrogate.
     * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
     * @return	JForm	A JForm object on success, false on failure
     * @since	1.6
     */
    public function getForm($data = array(), $loadData = true) {
        // Get the form.
        $form = $this->loadForm('com_churchdirectory.churchdirectory', 'churchdirectory', array('control' => 'jform', 'load_data' => true));
        if (empty($form)) {
            return false;
        }

        $id = $this->getState('churchdirectory.id');
        $params = $this->getState('params');
        $churchdirectory = $this->_item[$id];
        $params->merge($churchdirectory->params);

        if (!$params->get('show_email_copy', 0)) {
            $form->removeField('churchdirectory_email_copy');
        }

        return $form;
    }

    protected function loadFormData() {
        $data = (array) JFactory::getApplication()->getUserState('com_churchdirectory.churchdirectory.data', array());
        return $data;
    }

    /**
     * Gets a list of churchdirectorys
     * @param array
     * @return mixed Object or null
     */
    public function &getItem($pk = null) {
        // Initialise variables.
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('churchdirectory.id');

        if ($this->_item === null) {
            $this->_item = array();
        }

        if (!isset($this->_item[$pk])) {
            try {
                $db = $this->getDbo();
                $query = $db->getQuery(true);

                $query->select($this->getState('item.select', 'a.*') . ','
                        . ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug, '
                        . ' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END AS catslug ');
                $query->from('#__churchdirectory_details AS a');

                // Join on category table.
                $query->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access');
                $query->join('LEFT', '#__categories AS c on c.id = a.catid');


                // Join over the categories to get parent category titles
                $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias');
                $query->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

                $query->select('fu.name as fu_name, fu.id as fu_id, fu.description as fu_description');
                $query->join('LEFT', '#__churchdirectory_familyunit AS fu ON fu.id = a.funitid');

                $query->where('a.id = ' . (int) $pk);

                // Filter by start and end dates.
                $nullDate = $db->Quote($db->getNullDate());
                $nowDate = $db->Quote(JFactory::getDate()->toMySQL());


                // Filter by published state.
                $published = $this->getState('filter.published');
                $archived = $this->getState('filter.archived');
                if (is_numeric($published)) {
                    $query->where('(a.published = ' . (int) $published . ' OR a.published =' . (int) $archived . ')');
                    $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
                    $query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
                }

                $db->setQuery($query);

                $data = $db->loadObject();

                if ($error = $db->getErrorMsg()) {
                    throw new JException($error);
                }

                if (empty($data)) {
                    throw new JException(JText::_('COM_CHURCHDIRECTORY_ERROR_CONTACT_NOT_FOUND'), 404);
                }

                // Check for published state if filter set.
                if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived))) {
                    JError::raiseError(404, JText::_('COM_CHURCHDIRECTORY_ERROR_CONTACT_NOT_FOUND'));
                }

                // Convert parameter fields to objects.
                $registry = new JRegistry;
                $registry->loadString($data->params);
                $data->params = clone $this->getState('params');
                $data->params->merge($registry);

                $registry = new JRegistry;
                $registry->loadString($data->metadata);
                $data->metadata = $registry;

                // Compute access permissions.
                if ($access = $this->getState('filter.access')) {
                    // If the access filter has been set, we already know this user can view.
                    $data->params->set('access-view', true);
                } else {
                    // If no access filter is set, the layout takes some responsibility for display of limited information.
                    $user = JFactory::getUser();
                    $groups = $user->getAuthorisedViewLevels();

                    if ($data->catid == 0 || $data->category_access === null) {
                        $data->params->set('access-view', in_array($data->access, $groups));
                    } else {
                        $data->params->set('access-view', in_array($data->access, $groups) && in_array($data->category_access, $groups));
                    }
                }

                $this->_item[$pk] = $data;
            } catch (JException $e) {
                $this->setError($e);
                $this->_item[$pk] = false;
            }
        }

        if ($this->_item[$pk]) {
            if ($extendedData = $this->getChurchDirectoryQuery($pk)) {
                $this->_item[$pk]->articles = $extendedData->articles;
                $this->_item[$pk]->profile = $extendedData->profile;
            }
        }
        return $this->_item[$pk];
    }

    protected function getChurchDirectoryQuery($pk = null) {
        // TODO: Cache on the fingerprint of the arguments
        $db = $this->getDbo();
        $user = JFactory::getUser();
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('churchdirectory.id');

        $query = $db->getQuery(true);
        if ($pk) {
            $query->select('a.*, cc.access as category_access, cc.title as category_name, '
                    . ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug, '
                    . ' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(\':\', cc.id, cc.alias) ELSE cc.id END AS catslug ');

            $query->from('#__churchdirectory_details AS a');

            $query->join('INNER', '#__categories AS cc on cc.id = a.catid');

            $query->where('a.id = ' . (int) $pk);
            $published = $this->getState('filter.published');
            $archived = $this->getState('filter.archived');
            if (is_numeric($published)) {
                $query->where('a.published IN (1,2)');
                $query->where('cc.published IN (1,2)');
            }
            $groups = implode(',', $user->getAuthorisedViewLevels());
            $query->where('a.access IN (' . $groups . ')');

            try {
                $db->setQuery($query);
                $result = $db->loadObject();

                if ($error = $db->getErrorMsg()) {
                    throw new Exception($error);
                }

                if (empty($result)) {
                    throw new JException(JText::_('COM_CHURCHDIRECTORY_ERROR_CONTACT_NOT_FOUND'), 404);
                }

                // If we are showing a churchdirectory list, then the churchdirectory parameters take priority
                // So merge the churchdirectory parameters with the merged parameters
                if ($this->getState('params')->get('show_churchdirectory_list')) {
                    $registry = new JRegistry;
                    $registry->loadString($result->params);
                    $this->getState('params')->merge($registry);
                }
            } catch (Exception $e) {
                $this->setError($e);
                return false;
            }

            if ($result) {
                $user = JFactory::getUser();
                $groups = implode(',', $user->getAuthorisedViewLevels());

                //get the content by the linked user
                $query = $db->getQuery(true);
                $query->select('id, title, state, access, created');
                $query->from('#__content');
                $query->where('created_by = ' . (int) $result->user_id);
                $query->where('access IN (' . $groups . ')');
                $query->order('state DESC, created DESC');
                // filter per language if plugin published
                if (JFactory::getApplication()->getLanguageFilter()) {
                    $query->where('language=' . $db->quote(JFactory::getLanguage()->getTag()) . ' OR language ="*"');
                }
                if (is_numeric($published)) {
                    $query->where('state IN (1,2)');
                }
                $db->setQuery($query, 0, 10);
                $articles = $db->loadObjectList();
                $result->articles = $articles;

                //get the profile information for the linked user
                require_once JPATH_ADMINISTRATOR . '/components/com_users/models/user.php';
                $userModel = JModel::getInstance('User', 'UsersModel', array('ignore_request' => true));
                $data = $userModel->getItem((int) $result->user_id);

                JPluginHelper::importPlugin('user');
                $form = new JForm('com_users.profile');
                // Get the dispatcher.
                $dispatcher = JDispatcher::getInstance();

                // Trigger the form preparation event.
                $dispatcher->trigger('onContentPrepareForm', array($form, $data));
                // Trigger the data preparation event.
                $dispatcher->trigger('onContentPrepareData', array('com_users.profile', $data));

                // Load the data into the form after the plugins have operated.
                $form->bind($data);
                $result->profile = $form;

                $this->churchdirectory = $result;
                return $result;
            }
        }
    }

}