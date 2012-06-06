<?php

/**
 * @version		$Id: contacts.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

/**
 * Supports a modal contact picker.
 *
 * @package	com_churchdirectory
 * @since		1.7.0
 */
class JFormFieldModal_Member extends JFormField {

    /**
     * The form field type.
     *
     * @var		string
     * @since	1.7.0
     */
    protected $type = 'Modal_Member';

    /**
     * Method to get the field input markup.
     *
     * @return	string	The field input markup.
     * @since	1.7.0
     */
    protected function getInput() {
        // Load the javascript
        //JHtml::_('behavior.framework');
        JHtml::_('behavior.modal', 'a.modal');

        // Build the script.
        $script = array();
        $script[] = '	function jSelectChart_' . $this->id . '(id, name, catid, object) {';
        $script[] = '		document.id("' . $this->id . '_id").value = id;';
        $script[] = '		document.id("' . $this->id . '_name").value = name;';
        $script[] = '		SqueezeBox.close();';
        $script[] = '	}';

        // Add the script to the document head.
        JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

        // Get the title of the linked chart
        $db = JFactory::getDBO();
        $db->setQuery(
                'SELECT name' .
                ' FROM #__churchdirectory_details' .
                ' WHERE id = ' . (int) $this->value
        );
        $title = $db->loadResult();

        if ($error = $db->getErrorMsg()) {
            JError::raiseWarning(500, $error);
        }

        if (empty($title)) {
            $title = JText::_('COM_CHURCHDIRECTORY_SELECT_A_CONTACT');
        }

        $link = 'index.php?option=com_churchdirectory&amp;view=members&amp;layout=modal&amp;tmpl=component&amp;function=jSelectChart_' . $this->id;

        $html = "\n" . '<div class="fltlft"><input type="text" id="' . $this->id . '_name" value="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" disabled="disabled" /></div>';
        $html .= '<div class="button2-left"><div class="blank"><a class="modal" title="' . JText::_('COM_CHURCHDIRECTORY_CHANGE_CONTACT_BUTTON') . '"  href="' . $link . '" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">' . JText::_('COM_CHURCHDIRECTROY_CHANGE_CONTACT_BUTTON') . '</a></div></div>' . "\n";
        // The active contact id field.
        if (0 == (int) $this->value) {
            $value = '';
        } else {
            $value = (int) $this->value;
        }

        // class='required' for client side validation
        $class = '';
        if ($this->required) {
            $class = ' class="required modal-value"';
        }

        $html .= '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" />';

        return $html;
    }

}
