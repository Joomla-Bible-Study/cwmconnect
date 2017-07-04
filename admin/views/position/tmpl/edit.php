<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
?>
<script type="text/javascript">
	Joomla.submitbutton = function (task) {
		if (task == 'position.cancel' || document.formvalidator.isValid(document.id('position-form'))) {
			Joomla.submitform(task, document.getElementById('position-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=position&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="position-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="row-fluid">
		<!-- Begin Position -->
		<div class="span10 form-horizontal">
			<ul class="nav nav-tabs">
				<li class="active">
					<a href="#details"
					   data-toggle="tab"><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_POSITION') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_POSITION', $this->item->id); ?></a>
				</li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="details">
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
					</div>
				</div>
			</div>

			<input type="hidden" name="task" value=""/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
		<div class="span2 form-vertical">
			<h4><?php echo JText::_('COM_CHURCHDIRECTORY_POSITIONS_DETAILS'); ?></h4>
			<hr/>
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
			<hr/>
			<table class="table table-striped table-bordered">
				<thead class="thead-default">
				<tr>
					<th width="2%"><?php echo JText::_('COM_CHURCHDIRECTORY_FIELD_NAME_LABEL'); ?></th>
					<th><?php echo JText::_('COM_CHURCHDIRECTORY_ID_LABEL'); ?></th>

				</tr>
				</thead>
				<tbody>
				<?php
				if ($this->members != null): foreach ($this->members as $members) : ?>
					<tr>
						<th scope="row"><?php echo $members['id'] ?></th>
						<td><?php echo $this->escape($members['name']) ?></td>
					</tr>
					<?php
				endforeach;
				else:
					?>
					<tr>
						<td colspan="2" style="text-align: center">
							<?php echo JText::_('COM_CHURCHDIRECTORY_FIELD_NO_RECORDS'); ?>
						</td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</form>
