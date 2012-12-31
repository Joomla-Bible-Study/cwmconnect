<?php
/**
 * Edit for FamilyUnit
 * @package	ChurchDirectory.Admin
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
?>
<script type="text/javascript">
    Joomla.submitbutton = function(task)
    {
        if (task == 'familyunit.cancel' || document.formvalidator.isValid(document.id('familyunit-form'))) {
            Joomla.submitform(task, document.getElementById('familyunit-form'));
        }
        else {
            alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=familyunit&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="familyunit-form" class="form-validate">
    <div class="width-60 fltlft">
        <fieldset class="adminform">
            <legend><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_FAMILYUNIT') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_FAMILYUNIT', $this->item->id); ?></legend>
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('name'); ?>
                    <?php echo $this->form->getInput('name'); ?></li>
                <li><?php echo $this->form->getLabel('alias'); ?>
                    <?php echo $this->form->getInput('alias'); ?></li>
                <li><?php echo $this->form->getLabel('access'); ?>
                    <?php echo $this->form->getInput('access'); ?></li>
                <li><?php echo $this->form->getLabel('published'); ?>
                    <?php echo $this->form->getInput('published'); ?></li>
                <li><?php echo $this->form->getLabel('language'); ?>
                    <?php echo $this->form->getInput('language'); ?></li>
                <li><?php echo $this->form->getLabel('id'); ?>
                    <?php echo $this->form->getInput('id'); ?></li>
            </ul>
            <div class="clr"></div>
            <?php echo $this->form->getLabel('description'); ?>
            <div class="clr"></div>
            <?php echo $this->form->getInput('description'); ?>
        </fieldset>
    </div>
    <div class="width-40 fltrt">

        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_CHURCHDIRECTORY_FAMILYUNITE_DETAILS'); ?></legend>
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('image'); ?>
                    <?php echo $this->form->getInput('image'); ?></li>
                <li>
                    <img src="../<?php echo $this->form->getValue('image'); ?>" name="imagelib" style="border:2px solid;
                         border-radius:25px;
                         -moz-border-radius:25px; padding: 10px; width: 150px;" alt="<?php echo JText::_('Preview'); ?>" />
                </li>
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

                    <?php
                    if (count($this->members) > 0 && $this->members) :
                        foreach ($this->members as $i => $item) :
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td align="center">
                                    <?php $link = 'index.php?option=com_churchdirectory&amp;task=churchdirectory.edit&amp;id=' . (int) $item->id . '&amp;tmpl=component&amp;layout=modal'; ?>
                                    <!-- <a class="modal" href="<?php //echo $link;   ?>" rel="{handler: 'iframe', size: {x: 900, y: 550}}" title="<?php //echo $this->escape($item->name) ? $this->escape($item->name) : 'ID: ' . $this->escape($item->id);   ?>"> -->
                                    <?php echo ($this->escape($item->name) ? $this->escape($item->name) : 'ID: ' . $this->escape($item->id)); ?>
                                    <!-- </a> -->
                                </td>
                                <td align="center">
                                    <?php echo $item->id; ?>
                                </td>

                            </tr>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <tr>
                            <td colspan="4" align="center"><?php echo JText::_('COM_CHURCHDIRECTORY_NO_MEMBERS'); ?></td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>

        </fieldset>

        <fieldset class="adminform">
            <legend><?php echo JText::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
            <ul class="adminformlist">

                <li><?php echo $this->form->getLabel('created_by'); ?>
                    <?php echo $this->form->getInput('created_by'); ?></li>

                <li><?php echo $this->form->getLabel('created_by_alias'); ?>
                    <?php echo $this->form->getInput('created_by_alias'); ?></li>

                <li><?php echo $this->form->getLabel('created'); ?>
                    <?php echo $this->form->getInput('created'); ?></li>

                <li><?php echo $this->form->getLabel('publish_up'); ?>
                    <?php echo $this->form->getInput('publish_up'); ?></li>

                <li><?php echo $this->form->getLabel('publish_down'); ?>
                    <?php echo $this->form->getInput('publish_down'); ?></li>

                <?php if ($this->item->modified_by) : ?>
                    <li><?php echo $this->form->getLabel('modified_by'); ?>
                        <?php echo $this->form->getInput('modified_by'); ?></li>

                    <li><?php echo $this->form->getLabel('modified'); ?>
                        <?php echo $this->form->getInput('modified'); ?></li>
                <?php endif; ?>

            </ul>
        </fieldset>

    </div>
    <div class="clr"></div>
    <div>
        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>