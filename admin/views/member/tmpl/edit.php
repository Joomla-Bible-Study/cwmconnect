<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$app   = JFactory::getApplication();
$input = $app->input;

$assoc = JLanguageAssociations::isEnabled();

JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function (task) {
		if (task == "member.cancel" || document.formvalidator.isValid(document.getElementById("member-form"))) {
			' . $this->form->getField("misc")->save() . '
			Joomla.submitform(task, document.getElementById("member-form"));
		}
	};
	jQuery(document).ready(function ($) {
		$("#jform_funitid").change(function () {
			var funitid = $("#jform_funitid").val();
			if (funitid == "-1") {
				$("#jform_attribs_familypostion-lbl").css("display", "none");
				$("#jform_attribs_familypostion_chzn").css("display", "none");
			} else {
				$("#jform_attribs_familypostion-lbl").css("display", "inline");
				$("#jform_attribs_familypostion_chzn").css("display", "");
			}
		})
			.change();
	});
');

// Fieldsets to not automatically render by /layouts/joomla/edit/params.php
$this->ignore_fieldsets = array('details', 'item_associations', 'jmetadata', 'protected');

// In case of modal
$isModal = $input->get('layout') == 'modal' ? true : false;
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="member-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
	<!-- Begin Member -->
	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_MEMBER') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_MEMBER', $this->item->id)); ?>
		<div class="row-fluid">
			<div class="span9">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span6">
						<?php echo $this->form->renderField('lname'); ?>
						<?php echo $this->form->renderField('lat'); ?>
						<?php echo $this->form->renderField('funitid'); ?>
						<?php echo $this->form->renderField('familypostion', 'attribs'); ?>
					</div>
					<div class="span6">
						<?php echo $this->form->renderField('surname'); ?>
						<?php echo $this->form->renderField('lng'); ?>
						<?php echo $this->form->renderField('sex', 'attribs'); ?>
						<?php echo $this->form->renderField('user_id'); ?>
					</div>
				</div>
				<?php if ($this->access): ?>
				<h2>Protected Content</h2>
				<hr />
				<div class="row-fluid form-horizontal-desktop">
					<div class="span6">
						<?php echo $this->form->renderField('mstatus'); ?>
					</div>
					<div class="span6">
							<?php echo $this->form->renderField('bpc_date', 'attribs'); ?>
							<?php echo $this->form->renderField('memberotherinfo', 'attribs'); ?>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<div class="span3">
				<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'misc', JText::_('COM_CHURCHDIRECTORY_FIELD_PARAMS_MISC_INFO_LABEL')); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="form-vertical">
				<?php echo $this->form->renderField('misc'); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'basic', JText::_('COM_CHURCHDIRECTORY_MEMBER_DETAILS')); ?>
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
				<div class="controls"><?php echo $this->form->getInput('postcode'); ?>
					- <?php echo $this->form->getInput('postcodeaddon'); ?></div>
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
				<div class="control-label"><?php echo $this->form->getLabel('spouse'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('spouse'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('children'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('children'); ?></div>
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

			<?php if ($this->age !== '0')
			{
				?>
				<div class="control-group">
					<div class="control-label">
						<label id="jform_age-lbl" for="jform_age" class="hasTip"
						       title="<?php echo JText::_('COM_CHURCHDIRECTORY_AGE_HASTIP'); ?> ">
							<?php echo JText::_('COM_CHURCHDIRECTORY_AGE_LABEL'); ?>
						</label>
					</div>
					<div class="controls">
						<input type="text" name="jform[age]" id="jform_age"
						       value="<?php echo $this->age . ' ' . JText::_('COM_CHURCHDIRECTORY_YEARS_OLD'); ?>"
						       class="readonly" size="10" readonly="readonly"/>
					</div>
				</div>
				<?php
			} ?>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span6">
				<?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
			</div>
			<div class="span6">
				<?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
	</div>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="view" value="member"/>
	<?php echo JHtml::_('form.token'); ?>
	<!-- End Member -->
</form>
