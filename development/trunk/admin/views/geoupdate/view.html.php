<?php

/**
 * ChurchDirectory Contact manager component for Joomla! 1.7
 *
 * @version             $Id: view.html.php 71 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die;

// Load framework base classes
jimport('joomla.application.component.view');

class ChurchDirectoryViewGeoUpdate extends JView {

    function display() {
        // Set the toolbar title
        JToolBarHelper::title(JText::_('ADMINTOOLS_TITLE_DBTOOLS'), 'churchdirectory');

        $model = $this->getModel();
        $from = JRequest::getString('from', null);

        $tables = $model->findTables();
        $lastTable = $model->repairAndOptimise($from);
        if (empty($lastTable)) {
            $percent = 100;
            JToolBarHelper::back((ADMINTOOLS_JVERSION == '15') ? 'Back' : 'JTOOLBAR_BACK', 'index.php?option=com_admintools');
        } else {
            $lastTableID = array_search($lastTable, $tables);
            $percent = round(100 * ($lastTableID + 1) / count($tables));
            if ($percent < 1)
                $percent = 1;
            if ($percent > 100)
                $percent = 100;
        }

        $this->assign('table', $lastTable);
        $this->assign('percent', $percent);

        $this->setLayout('optimize');

        $document = JFactory::getDocument();
        $script = "window.addEvent( 'domready' ,  function() {\n";
        $script .= "$('progressbar-inner').setStyle('width', '$percent%');\n";
        if (!empty($lastTable)) {
            $script .= "document.forms.adminForm.submit();\n";
        } else {
            $script .= "window.setTimeout('parent.SqueezeBox.close();', 3000);\n";
        }
        $script .= "});\n";
        $document->addScriptDeclaration($script);

        // Load CSS
        $document = JFactory::getDocument();
        $document->addStyleSheet(rtrim(JURI::base(), '/') . '/../media/com_churchdirectory/css/genural.css');

        JHTML::_('behavior.mootools');

        parent::display();
    }

}