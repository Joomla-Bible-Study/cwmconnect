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

// Start of Form
?>
<div class="accordion-group">
    <div class="accordion-heading">
        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion6" href="#kml">
			<?php echo JText::_('COM_CHURCHDIRECTORY_KML_OPTIONS');?>
        </a>
    </div>
    <div id="kml" class="accordion-body collapse">
        <div class="accordion-inner">
            <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_MEMBER_KML', $this->item->id); ?></p>

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
        </div>
    </div>
</div>

<!-- Protected Access info -->
<?php $fieldSets = $this->form->getFieldsets('attribs');
if ($this->access):
	foreach ($fieldSets as $name => $fieldSet) :
		if ($name === 'protected')
		{
			?>
        <div class="accordion-group">
            <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion6"
                   href="#<?php echo $name . 'options'; ?>">
					<?php echo JText::_($fieldSet->label);?>
                </a>
            </div>
            <div id="<?php echo $name . 'options'; ?>" class="accordion-body collapse">
                <div class="accordion-inner">
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
            </div>
        </div>
		<?php
		}
	endforeach;
endif;?>
<!-- End of Protected Access -->
<?php foreach ($fieldSets as $name => $fieldSet) :
	if ($name != 'protected' && $name != 'memberstate')
	{
		?>
    <div class="accordion-group">
        <div class="accordion-heading">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion6"
               href="#<?php echo $name . 'options'; ?>">
				<?php echo JText::_($fieldSet->label);?>
            </a>
        </div>
        <div id="<?php echo $name . 'options'; ?>" class="accordion-body collapse">
            <div class="accordion-inner">
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
        </div>
    </div>
	<?php
	}
endforeach;
