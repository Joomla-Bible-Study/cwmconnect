<?php

/**
 * ChurchDirectory Contact manager component for Joomla! 1.7
 *
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('_JEXEC') or die;

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * Class for GeoUpdate
 * @package ChurchDirectory.Admin
 * @since 1.7.1
 */
class ChurchDirectoryViewGeoUpdate extends JViewLegacy {

    /**
     * Display the view
     * @return	void
     */
    public function display() {
        // Set the toolbar title
        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_GEOUPDATE'), 'churchdirectory');

        $model = $this->getModel();
        $from = JRequest::getString('from', null);


        $records = $model->findRecords();
        $lastRecord = $model->update($from);
        //print_r($lastRecord);
        if (empty($lastRecord)) {
            $percent = 100;
            JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_churchdirectory&view=cpanel');
        } else {
            $lastRecordID = array_search($lastRecord, $records);
            $percent = round(100 * ($lastRecordID + 1) / count($records));
            if ($percent < 1)
                $percent = 1;
            if ($percent > 100)
                $percent = 100;
        }

        $this->assign('table', $lastRecord);
        $this->assign('percent', $percent);

        $this->setLayout('geoupdate');

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

        JHTML::_('behavior.framework');

        parent::display();
    }

}