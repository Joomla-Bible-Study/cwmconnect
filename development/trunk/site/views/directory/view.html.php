<?php

/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @version             $Id: view.html.php 71 $
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
jimport('joomla.mail.helper');

/**
 * HTML Contact View class for the Contact component
 *
 * @package	com_churchdirectory
 * @since 		1.7.0
 */
class ChurchDirectoryViewDirectory extends JView {

    protected $state;
    protected $items;
    protected $category;
    protected $categories;
    protected $pagination;

    /**
     * Display the view
     *
     * @return	mixed	False on error, null otherwise.
     */
    function display($tpl = null) {
        $app = JFactory::getApplication();
        $params = $app->getParams();

        // Get some data from the models
        $state = $this->get('State');
        $items = $this->get('Items');
        $category = $this->get('Category');
        $children = $this->get('Children');
        $pagination = $this->get('Pagination');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseWarning(500, implode("\n", $errors));
            return false;
        }

        if ($items === false) {
            JError::raiseError(404, JText::_('COM_CHURCHDIRECTOY_ERROR_DIRECTORY_NOT_FOUND'));
            return false;
        }

        // Check whether category access level allows access.
        $user = JFactory::getUser();
        $groups = $user->getAuthorisedViewLevels();

        // Prepare the data.
        // Compute the contact slug.
        for ($i = 0, $n = count($items); $i < $n; $i++) {
            $item = &$items[$i];
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
            $temp = new JRegistry();
            $temp->loadString($item->params);
            $item->params = clone($params);
            $item->params->merge($temp);
            if ($item->params->get('show_email', 0) == 1) {
                $item->email_to = trim($item->email_to);
                if (!empty($item->email_to) && JMailHelper::isEmailAddress($item->email_to)) {
                    $item->email_to = JHtml::_('email.cloak', $item->email_to);
                } else {
                    $item->email_to = '';
                }
            }
        }

        // Setup the category parameters.
        $cparams = $category->getParams();
        $category->params = clone($params);
        $category->params->merge($cparams);
        $children = array($category->id => $children);

        $maxLevel = $params->get('maxLevel', -1);
        $this->assignRef('maxLevel', $maxLevel);
        $this->assignRef('state', $state);
        $this->assignRef('items', $items);
        $this->assignRef('category', $category);
        $this->assignRef('children', $children);
        $this->assignRef('params', $params);
        $this->assignRef('parent', $parent);
        $this->assignRef('pagination', $pagination);

        //Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

        $this->_prepareDocument();
        JHTML::stylesheet('general.css', 'media/com_churchdirectory/css/');
        JHTML::stylesheet('churchdirectory.css', 'media/com_churchdirectory/css/');

        parent::display($tpl);
    }

    /**
     * Prepares the document
     */
    protected function _prepareDocument() {
        $app = JFactory::getApplication();
        $menus = $app->getMenu();
        $pathway = $app->getPathway();
        $title = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();
        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', JText::_('COM_CHURCHDIRECTORY_DEFAULT_PAGE_TITLE'));
        }
        $id = (int) @$menu->query['id'];

        $title = $this->params->get('page_title', '');
        if (empty($title)) {
            $title = $app->getCfg('sitename');
        } elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
            $title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
        } elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
            $title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
        }
        $this->document->setTitle($title);

        if ($this->category->metadesc) {
            $this->document->setDescription($this->category->metadesc);
        } elseif (!$this->category->metadesc && $this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->category->metakey) {
            $this->document->setMetadata('keywords', $this->category->metakey);
        } elseif (!$this->category->metakey && $this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }

}
