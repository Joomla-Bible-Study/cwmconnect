<?php
/**
 * Edit dirheader
 *
 * @package      ChurchDirectory.Admin
 * @copyright    (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
?>
<script type="text/javascript">
    Joomla.submitbutton = function (task) {
        if (task == 'dirheader.cancel' || document.formvalidator.isValid(document.id('dirheader-form'))) {
            Joomla.submitform(task, document.getElementById('dirheader-form'));
        }
        else {
            alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=dirheader&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="dirheader-form" class="form-validate">
    <div class="row-fluid">
        <div class="span9 form-horizontal">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#details"
                                      data-toggle="tab"><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_DIRHEADER') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_DIRHEADER', $this->item->id); ?></a>
                </li>
                <li><a href="#publishing"
                       data-toggle="tab"><?php echo JText::_('JGLOBAL_FIELDSET_PUBLISHING');?></a></li>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="details">
                    <div class="control-group form-inline">
						<?php echo $this->form->getLabel('name'); ?><?php echo $this->form->getInput('name'); ?>
						<?php echo $this->form->getLabel('alias'); ?><?php echo $this->form->getInput('alias'); ?>
						<?php echo $this->form->getLabel('id'); ?><?php echo $this->form->getInput('id'); ?>
                    </div>
                    <div class="clr"></div>
					<?php echo $this->form->getLabel('description'); ?>
                    <div class="clr"></div>
					<?php echo $this->form->getInput('description'); ?>
                </div>
                <div class="tab-pane" id="publishing">
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
                    </div>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('created_by_alias'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('created_by_alias'); ?></div>
                    </div>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('created'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('created'); ?></div>
                    </div>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('publish_up'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('publish_up'); ?></div>
                    </div>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('publish_down'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('publish_down'); ?></div>
                    </div>
					<?php if ($this->item->modified_by) : ?>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('modified_by'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('modified_by'); ?></div>
                    </div>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('modified'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('modified'); ?></div>
                    </div>
					<?php endif; ?>
                </div>
	            <div class="clearfix"></div>
                <div class="row-fluid">
                    <div class="span6">
                        <div class="control-group churchdirectory_paddingtop20">
                            <div class="control-label"><?php echo $this->form->getLabel('image'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('image'); ?></div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="task" value=""/>
				<?php echo JHtml::_('form.token'); ?>
            </div>
        </div>
        <!-- End DirHeader -->
        <!-- Begin Sidebar -->
        <div class="span3">
            <h4><?php echo JText::_('COM_CHURCHDIRECTORY_DIRHEADERE_DETAILS'); ?></h4>
            <hr/>
            <fieldset class="form-vertical">
                <div class="control-group">
                    <div class="control-group">
                        <div class="controls">
							<?php echo $this->form->getValue('name'); ?>
                        </div>
                    </div>
                    <div class="control-label">
						<?php echo $this->form->getLabel('published'); ?>
                    </div>
                    <div class="controls">
						<?php echo $this->form->getInput('published'); ?>
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
						<?php echo $this->form->getLabel('access'); ?>
                    </div>
                    <div class="controls">
						<?php echo $this->form->getInput('access'); ?>
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
						<?php echo $this->form->getLabel('featured'); ?>
                    </div>
                    <div class="controls">
						<?php echo $this->form->getInput('featured'); ?>
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
						<?php echo $this->form->getLabel('language'); ?>
                    </div>
                    <div class="controls">
						<?php echo $this->form->getInput('language'); ?>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>
</form>
