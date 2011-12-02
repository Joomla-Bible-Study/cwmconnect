<?php
/**
 * @version		$Id: edit.php $
 * @package		Joomla.Administrator
 * @subpackage	com_churchdirectory
 * @copyright	Copyright (C) 2005 - 2011 All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'churchdirectory.cancel' || document.formvalidator.isValid(document.id('churchdirectory-form'))) {
			<?php echo $this->form->getField('misc')->save(); ?>
			Joomla.submitform(task, document.getElementById('churchdirectory-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="churchdirectory-form" class="form-validate">
	<div class="width-60 fltlft">
		<fieldset class="adminform">
			<legend><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_CONTACT') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_CONTACT', $this->item->id); ?></legend>
			<ul class="adminformlist">
				<li><?php echo $this->form->getLabel('name'); ?>
				<?php echo $this->form->getInput('name'); ?></li>

                                <li><?php echo $this->form->getLabel('lname'); ?>
				<?php echo $this->form->getInput('lname'); ?></li>

				<li><?php echo $this->form->getLabel('alias'); ?>
				<?php echo $this->form->getInput('alias'); ?></li>

				<li><?php echo $this->form->getLabel('user_id'); ?>
				<?php echo $this->form->getInput('user_id'); ?></li>

				<li><?php echo $this->form->getLabel('catid'); ?>
				<?php echo $this->form->getInput('catid'); ?></li>

				<li><?php echo $this->form->getLabel('published'); ?>
				<?php echo $this->form->getInput('published'); ?></li>

				<li><?php echo $this->form->getLabel('access'); ?>
				<?php echo $this->form->getInput('access'); ?></li>

				<li><?php echo $this->form->getLabel('ordering'); ?>
				<?php echo $this->form->getInput('ordering'); ?></li>

				<li><?php echo $this->form->getLabel('featured'); ?>
				<?php echo $this->form->getInput('featured'); ?></li>

				<li><?php echo $this->form->getLabel('language'); ?>
				<?php echo $this->form->getInput('language'); ?></li>

				<li><?php echo $this->form->getLabel('id'); ?>
				<?php echo $this->form->getInput('id'); ?></li>
			</ul>
			<div class="clr"></div>
			<?php echo $this->form->getLabel('misc'); ?>
			<div class="clr"></div>
			<?php echo $this->form->getInput('misc'); ?>
		</fieldset>
	</div>

	<div class="width-40 fltrt">
		<?php echo  JHtml::_('sliders.start', 'churchdirectory-slider'); ?>
			<?php echo JHtml::_('sliders.panel',JText::_('JGLOBAL_FIELDSET_PUBLISHING'), 'publishing-details'); ?>

			<fieldset class="panelform">
				<ul class="adminformlist">

					<li><?php echo $this->form->getLabel('created_by'); ?>
					<?php echo $this->form->getInput('created_by'); ?></li>

					<li><?php echo $this->form->getLabel('created_by_alias'); ?>
					<?php echo $this->form->getInput('created_by_alias'); ?></li>

					<li><?php echo $this->form->getLabel('created'); ?>
					<?php echo $this->form->getInput('created'); ?></li>

					<li><?php echo $this->form->getLabel('publish_up'); ?>
					<?php echo $this->form->getInput('publish_up'); ?></li>

					<li><?php echo $this->form->getLabel('publish_down'); ?>
					<?php echo $this->form->getInput('publish_down'); ?></li>

					<?php if ($this->item->modified_by) : ?>
						<li><?php echo $this->form->getLabel('modified_by'); ?>
						<?php echo $this->form->getInput('modified_by'); ?></li>

						<li><?php echo $this->form->getLabel('modified'); ?>
						<?php echo $this->form->getInput('modified'); ?></li>
					<?php endif; ?>

				</ul>
			</fieldset>
			<?php echo JHtml::_('sliders.panel',JText::_('COM_CHURCHDIRECTORY_CONTACT_DETAILS'), 'basic-options'); ?>


			<fieldset class="panelform">
				<p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_DETAILS', $this->item->id); ?></p>

				<ul class="adminformlist">
					<li><?php echo $this->form->getLabel('image'); ?>
					<?php echo $this->form->getInput('image'); ?></li>

					<li><?php echo $this->form->getLabel('con_position'); ?>
					<?php echo $this->form->getInput('con_position'); ?></li>

					<li><?php echo $this->form->getLabel('email_to'); ?>
					<?php echo $this->form->getInput('email_to'); ?></li>

					<li><?php echo $this->form->getLabel('address'); ?>
					<?php echo $this->form->getInput('address'); ?></li>

					<li><?php echo $this->form->getLabel('suburb'); ?>
					<?php echo $this->form->getInput('suburb'); ?></li>

					<li><?php echo $this->form->getLabel('state'); ?>
					<?php echo $this->form->getInput('state'); ?></li>

					<li><?php echo $this->form->getLabel('postcode'); ?>
					<?php echo $this->form->getInput('postcode'); ?></li>

                                        <li><?php echo $this->form->getLabel('postcodeaddon'); ?>
					<?php echo $this->form->getInput('postcodeaddon'); ?></li>

					<li><?php echo $this->form->getLabel('country'); ?>
					<?php echo $this->form->getInput('country'); ?></li>

					<li><?php echo $this->form->getLabel('telephone'); ?>
					<?php echo $this->form->getInput('telephone'); ?></li>

					<li><?php echo $this->form->getLabel('mobile'); ?>
					<?php echo $this->form->getInput('mobile'); ?></li>

					<li><?php echo $this->form->getLabel('fax'); ?>
					<?php echo $this->form->getInput('fax'); ?></li>

					<li><?php echo $this->form->getLabel('webpage'); ?>
					<?php echo $this->form->getInput('webpage'); ?></li>

					<li><?php echo $this->form->getLabel('sortname1'); ?>
					<?php echo $this->form->getInput('sortname1'); ?></li>

					<li><?php echo $this->form->getLabel('sortname2'); ?>
					<?php echo $this->form->getInput('sortname2'); ?></li>

					<li><?php echo $this->form->getLabel('sortname3'); ?>
					<?php echo $this->form->getInput('sortname3'); ?></li>
				</ul>
			</fieldset>

                        <?php echo JHtml::_('sliders.panel',JText::_('COM_CHURCHDIRECTORY_CONTACT_IM'), 'basic-options'); ?>

                        <fieldset class="panelform">
                            <ul class="adminformlist">
                                        <li><?php echo $this->form->getLabel('skype'); ?>
					<?php echo $this->form->getInput('skype'); ?></li>

                                        <li><?php echo $this->form->getLabel('yahoo_msg'); ?>
					<?php echo $this->form->getInput('yahoo_msg'); ?></li>
                            </ul>
                        </fieldset>

                        <?php echo JHtml::_('sliders.panel',JText::_('COM_CHURCHDIRECTORY_CONTACT_DETAILS'), 'basic-options'); ?>

                        <fieldset class="panelform">
                            <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_CONTACT_MEMBERINFO', $this->item->id); ?></p>
                            <ul class="adminformlist">
                                        <li><?php echo $this->form->getLabel('membstatus'); ?>
					<?php echo $this->form->getInput('membstatus'); ?></li>

                                        <li><?php echo $this->form->getLabel('elder'); ?>
					<?php echo $this->form->getInput('elder'); ?></li>
                            </ul>
				<p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_CONTACT_KMLOPTIONS', $this->item->id); ?></p>

				<ul class="adminformlist">
                                        <li><?php echo $this->form->getLabel('lat'); ?>
					<?php echo $this->form->getInput('lat'); ?></li>

                                        <li><?php echo $this->form->getLabel('lng'); ?>
					<?php echo $this->form->getInput('lng'); ?></li>

                                        <li><?php echo $this->form->getLabel('team'); ?>
					<?php echo $this->form->getInput('team'); ?></li>

                                        <li><?php echo $this->form->getLabel('teamicon'); ?>
					<?php echo $this->form->getInput('teamicon'); ?></li>
                                </ul>
                        </fieldset>

			<?php echo $this->loadTemplate('params'); ?>

			<?php echo $this->loadTemplate('metadata'); ?>
		<?php echo JHtml::_('sliders.end'); ?>
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
<div class="clr"></div>
