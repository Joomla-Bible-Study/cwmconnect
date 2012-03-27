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

    protected $state = null;
    protected $items = null;
    protected $category = null;
    protected $categories = null;
    protected $pagination = null;

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
        //var_dump($items);
        //print_r('Home');
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        if ($items == false) {
            JError::raiseError(404, JText::_('COM_CHURCHDIRECTOY_ERROR_DIRECTORY_NOT_FOUND'));
            return false;
        }

        // Check whether category access level allows access.
        $user = JFactory::getUser();
        $groups = $user->getAuthorisedViewLevels();
        if (!in_array($category->access, $groups)) {
            return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
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
            if ($item->params->get('dr_show_street_address') || $item->params->get('dr_show_suburb') || $item->params->get('dr_show_state') || $item->params->get('dr_show_postcode') || $item->params->get('dr_show_country')) {
                $params->set('address_check', 1);
            } else {
                $params->set('address_check', 0);
            }
            if ($item->params->get('dr_show_email') || $item->params->get('dr_show_telephone') || $item->params->get('dr_show_fax') || $item->params->get('dr_show_mobile') || $item->params->get('dr_show_webpage') || $item->params->get('dr_show_spouse') || $item->params->get('dr_show_children')) {
                $params->set('other_check', 1);
            } else {
                $params->set('other_check', 0);
            }
        }

        switch ($item->params->get('dr_churchdirectory_icons')) {
            case 1 :
                // text
                $params->set('marker_address', JText::_('COM_CHURCHDIRECTORY_ADDRESS') . ": ");
                $params->set('marker_email', JText::_('JGLOBAL_EMAIL') . ": ");
                $params->set('marker_telephone', JText::_('COM_CHURCHDIRECTORY_TELEPHONE') . ": ");
                $params->set('marker_fax', JText::_('COM_CHURCHDIRECTORY_FAX') . ": ");
                $params->set('marker_mobile', JText::_('COM_CHURCHDIRECTORY_MOBILE') . ": ");
                $params->set('marker_misc', JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION') . ": ");
                $params->set('marker_class', 'jicons-text');
                break;

            case 2 :
                // none
                $params->set('marker_address', '');
                $params->set('marker_email', '');
                $params->set('marker_telephone', '');
                $params->set('marker_mobile', '');
                $params->set('marker_fax', '');
                $params->set('marker_misc', '');
                $params->set('marker_class', 'jicons-none');
                break;

            default :
                // icons
                $image1 = JHtml::_('image', 'contacts/' . $params->get('icon_address', 'con_address.png'), JText::_('COM_CHURCHDIRECTORY_ADDRESS') . ": ", NULL, true);
                $image2 = JHtml::_('image', 'contacts/' . $params->get('icon_email', 'emailButton.png'), JText::_('JGLOBAL_EMAIL') . ": ", NULL, true);
                $image3 = JHtml::_('image', 'contacts/' . $params->get('icon_telephone', 'con_tel.png'), JText::_('COM_CHURCHDIRECTORY_TELEPHONE') . ": ", NULL, true);
                $image4 = JHtml::_('image', 'contacts/' . $params->get('icon_fax', 'con_fax.png'), JText::_('COM_CHURCHDIRECTORY_FAX') . ": ", NULL, true);
                $image5 = JHtml::_('image', 'contacts/' . $params->get('icon_misc', 'con_info.png'), JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION') . ": ", NULL, true);
                $image6 = JHtml::_('image', 'contacts/' . $params->get('icon_mobile', 'con_mobile.png'), JText::_('COM_CHURCHDIRECTORY_MOBILE') . ": ", NULL, true);

                $params->set('marker_address', $image1);
                $params->set('marker_email', $image2);
                $params->set('marker_telephone', $image3);
                $params->set('marker_fax', $image4);
                $params->set('marker_misc', $image5);
                $params->set('marker_mobile', $image6);
                $params->set('marker_class', 'jicons-icons');
                break;
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
