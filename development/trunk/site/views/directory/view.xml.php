<?php

/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @version             $Id: view.xml.php 71 $
 * @package		com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 Joomla Bible Study, All rights reserved.
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
    protected $params;
    protected $kml_params;
    protected $category_params;
    protected $category;
    protected $children;
    protected $pagination;

    function display($tpl = null) {
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        // Get some data from the models
        $state = $this->get('State');
        $params = $state->params;
        $items = $this->get('Items');
        $category = $this->get('Category');
        $children = $this->get('Children');
        $parent = $this->get('Parent');
        $pagination = $this->get('Pagination');
        $dispatcher = & JDispatcher::getInstance();
        $doc = JFactory::getDocument();
        $doc->setMetaData('Content-Type', 'application/xml', true);

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseWarning(500, implode("\n", $errors));
            return false;
        }

        // Check whether category access level allows access.
        $user = JFactory::getUser();
        $groups = $user->getAuthorisedViewLevels();
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseWarning(500, implode("\n", $errors));
            return false;
        }

        if ($items === false) {
            JError::raiseError(404, JText::_('COM_CHURCHDIRECTOY_ERROR_DIRECTORY_NOT_FOUND'));
            return false;
        }

        // Prepare the data.
        // Compute the contact slug.
        for ($i = 0, $n = count($items); $i < $n; $i++) {
            $item = &$items[$i];
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;


            $item->event = new stdClass();
            $temp = new JRegistry();
            $temp->loadString($item->params);
            $item->params = clone($params);
            $item->params->merge($temp);

            if ($item->params->get('show_email', 0) == 1) {
                $item->email_to = trim($item->email_to);

                if (!empty($item->email_to) && JMailHelper::isEmailAddress($item->email_to)) {
                    $item->email_to = JHtml::_('email.cloak', $item->email_to);
                } else {
                    $item->email_to = null;
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

        // for parssing records out to put then in order and not repeat the records.
        function groupit($args) {
            extract($args);

            $result = array();
            foreach ($items as $item) {
                if (!empty($item->$field))
                    $key = $item->$field;
                else
                    $key = 'nomatch';
                if (array_key_exists($key, $result))
                    $result[$key][] = $item;
                else {
                    $result[$key] = array();
                    $result[$key][] = $item;
                }
            }
            return $result;
        }

        // Creates an array of strings to hold the lines of the KML file.
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2">';
        $kml[] = '<Document>';
        $kml[] = '<name>' . $items[0]->kml_name . '</name>';
        $kml[] = '<open>' . $items[0]->kml_params->get('open') . '</open>';
        $kml[] = '<LookAt>
    		 <longitude>' . $items[0]->kml_lng . '</longitude>
    		 <latitude>' . $items[0]->kml_lat . '</latitude>
		 <altitude>' . $items[0]->kml_params->get('altitude') . '</altitude>
		 <range>' . $items[0]->kml_params->get('range') . '</range>
		 <tilt>' . $items[0]->kml_params->get('tilt') . '</tilt>
		 <heading>' . $items[0]->kml_params->get('heading') . '</heading>
		 <gx:altitudeMode>' . $items[0]->kml_params->get('gxaltitudeMode') . '</gx:altitudeMode>
  	     </LookAt>    <!-- Camera or LookAt -->';
        $kml[] = $items[0]->kml_style;

        $teams = groupit(array('items' => $items, 'field' => 'category_title'));

        foreach ($teams as $c => $catid) {
            $newrows[$c] = groupit(array('items' => $teams[$c], 'field' => 'suburb'));
            $ckml_params = $catid[0]->kml_params;
        }
        $mycounter = '0';
        foreach ($newrows as $c => $suburb) {
            $kml[] = '<Folder id="' . $mycounter++ . '"> ';
            $kml[] = '<name>';
            $kml[] = $c;
            $kml[] = '</name>';
            $kml[] = '<open>' . $ckml_params->get('mcropen') . '</open>           <!-- boolean -->';
            foreach ($suburb as $s => $rows) {
                $kml[] = ' <Folder id="' . $mycounter++ . '"> ';
                $kml[] = ' <name>' . $s . ' </name> ';
                $kml[] = ' <open>' . $ckml_params->get('msropen') . '</open>           	   <!-- boolean -->';

                foreach ($rows as $row) {
                    $kml[] = '<Style id="text_photo_banner">';
                    $kml[] = '<IconStyle>';
                    $kml[] = '<scale>';
                    if ($row->params->get('icscale') == null) {
                        $kml[] = '1.1';
                    } else {
                        $kml[] = $row->kml_params->get('icscale');
                    }
                    $kml[] = '</scale>';
                    $kml[] = '<Icon>';
                    $kml[] = '<href>';
                    if ($row->category_params->get('image') == null) {
                        $kml[] = JURI::base() . 'media/com_churchdirectory/images/kml_icons/iconb.png';
                    } else {
                        $kml[] = JURI::base() . $row->category_params->get('image');
                    }
                    $kml[] = '</href>';
                    $kml[] = '</Icon>';
                    $kml[] = '</IconStyle>';
                    $kml[] = '<LabelStyle>';
                    $kml[] = '<color>';
                    $kml[] = $row->kml_params->get('lscolor');
                    $kml[] = '</color>';
                    $kml[] = '<colorMode>';
                    $kml[] = $row->kml_params->get('lscolormode');
                    $kml[] = '</colorMode>';
                    $kml[] = '<scale>';
                    if ($row->params->get('lsscale') == null) {
                        $kml[] = '.6';
                    } else {
                        $kml[] = $row->kml_params->get('lsscale');
                    }
                    $kml[] = '</scale>';
                    $kml[] = '</LabelStyle>';
                    $kml[] = '</Style> ';
                    $kml[] = '<Placemark id="placemark' . $mycounter++ . ' "> ';
                    $kml[] = '<name>' . $row->name . '</name>';
                    $kml[] = '<visibility>';
                    if ($row->params->get('visibility') == null) {
                        $kml[] = '0';
                    } else {
                        $kml[] = $row->params->get('visibility');
                    }
                    $kml[] = '</visibility><open>';
                    if ($row->params->get('open') == null) {
                        $kml[] = '0';
                    } else {
                        $kml[] = $row->params->get('open');
                    }
                    $kml[] = '</open>';
                    $kml[] = '<gx:balloonVisibility>';
                    if ($row->params->get('gxballoonvisibility') == null) {
                        $kml[] = '0';
                    } else {
                        $kml[] = $row->params->get('gxballoonvisibility');
                    }
                    $kml[] = '</gx:balloonVisibility>';
                    $kml[] = '<address><![CDATA[';
                    if ($row->address == null) {
                        $kml[] = $row->address . ',<br />';
                    }
                    $kml[] = $row->suburb . ', ' . $row->state . ' ' . $row->postcode;
                    if ($row->postcodeaddon == null) {
                        $kml[] = '-' . $row->postcodeaddon;
                    }
                    $kml[] = ']]></address> <!-- string -->';
                    $kml[] = '<phoneNumber>' . $row->telephone . '</phoneNumber> <!-- string -->';
                    $kml[] = '<Snippet maxLines="';
                    if ($row->kml_params->get('rmaxlines')) {
                        $kml[] = '2';
                    } else {
                        $kml[] = $row->kml_params->get('rmaxlines');
                    }
                    $kml[] = '"><![CDATA[' . $row->con_position . ' <br />Team ' . $row->catid . ']]></Snippet>   <!-- string -->';
                    $kml[] = '<description>' . '<![CDATA[<div>';
                    if ($row->image == null) {
                        $kml[] = '<img src="' . JURI::base() . 'media/com_churchdirectory/images/photo_not_available.jpg" alt="Photo" width="100" hight="100" /><br />';
                    } else {
                        $kml[] = '<img src="' . JURI::base() . $row->image . '" alt="Photo" width="100" hight="100" /><br />';
                    }
                    if ($row->con_position == null) {
                        $kml[] = '<b>Position: ' . $row->con_position . '</b><br />';
                    }
                    if ($row->spouse == null) {
                        $kml[] = 'Spouse: ' . $row->spouse . '<br />';
                    }
                    if ($row->children == null) {
                        $kml[] = 'Children: ' . $row->children . '<br />';
                    }
                    if ($row->misc == null) {
                        $kml[] = $row->misc;
                    }
                    if ($row->telephone == null) {
                        $kml[] = '<br />PH: ' . $row->telephone;
                    }
                    if ($row->fax == null) {
                        $kml[] = '<br />Fax: ' . $row->fax;
                    }
                    if ($row->mobile == null) {
                        $kml[] = '<br />Cell: ' . $row->mobile;
                    }
                    if ($row->email_to == null) {
                        $kml[] = '<br />Email: <a href="mailto:' . $row->email_to . '">' . $row->email_to . '</a>';
                    }
                    $kml[] = '</div>]]>' . '</description>';
                    $kml[] = '<Point>';
                    $kml[] = '<coordinates>' . $row->lng . ',' . $row->lat . '</coordinates>';
                    $kml[] = '</Point>';
                    $kml[] = '</Placemark>';
                } // end the state folder
                $kml[] = '</Folder>';
            } // end the country folder
            $kml[] = '</Folder>';
        }
// End XML file
        $kml[] = '</Document>';
        $kml[] = '</kml>';
        $kmlOutput = join("\n", $kml);
        echo $kmlOutput;
    }

}
