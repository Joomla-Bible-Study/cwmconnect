<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;

/**
 * Supports a modal member picker.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class JFormFieldModal_Members extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.7.0
	 */
	protected $type = 'Modal_Members';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.7.0
	 */
	protected function getInput()
	{
		JHtml::_('behavior.framework');
		JHtml::_('behavior.modal', 'a.modal');

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			JHtml::_('bootstrap.tooltip');
		}

		// Build the script.
		$script   = [];
		$script[] = '	function jSelectChart_' . $this->id . '(id, name, object) {';
		$script[] = '		document.id("' . $this->id . '_id").value = id;';
		$script[] = '		document.id("' . $this->id . '_name").value = name;';
		$script[] = '		SqueezeBox.close();';
		$script[] = '	}';

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		// Get the title of the linked chart
		$db = JFactory::getDbo();
		$db->setQuery(
			'SELECT name' .
				' FROM #__churchdirectory_details' .
				' WHERE id = ' . (int) $this->value
		);

		try
		{
			$title = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage);
		}

		if (empty($title))
		{
			$title = JText::_('COM_CHURCHDIRECTORY_SELECT_A_MEMBER');
		}

		$link = 'index.php?option=com_churchdirectory&amp;view=members&amp;layout=modal&amp;tmpl=component&amp;function=jSelectChart_' . $this->id;

		if (isset($this->element['language']))
		{
			$link .= '&amp;forcedLanguage=' . $this->element['language'];
		}

		$html = "\n" . '<div class="input-append"><input type="text" class="input-medium" id="' . $this->id . '_name" value="' .
			htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" disabled="disabled" /><a class="modal btn" title="' .
			JText::_('COM_CHURCHDIRECTORY_CHANGE_MEMBER_BUTTON') .
			'"  href="' . $link . '" rel="{handler: \'iframe\', size: {x: 800, y: 450}}"><i class="icon-address hasTooltip" title="' .
			JText::_('COM_CHURCHDIRECTORY_CHANGE_MEMBER_BUTTON') . '"></i> ' . JText::_('JSELECT') . '</a></div>' . "\n";

		// The active member id field.
		if (0 == (int) $this->value)
		{
			$value = '';
		}
		else
		{
			$value = (int) $this->value;
		}

		// -- class='required' for client side validation
		$class = '';

		if ($this->required)
		{
			$class = ' class="required modal-value"';
		}

		$html .= '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" />';

		return $html;
	}
}
