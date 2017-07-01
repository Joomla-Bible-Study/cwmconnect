<?php
/**
 * @package    ChurchDirectory.Admin
 * @desc  form for modal layout
 * @copyright  Copyright (C) 2007 - 2012 Joomla Bible Study
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));

$function  = JFactory::getApplication()->input->getCmd('function', 'jEditMember_' . (int) $this->item->id);

// Function to update input title when changed
JFactory::getDocument()->addScriptDeclaration('
	function jEditMemberModal() {
		if (window.parent && document.formvalidator.isValid(document.getElementById("member-form"))) {
			return window.parent.' . $this->escape($function) . '(document.getElementById("jform_name").value);
		}
	}
');
?>
	<button id="applyBtn" type="button" onclick="Joomla.submitbutton('member.apply'); jEditMemberModal();"></button>
	<button id="saveBtn" type="button" onclick="Joomla.submitbutton('member.save'); jEditMemberModal();"></button>
	<button id="closeBtn" type="button" onclick="Joomla.submitbutton('member.cancel');"></button>

<div class="container-popup">
	<?php $this->setLayout('edit'); ?>
	<?php echo $this->loadTemplate(); ?>
</div>
