<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive'); // Predefine for Access
?>
<script type="text/javascript">
    Joomla.submitbutton = function (task) {
        if (task == 'member.cancel' || document.formvalidator.isValid(document.id('member-form'))) {
		<?php echo $this->form->getField('misc')->save(); ?>
            Joomla.submitform(task, document.getElementById('member-form'));
        }
    }
</script>
<script type="text/javascript">
	function setClassFamilyUnite(thisForm){
		switch (document.getElementById('funitid').value) {
        case '-1':
            document.getElementById('jform_attribs_familypostion-lbl').style = 'display:hiddon';
            break;
        default:
            document.getElementById('jform_attribs_familypostion-lbl').style = '';
            break;
    }
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="member-form" class="form-validate form-horizontal">
<div class="row-fluid">
<!-- Begin Member -->
<div class="span8 form-horizontal">
<ul class="nav nav-tabs">
    <li class="active"><a href="#details"
                          data-toggle="tab"><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_MEMBER') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_MEMBER', $this->item->id); ?></a>
    </li>
    <li><a href="#publishing" data-toggle="tab"><?php echo JText::_('JGLOBAL_FIELDSET_PUBLISHING');?></a></li>
    <li><a href="#basic" data-toggle="tab"><?php echo JText::_('COM_CHURCHDIRECTORY_MEMBER_DETAILS');?></a></li>
	<?php
	$fieldSets = $this->form->getFieldsets('params');
	foreach ($fieldSets as $name => $fieldSet) :
		?>
        <li><a href="#params-<?php echo $name;?>" data-toggle="tab"><?php echo JText::_($fieldSet->label);?></a>
        </li>
		<?php endforeach; ?>
	<?php
	$fieldSets = $this->form->getFieldsets('metadata');
	foreach ($fieldSets as $name => $fieldSet) :
		?>
        <li><a href="#metadata-<?php echo $name;?>"
               data-toggle="tab"><?php echo JText::_($fieldSet->label);?></a></li>
		<?php endforeach; ?>

</ul>
<div class="tab-content">
    <div class="tab-pane active" id="details">
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('name'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('lname'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('lname'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('funitid'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('funitid'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('familypostion', 'attribs'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('familypostion', 'attribs'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('user_id'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('user_id'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('catid'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('catid'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('ordering'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('ordering'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('id'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('misc'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('misc'); ?></div>
        </div>
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

		<?php if ($this->item->hits) : ?>
        <div class="control-group">
            <div class="control-label">
				<?php echo $this->form->getLabel('hits'); ?>
            </div>
            <div class="controls">
				<?php echo $this->form->getInput('hits'); ?>
            </div>
        </div>
		<?php endif; ?>
    </div>

    <div class="tab-pane" id="basic">
        <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_DETAILS', $this->item->id); ?></p>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('image'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('image'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('con_position'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('con_position'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('email_to'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('email_to'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('address'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('address'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('suburb'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('suburb'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('state'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('postcode'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('postcode'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('country'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('country'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('telephone'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('telephone'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('mobile'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('mobile'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('fax'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('fax'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('webpage'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('webpage'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('sortname1'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('sortname1'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('sortname2'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('sortname2'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('sortname3'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('sortname3'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('birthdate'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('birthdate'); ?></div>
        </div>
        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('anniversary'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('anniversary'); ?></div>
        </div>

		<?php if ($this->age != '0'): ?>
        <div class="control-group">
            <div class="control-label"><label id="jform_age-lbl" for="jform_age" class="hasTip"
                                              title="<?php echo JText::_('COM_CHURCHDIRECTORY_AGE_HASTIP'); ?> "><?php echo JText::_('COM_CHURCHDIRECTORY_AGE_LABEL'); ?></label>
            </div>
            <div class="controls"><input type="text" name="jform[age]" id="jform_age"
                                         value="<?php echo $this->age . ' ' . JText::_('COM_CHURCHDIRECTORY_YEARS_OLD'); ?>"
                                         class="readonly" size="10" readonly="readonly"/></div>
        </div>
		<?php endif; ?>
    </div>

	<?php echo $this->loadTemplate('params'); ?>

	<?php echo $this->loadTemplate('metadata'); ?>

</div>
</fieldset>
<input type="hidden" name="task" value=""/>
<input type="hidden" name="return"
       value="<?php echo JRequest::getCmd('return'); ?>"/>
<?php echo JHtml::_('form.token'); ?>
</div>
<!-- End Sidebar -->
<!-- Begin Sidebar -->
<div class="span4">
    <fieldset class="form-vertical">
        <div class="accordion" id="accordion6">
            <div class="accordion-group">
                <div class="accordion-heading">
                    <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion6" href="#detailsr">
						<?php echo JText::_('JDETAILS');?>
                    </a>
                </div>
                <div id="detailsr" class="accordion-body collapse in">
                    <div class="accordion-inner">
                        <div class="control-group">
                            <div class="control-group">
                                <div class="control-label">
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
                    </div>
                </div>
            </div>
            <div class="accordion-group">
                <div class="accordion-heading">
                    <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion6" href="#im">
						<?php echo JText::_('COM_CHURCHDIRECTORY_IM_DETAILS');?>
                    </a>
                </div>
                <div id="im" class="accordion-body collapse">
                    <div class="accordion-inner">
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('skype'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('skype'); ?></div>
                        </div>

                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('yahoo_msg'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('yahoo_msg'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

			<?php echo $this->loadTemplate('attribs'); ?>
        </div>
    </fieldset>
</div>
<!-- End Sidebar -->
</form>
