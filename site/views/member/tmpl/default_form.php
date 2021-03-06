<?php
/**
 * Sub view member for form
 *
 * @package    ChurchDirectory.Site
 *
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
if (isset($this->error)) : ?>
<div class="churchdirectory-error">
	<?php echo $this->error; ?>
</div>
<?php endif; ?>

<div class="churchdirectory-form">
    <form id="churchdirectory-form" action="<?php echo JRoute::_('index.php'); ?>" method="post" class="form-validate">
        <fieldset>
            <legend><?php echo JText::_('COM_CHURCHDIRECTORY_FORM_LABEL'); ?></legend>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('churchdirectory_name'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('churchdirectory_name'); ?></div>
            </div>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('churchdirectory_email'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('churchdirectory_email'); ?></div>
            </div>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('churchdirectory_subject'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('churchdirectory_subject'); ?></div>
            </div>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('churchdirectory_message'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('churchdirectory_message'); ?></div>
            </div>
			<?php if ($this->params->get('show_email_copy'))
		{ ?>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('churchdirectory_email_copy'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('churchdirectory_email_copy'); ?></div>
            </div>
			<?php } ?>
			<?php // Dynamically load any additional fields from plugins. ?>
			<?php foreach ($this->form->getFieldsets() as $fieldset): ?>
			<?php if ($fieldset->name != 'member'): ?>
				<?php $fields = $this->form->getFieldset($fieldset->name); ?>
				<?php foreach ($fields as $field): ?>
                    <div class="control-group">
						<?php if ($field->hidden): ?>
                        <div class="controls">
							<?php echo $field->input; ?>
                        </div>
						<?php else: ?>
                        <div class="control-label">
							<?php echo $field->label; ?>
							<?php if (!$field->required && $field->type != "Spacer"): ?>
                            <span class="optional"><?php echo JText::_('COM_CHURCHDIRECTORY_OPTIONAL'); ?></span>
							<?php endif; ?>
                        </div>
                        <div class="controls"><?php echo $field->input; ?></div>
						<?php endif; ?>
                    </div>
					<?php endforeach; ?>
				<?php endif ?>
			<?php endforeach; ?>
            <div class="form-actions">
                <button class="btn btn-primary validate"
                        type="submit"><?php echo JText::_('COM_CHURCHDIRECTORY_MEMBER_SEND'); ?></button>
                <input type="hidden" name="option" value="com_churchdirectory"/>
                <input type="hidden" name="task" value="member.submit"/>
                <input type="hidden" name="return" value="<?php echo $this->return_page; ?>"/>
                <input type="hidden" name="id" value="<?php echo $this->member->slug; ?>"/>
				<?php echo JHtml::_('form.token'); ?>
            </div>
        </fieldset>
    </form>
</div>
