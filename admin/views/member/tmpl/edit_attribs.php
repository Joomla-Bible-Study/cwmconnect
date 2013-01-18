<?php
/**
 * Sube view member attribs
 *
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

// Predefine for Access
$itemacess = $this->state->params->get('protectedaccess');
$groups    = $this->groups;

if (isset($groups[$itemacess]))
{
	$access = true;
}
else
{
	$access = false;
}
// Start of Form
?>
<div class="tab-pane" id="kml-options">;
    <p>
		<?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_MEMBER_KML', $this->item->id); ?></p>

    <div class="control-group">
        <div class="control-label"><?php echo $this->form->getLabel('lat'); ?></div>
        <div class="controls"><?php echo $this->form->getInput('lat'); ?></div>
    </div>

    <div class="control-group">
        <div class="control-label"><?php echo $this->form->getLabel('lng'); ?>
			<?php echo $this->form->getInput('lng'); ?></div>
    </div>

    <div class="control-group">
        <div class="control-label"><?php echo $this->form->getLabel('visibility', 'params'); ?>
			<?php echo $this->form->getInput('visibility', 'params'); ?></div>
    </div>

    <div class="control-group">
        <div class="control-label"><?php echo $this->form->getLabel('open', 'params'); ?>
			<?php echo $this->form->getInput('open', 'params'); ?></div>
    </div>

    <div class="control-group">
        <div class="control-label"><?php echo $this->form->getLabel('gxballoonvisibility', 'params'); ?>
			<?php echo $this->form->getInput('gxballoonvisibility', 'params'); ?></div>
    </div>

    <div class="control-group">
        <div class="control-label"><?php echo $this->form->getLabel('scale', 'params'); ?>
			<?php echo $this->form->getInput('scale', 'params'); ?></div>
    </div>

    <!-- Protected Access info -->
    <div class="control-group">
        <div class="control-label"><?php $fieldSets = $this->form->getFieldsets('attribs'); ?></div>
    </div>
</div>

<?php
if ($access === true):
	foreach ($fieldSets as $name => $fieldSet) :
		if ($name === 'protected')
		{
			?>
        <div id="tab-pane" id="<?php echo $name . '-options'; ?>">
			<?php if (isset($fieldSet->description) && trim($fieldSet->description)) : ?>
            <p class="tip">
				<?php echo $this->escape(JText::_($fieldSet->description)); ?></p>
			<?php endif; ?>
			<?php foreach ($this->form->getFieldset($name) as $field) : ?>
			<?php if ($field->name == 'jform[attribs][memberstatusother]' && $this->form->getValue('memberstatus', 'attribs') == '2'): ?>
                <div class="control-group">
                    <div class="control-label"><?php echo $field->label; ?></div>
                    <div class="controls"><?php echo $field->input; ?></div>
                </div>
				<?php elseif ($field->name != 'jform[attribs][memberstatusother]'): ?>
                <div class="control-group">
                    <div class="control-label"><?php echo $field->label; ?></div>
                    <div class="controls"><?php echo $field->input; ?></div>
                </div>
				<?php endif; ?>
			<?php endforeach; ?>
        </div>
		<?php
		}
	endforeach;
endif;
// End of Protected Access
foreach ($fieldSets as $name => $fieldSet) :
	if ($name != 'protected')
	{
		?>
    <div class="tab-pane" id="<?php echo $name . '-options'; ?>">
		<?php if (isset($fieldSet->description) && trim($fieldSet->description)) : ?>
        <p class="tip">
			<?php echo $this->escape(JText::_($fieldSet->description)); ?></p>
		<?php endif; ?>
		<?php foreach ($this->form->getFieldset($name) as $field) : ?>
        <div class="control-group">
            <div class="control-label"><?php echo $field->label; ?></div>
            <div class="controls"><?php echo $field->input; ?></div>
        </div>
		<?php endforeach; ?>
    </div>
	<?php
	}
endforeach;
