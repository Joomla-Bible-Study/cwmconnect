<?php
/**
 * Edit view member
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
?>
<script type="text/javascript">
    Joomla.submitbutton = function(task)
    {
        if (task == 'member.cancel' || document.formvalidator.isValid(document.id('member-form'))) {
<?php echo $this->form->getField('misc')->save(); ?>
            Joomla.submitform(task, document.getElementById('member-form'));
        }
        else {
            alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="member-form" class="form-validate">
    <div class="width-60 fltlft">
        <fieldset class="adminform">
            <legend><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_MEMBER') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_MEMBER', $this->item->id); ?></legend>
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('name'); ?>
                    <?php echo $this->form->getInput('name'); ?></li>

                <li><?php echo $this->form->getLabel('lname'); ?>
                    <?php echo $this->form->getInput('lname'); ?></li>

                <li><?php echo $this->form->getLabel('funitid'); ?>
                    <?php echo $this->form->getInput('funitid'); ?></li>

                <li><?php echo $this->form->getLabel('alias'); ?>
                    <?php echo $this->form->getInput('alias'); ?></li>

                <li><?php echo $this->form->getLabel('user_id'); ?>
                    <?php echo $this->form->getInput('user_id'); ?></li>

                <li><?php echo $this->form->getLabel('catid'); ?>
                    <?php echo $this->form->getInput('catid'); ?></li>

                <li><?php echo $this->form->getLabel('published'); ?>
                    <?php echo $this->form->getInput('published'); ?></li>

                <li><?php echo $this->form->getLabel('access'); ?>
                    <?php echo $this->form->getInput('access'); ?></li>

                <li><?php echo $this->form->getLabel('ordering'); ?>
                    <?php echo $this->form->getInput('ordering'); ?></li>

                <li><?php echo $this->form->getLabel('featured'); ?>
                    <?php echo $this->form->getInput('featured'); ?></li>

                <li><?php echo $this->form->getLabel('language'); ?>
                    <?php echo $this->form->getInput('language'); ?></li>

                <li><?php echo $this->form->getLabel('id'); ?>
                    <?php echo $this->form->getInput('id'); ?></li>
            </ul>
        </fieldset>
        <fieldset class="adminform">

            <div class="clr"></div>
            <?php echo $this->form->getLabel('misc'); ?>
            <div class="clr"></div>
            <?php echo $this->form->getInput('misc'); ?>
        </fieldset>
    </div>
    <div class="width-40 fltrt">
        <?php echo JHtml::_('sliders.start', 'member-slider_' . $this->item->id, array('useCookie' => 1)); ?>
        <?php echo JHtml::_('sliders.panel', JText::_('JGLOBAL_FIELDSET_PUBLISHING'), 'publishing-details'); ?>

        <fieldset class="adminform">
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
        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_MEMBER_DETAILS'), 'basic-options'); ?>


        <fieldset class="adminform">
            <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_DETAILS', $this->item->id); ?></p>

            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('image'); ?>
                    <?php echo $this->form->getInput('image'); ?></li>

                <li><?php echo $this->form->getLabel('con_position'); ?>
                    <?php echo $this->form->getInput('con_position'); ?></li>

                <li><?php echo $this->form->getLabel('email_to'); ?>
                    <?php echo $this->form->getInput('email_to'); ?></li>

                <li><?php echo $this->form->getLabel('address'); ?>
                    <?php echo $this->form->getInput('address'); ?></li>

                <li><?php echo $this->form->getLabel('suburb'); ?>
                    <?php echo $this->form->getInput('suburb'); ?></li>

                <li><?php echo $this->form->getLabel('state'); ?>
                    <?php echo $this->form->getInput('state'); ?></li>

                <li><?php echo $this->form->getLabel('postcode'); ?>
                    <?php echo $this->form->getInput('postcode'); ?></li>

                <li><?php echo $this->form->getLabel('country'); ?>
                    <?php echo $this->form->getInput('country'); ?></li>

                <li><?php echo $this->form->getLabel('telephone'); ?>
                    <?php echo $this->form->getInput('telephone'); ?></li>

                <li><?php echo $this->form->getLabel('mobile'); ?>
                    <?php echo $this->form->getInput('mobile'); ?></li>

                <li><?php echo $this->form->getLabel('fax'); ?>
                    <?php echo $this->form->getInput('fax'); ?></li>

                <li><?php echo $this->form->getLabel('webpage'); ?>
                    <?php echo $this->form->getInput('webpage'); ?></li>

                <li><?php echo $this->form->getLabel('spouse'); ?>
                    <?php echo $this->form->getInput('spouse'); ?></li>

                <li><?php echo $this->form->getLabel('children'); ?>
                    <?php echo $this->form->getInput('children'); ?></li>

                <li><?php echo $this->form->getLabel('sortname1'); ?>
                    <?php echo $this->form->getInput('sortname1'); ?></li>

                <li><?php echo $this->form->getLabel('sortname2'); ?>
                    <?php echo $this->form->getInput('sortname2'); ?></li>

                <li><?php echo $this->form->getLabel('sortname3'); ?>
                    <?php echo $this->form->getInput('sortname3'); ?></li>

                <li><?php echo $this->form->getLabel('birthdate'); ?>
                    <?php echo $this->form->getInput('birthdate'); ?></li>

                <li><?php echo $this->form->getLabel('anniversary'); ?>
                    <?php echo $this->form->getInput('anniversary'); ?></li>

                <?php if ($this->age != '0'): ?>
                    <li><label id="jform_age-lbl" for="jform_age" class="hasTip" title="<?php echo JText::_('COM_CHURCHDIRECTORY_AGE_HASTIP'); ?> "><?php echo JText::_('COM_CHURCHDIRECTORY_AGE_LABEL'); ?></label>
                        <input type="text" name="jform[age]" id="jform_age" value="<?php echo $this->age . ' ' . JText::_('COM_CHURCHDIRECTORY_YEARS_OLD'); ?>" class="readonly" size="10" readonly="readonly"/></li>
                <?php endif; ?>
            </ul>
        </fieldset>

        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_MEMBER_IM'), 'im-options'); ?>

        <fieldset class="adminform">
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('skype'); ?>
                    <?php echo $this->form->getInput('skype'); ?></li>

                <li><?php echo $this->form->getLabel('yahoo_msg'); ?>
                    <?php echo $this->form->getInput('yahoo_msg'); ?></li>
            </ul>
        </fieldset>

        <?php echo $this->loadTemplate('attribs'); ?>

        <?php echo $this->loadTemplate('params'); ?>

        <?php echo $this->loadTemplate('metadata'); ?>
        <?php echo JHtml::_('sliders.end'); ?>
    </div>
    <div class="clr"></div>
    <div>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="return" value="<?php echo JRequest::getCmd('return'); ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
