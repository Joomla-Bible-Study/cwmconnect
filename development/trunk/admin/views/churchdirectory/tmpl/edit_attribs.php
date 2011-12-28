<?php
/**
 * @version             $Id: edit_attribs.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_CONTACT_KML_DETAILS'), 'kml-options');
?>
<fieldset class="panelform">
    <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_CONTACT_KML', $this->item->id); ?></p>

    <ul class="adminformlist">
        <li><?php echo $this->form->getLabel('lat'); ?>
            <?php echo $this->form->getInput('lat'); ?></li>
        <li><?php echo $this->form->getLabel('lng'); ?>
            <?php echo $this->form->getInput('lng'); ?></li>
        <li><?php echo $this->form->getLabel('visibility', 'params'); ?>
            <?php echo $this->form->getInput('visibility', 'params'); ?></li>
        <li><?php echo $this->form->getLabel('open', 'params'); ?>
            <?php echo $this->form->getInput('open', 'params'); ?></li>
        <li><?php echo $this->form->getLabel('gxballoonvisibility', 'params'); ?>
            <?php echo $this->form->getInput('gxballoonvisibility', 'params'); ?></li>
        <li><?php echo $this->form->getLabel('scale', 'params'); ?>
            <?php echo $this->form->getInput('scale', 'params'); ?></li>
    </ul>
</fieldset>

<?php $fieldSets = $this->form->getFieldsets('attribs'); ?>
<?php foreach ($fieldSets as $name => $fieldSet) : ?>
    <?php echo JHtml::_('sliders.panel', JText::_($fieldSet->label), $name . '-options'); ?>
    <?php if (isset($fieldSet->description) && trim($fieldSet->description)) : ?>
        <p class="tip"><?php echo $this->escape(JText::_($fieldSet->description)); ?></p>
    <?php endif; ?>
        <div class="clearfix"></div>
    <fieldset class="panelform">
        <ul class="adminformlist">
            <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                <li><?php echo $field->label; ?>
                    <?php echo $field->input; ?></li>
            <?php endforeach; ?>
        </ul>
    </fieldset>
<?php endforeach; ?>
