<?php
/**
 * Edit view member
 *
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Nmo direct access
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
?>
<script type="text/javascript">
    Joomla.submitbutton = function (task) {
        if (task == 'member.cancel' || document.formvalidator.isValid(document.id('member-form'))) {
		<?php echo $this->form->getField('misc')->save(); ?>
            Joomla.submitform(task, document.getElementById('member-form'));
        }
        else {
            alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="member-form" class="form-validate">
<div class="row-fluid">
<!-- Begin Member -->
<div class="span10 form-horizontal">
<fieldset>
<ul class="nav nav-tabs">
    <li class="active"><a href="#details"
                          data-toggle="tab"><?php echo empty($this->item->id) ? JText::_('COM_CONTACT_NEW_CONTACT') : JText::sprintf('COM_CONTACT_EDIT_CONTACT', $this->item->id); ?></a>
    </li>
    <li><a href="#publishing" data-toggle="tab"><?php echo JText::_('JGLOBAL_FIELDSET_PUBLISHING');?></a></li>
    <li><a href="#basic" data-toggle="tab"><?php echo JText::_('COM_CONTACT_CONTACT_DETAILS');?></a></li>
    <li><a href="#im" data-toggle="tab">IM</a></li>
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
        <legend><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_MEMBER') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_MEMBER', $this->item->id); ?></legend>
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
            <div class="control-label"><?php echo $this->form->getLabel('published'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('published'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('access'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('access'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('ordering'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('ordering'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('featured'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('featured'); ?></div>
        </div>

        <div class="control-group">
            <div class="control-label"><?php echo $this->form->getLabel('language'); ?></div>
            <div class="controls"><?php echo $this->form->getInput('language'); ?></div>
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
    </div>

    <div class="tab-pane" id="basic">
        <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_DETAILS', $this->item->id); ?></p>

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

            <li><?php echo $this->form->getLabel('spouse'); ?>
				<?php echo $this->form->getInput('spouse'); ?></li>

            <li><?php echo $this->form->getLabel('children'); ?>
				<?php echo $this->form->getInput('children'); ?></li>

            <li><?php echo $this->form->getLabel('sortname1'); ?>
				<?php echo $this->form->getInput('sortname1'); ?></li>

            <li><?php echo $this->form->getLabel('sortname2'); ?>
				<?php echo $this->form->getInput('sortname2'); ?></li>

            <li><?php echo $this->form->getLabel('sortname3'); ?>
				<?php echo $this->form->getInput('sortname3'); ?></li>

            <li><?php echo $this->form->getLabel('birthdate'); ?>
				<?php echo $this->form->getInput('birthdate'); ?></li>

            <li><?php echo $this->form->getLabel('anniversary'); ?>
				<?php echo $this->form->getInput('anniversary'); ?></li>

			<?php if ($this->age != '0'): ?>
            <li><label id="jform_age-lbl" for="jform_age" class="hasTip"
                       title="<?php echo JText::_('COM_CHURCHDIRECTORY_AGE_HASTIP'); ?> "><?php echo JText::_('COM_CHURCHDIRECTORY_AGE_LABEL'); ?></label>
                <input type="text" name="jform[age]" id="jform_age"
                       value="<?php echo $this->age . ' ' . JText::_('COM_CHURCHDIRECTORY_YEARS_OLD'); ?>"
                       class="readonly" size="10" readonly="readonly"/></li>
			<?php endif; ?>
    </div>

    <div class="tab-pane" id="im">
        <li><?php echo $this->form->getLabel('skype'); ?>
			<?php echo $this->form->getInput('skype'); ?></li>

        <li><?php echo $this->form->getLabel('yahoo_msg'); ?>
			<?php echo $this->form->getInput('yahoo_msg'); ?></li>
    </div>

	<?php echo $this->loadTemplate('attribs'); ?>

	<?php echo $this->loadTemplate('params'); ?>

	<?php echo $this->loadTemplate('metadata'); ?>

</div>
</fieldset>
<input type="hidden" name="task" value=""/>
<input type="hidden" name="return"
       value="<?php echo JRequest::getCmd('return'); ?>"/>
<?php echo JHtml::_('form.token'); ?>
</div>
<!-- End Sidebar -->    <!-- Begin Sidebar -->
<div class="span2">
    <h4><?php echo JText::_('JDETAILS');?></h4>
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
<!-- End Sidebar -->
</form>
