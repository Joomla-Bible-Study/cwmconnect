<?php
/**
 * Sube view member params
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
$fieldSets = $this->form->getFieldsets('params');

foreach ($fieldSets as $name => $fieldSet) :
	?>
	<div class="tab-pane" id="params-<?php echo $name; ?>">
		<?php
		if (isset($fieldSet->description) && trim($fieldSet->description))
		{
			echo '<p class="alert alert-info">' . $this->escape(JText::_($fieldSet->description)) . '</p>';
		}
		?>
		<?php foreach ($this->form->getFieldset($name) as $field) : ?>
			<div class="control-group">
				<div class="control-label"><?php echo $field->label; ?></div>
				<div class="controls"><?php echo $field->input; ?></div>
			</div>
		<?php endforeach; ?>
	</div>
<?php
endforeach;

