<?php
/**
 * Sube view member attribs
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
//Predefine for Access
$itemacess = $this->state->params->get('protectedaccess');
$groups = $this->groups;
if (isset($groups[$itemacess])) {
    $access = true;
} else {
    $access = false;
}
// Start of Form
echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_MEMBER_KML_DETAILS'), 'kml-options');
?>
<fieldset class="adminform">
    <p>
        <?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_MEMBER_KML', $this->item->id); ?></p>

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
<!-- Protected Access info -->

<?php
if ($access === true):
    foreach ($fieldSets as $name => $fieldSet) :
        if ($name === 'protected') {
            ?>
            <?php echo JHtml::_('sliders.panel', JText::_($fieldSet->label), $name . '-options'); ?>
            <?php if (isset($fieldSet->description) && trim($fieldSet->description)) : ?>
                <p class="tip">
                    <?php echo $this->escape(JText::_($fieldSet->description)); ?></p>
            <?php endif; ?>
            <div class="clearfix"></div>
            <fieldset class="adminform">
                <ul class="adminformlist">
                    <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                        <?php if ($field->name == 'jform[attribs][memberstatusother]' && $this->form->getValue('memberstatus', 'attribs') == '2'): ?>
                            <li><?php echo $field->label; ?>
                                <?php echo $field->input; ?></li>
                        <?php elseif ($field->name != 'jform[attribs][memberstatusother]'): ?>
                            <li><?php echo $field->label; ?>
                                <?php echo $field->input; ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </fieldset>

            <?php
        }
    endforeach;
endif;
// End of Protected Access
foreach ($fieldSets as $name => $fieldSet) :
    if ($name != 'protected') {
        echo JHtml::_('sliders.panel', JText::_($fieldSet->label), $name . '-options');
        ?>
        <?php if (isset($fieldSet->description) && trim($fieldSet->description)) : ?>
            <p class="tip">
                <?php echo $this->escape(JText::_($fieldSet->description)); ?></p>
        <?php endif; ?>
        <div class="clearfix"></div>
        <fieldset class="adminform">
            <ul class="adminformlist">
                    <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                    <li><?php echo $field->label; ?>
                        <?php echo $field->input; ?></li>
                <?php endforeach; ?>
            </ul>
        </fieldset>

        <?php
    }
endforeach;
