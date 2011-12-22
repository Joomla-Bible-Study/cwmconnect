<?php

/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @version             $Id: churchdirectory.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Renders a contact element
 *
 * @package     com_churchdirectory
 * @deprecated	JParameter is deprecated and will be removed in a future version. Use JForm instead.
 * @since		1.7.0
 */
class JElementChurchdirectory extends JElement {

    /**
     * Element name
     *
     * @var		string
     */
    var $_name = 'Churchdirectory';

    public function fetchElement($name, $value, &$node, $control_name) {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $doc = JFactory::getDocument();
        $template = $app->getTemplate();
        $fieldName = $control_name . '[' . $name . ']';
        $contact = JTable::getInstance('churchdirectory');
        if ($value) {
            $contact->load($value);
        } else {
            $contact->title = JText::_('COM_CONTENT_SELECT_A_CONTACT');
        }
        $js = "
		function jSelectChurchDirectory(id, name, object) {
			document.getElementById(object + '_id').value = id;
			document.getElementById(object + '_name').value = name;
			document.getElementById('sbox-window').close();
		}";
        $doc->addScriptDeclaration($js);
        $link = 'index.php?option=com_churchdirectory&amp;task=element&amp;tmpl=component&amp;object=' . $name;

        JHtml::_('behavior.modal', 'a.modal');
        $html = "\n" . '<div class="fltlft"><input type="text" id="' . $name . '_name" value="' . htmlspecialchars($contact->name, ENT_QUOTES, 'UTF-8') . '" disabled="disabled" /></div>';
        $html .= '<div class="button2-left"><div class="blank"><a class="modal" title="' . JText::_('COM_CONTENT_SELECT_A_CONTACT') . '"  href="' . $link . '" rel="{handler: \'iframe\', size: {x: 650, y: 375}}">' . JText::_('JSELECT') . '</a></div></div>' . "\n";
        $html .= "\n" . '<input type="hidden" id="' . $name . '_id" name="' . $fieldName . '" value="' . (int) $value . '" />';

        return $html;
    }

}