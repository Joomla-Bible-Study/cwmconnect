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
?>
<script type="text/javascript">
	Joomla.submitbutton = function (task) {
		if (task == 'familyunit.cancel' || document.formvalidator.isValid(document.id('familyunit-form'))) {
			Joomla.submitform(task, document.getElementById('familyunit-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=familyunit&layout=edit&id=' . (int)
	$this->item->id); ?>" method="post" name="adminForm" id="familyunit-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="row-fluid">
		<div class="span9  form-horizontal">
			<div class="clearfix"></div>
			<ul class="nav nav-tabs">
				<li class="active"><a href="#details"
				                      data-toggle="tab"><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_FAMILYUNIT') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_FAMILYUNIT', $this->item->id); ?></a>
				</li>
				<li><a href="#publishing"
				       data-toggle="tab"><?php echo JText::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></a></li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="details">
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
						<?php echo $this->form->getInput('description'); ?>
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
				</div>

			</div>
			<input type="hidden" name="task" value=""/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
		<div class="span3 form-vertical">

			<!-- Begin Sidebar -->
			<h4><?php echo JText::_('JDETAILS'); ?></h4>
			<hr/>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('id'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('id'); ?>
				</div>
			</div>
			<div class="control-group">
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
					<?php echo $this->form->getLabel('language'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('language'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('image'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('image'); ?></div>
			</div>
			<h4><?php echo JText::_('COM_CHURCHDIRECTORY_FAMILY_MEMBERS'); ?></h4>
			<table class="table table-striped table-bordered">
				<thead class="thead-default">
				<tr>
					<th width="2%"><?php echo JText::_('COM_CHURCHDIRECTORY_ID_LABEL'); ?></th>
					<th><?php echo JText::_('COM_CHURCHDIRECTORY_FIELD_NAME_LABEL'); ?></th>

				</tr>
				</thead>
				<tbody>
				<?php
				if (count($this->members) > 0 && $this->members) :
					foreach ($this->members as $i => $item) :
						?>
						<tr>
							<th scope="row"><?php echo $item->id; ?></th>
							<td>
								<?php echo($this->escape($item->name) ? $this->escape($item->name) : 'ID: ' . $this->escape($item->id)); ?>
							</td>
						</tr>
						<?php
					endforeach;
				else:
					?>
					<tr>
						<td colspan="2"
						    style="text-align: center"><?php echo JText::_('COM_CHURCHDIRECTORY_NO_MEMBERS'); ?></td>
					</tr>
				<?php endif; ?>

				</tbody>
			</table>
			<!-- End Sidebar -->
		</div>
	</div>
</form>
