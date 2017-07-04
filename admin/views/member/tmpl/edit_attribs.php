<?php
/**
 * Sub view member attribs
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

// Start of Form
$fieldSets = $this->form->getFieldsets('attribs');
?>
<!-- Protected Access info -->
<?php
if ($this->access):
	foreach ($fieldSets as $name => $fieldSet) :
		if ($name === 'protected')
		{
			echo JHtml::_('bootstrap.addTab', 'myTab', 'protected', JText::_($fieldSet->label));
			?>
			<div>
				<?php if (isset($fieldSet->description) && trim($fieldSet->description))
				{
					?>
					<p class="tip"><?php echo $this->escape(JText::_($fieldSet->description)); ?></p>
				<?php } ?>
				<div class="clearfix"></div>
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('mstatus'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('mstatus'); ?>
					</div>
				</div>
				<?php foreach ($this->form->getFieldset($name) as $field) : ?>
					<?php if ($field->name != 'jform[attribs][memberstatusother]')
					{
						?>
						<div class="control-group">
							<div class="control-label"><?php echo $field->label; ?></div>
							<div class="controls"><?php echo $field->input; ?></div>
						</div>
					<?php } ?>
				<?php endforeach; ?>
			</div>
			<?php
			echo JHtml::_('bootstrap.endTab');
		}
	endforeach;
endif; ?>
<!-- End of Protected Access -->
