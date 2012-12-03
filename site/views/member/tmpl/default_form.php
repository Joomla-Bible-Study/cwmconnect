<?php
/**
 * Sub view member for form
 * @package		ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
if (isset($this->error)) :
    ?>
    <div class="churchdirectory-error">
        <?php echo $this->error; ?>
    </div>
<?php endif; ?>

<div class="churchdirectory-form">
    <form id="churchdirectory-form" action="<?php echo JRoute::_('index.php'); ?>" method="post" class="form-validate">
        <fieldset>
            <legend><?php echo JText::_('COM_CHURCHDIRECTORY_FORM_LABEL'); ?></legend>
            <dl>
                <dt><?php echo $this->form->getLabel('churchdirectory_name'); ?></dt>
                <dd><?php echo $this->form->getInput('churchdirectory_name'); ?></dd>
                <dt><?php echo $this->form->getLabel('churchdirectory_email'); ?></dt>
                <dd><?php echo $this->form->getInput('churchdirectory_email'); ?></dd>
                <dt><?php echo $this->form->getLabel('churchdirectory_subject'); ?></dt>
                <dd><?php echo $this->form->getInput('churchdirectory_subject'); ?></dd>
                <dt><?php echo $this->form->getLabel('churchdirectory_message'); ?></dt>
                <dd><?php echo $this->form->getInput('churchdirectory_message'); ?></dd>
                <?php if ($this->params->get('show_email_copy')) { ?>
                    <dt><?php echo $this->form->getLabel('churchdirectory_email_copy'); ?></dt>
                    <dd><?php echo $this->form->getInput('churchdirectory_email_copy'); ?></dd>
                <?php } ?>
                <?php //Dynamically load any additional fields from plugins. ?>
                <?php foreach ($this->form->getFieldsets() as $fieldset): ?>
                    <?php if ($fieldset->name != 'member'): ?>
                        <?php $fields = $this->form->getFieldset($fieldset->name); ?>
                        <?php foreach ($fields as $field): ?>
                            <?php if ($field->hidden): ?>
                                <?php echo $field->input; ?>
                            <?php else: ?>
                                <dt>
                                <?php echo $field->label; ?>
                                <?php if (!$field->required && $field->type != "Spacer"): ?>
                                    <span class="optional"><?php echo JText::_('COM_CHURCHDIRECTORY_OPTIONAL'); ?></span>
                                <?php endif; ?>
                                </dt>
                                <dd><?php echo $field->input; ?></dd>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif ?>
                <?php endforeach; ?>
                <dt></dt>
                <dd><button class="button validate" type="submit"><?php echo JText::_('COM_CHURCHDIRECTORY_MEMBER_SEND'); ?></button>
                    <input type="hidden" name="option" value="com_churchdirectory" />
                    <input type="hidden" name="task" value="member.submit" />
                    <input type="hidden" name="return" value="<?php echo $this->return_page; ?>" />
                    <input type="hidden" name="id" value="<?php echo $this->member->slug; ?>" />
                    <?php echo JHtml::_('form.token'); ?>
                </dd>
            </dl>
        </fieldset>
    </form>
</div>
