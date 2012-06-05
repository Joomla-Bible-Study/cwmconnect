<?php
/**
 * @version		$Id: edit.php 1.7.0 $
 * @package	com_churchdirectory
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
?>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=position&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="position-form" class="form-validate">
    <div class="width-60 fltlft">
        <fieldset class="adminform">
            <legend><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_POSITION') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_POSITION', $this->item->id); ?></legend>
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('name'); ?>
                    <?php echo $this->form->getInput('name'); ?></li>
                <li><?php echo $this->form->getLabel('alias'); ?>
                    <?php echo $this->form->getInput('alias'); ?></li>
                <li><?php echo $this->form->getLabel('published'); ?>
                    <?php echo $this->form->getInput('published'); ?></li>
                <li><?php echo $this->form->getLabel('language'); ?>
                    <?php echo $this->form->getInput('language'); ?></li>
                <li><?php echo $this->form->getLabel('id'); ?>
                    <?php echo $this->form->getInput('id'); ?></li>
            </ul>
        </fieldset>
    </div>
    <div class="width-40 fltrt">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_CHURCHDIRECTORY_FAMILY_MEMBERS'); ?></legend>
            <table class="adminlist">
                <thead>
                    <tr>
                        <th align="center"><?php echo JText::_('COM_CHURCHDIRECTORY_FIELD_NAME_LABEL'); ?></th>
                        <th align="center"><?php echo JText::_('COM_CHURCHDIRECTORY_ID_LABEL'); ?></th>

                    </tr>
                </thead>
                <tbody>

                    <?php echo $this->members ?>

                </tbody>
            </table>

        </fieldset>
    </div>
    <div>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="tooltype" value="" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
<div class="clr"></div>
