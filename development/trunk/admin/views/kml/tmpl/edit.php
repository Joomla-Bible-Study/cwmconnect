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

<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=kml&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="kml-form" class="form-validate">
    <div class="width-60 fltlft">
        <fieldset class="adminform">
            <legend><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_CONTACT') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_CONTACT', $this->item->id); ?></legend>
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('name'); ?>
                    <?php echo $this->form->getInput('name'); ?></li>
                <li><?php echo $this->form->getLabel('alias'); ?>
                    <?php echo $this->form->getInput('alias'); ?></li>
                <li><?php echo $this->form->getLabel('open', 'params'); ?>
                    <?php echo $this->form->getInput('open', 'params'); ?></li>
                <li><?php echo $this->form->getLabel('access'); ?>
                    <?php echo $this->form->getInput('access'); ?></li>
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
        <?php echo JHtml::_('sliders.start', 'kml-slider'); ?>
        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_RECORD_OPTIONS'), 'kmloptions-details'); ?>
        <fieldset class="panelform">
            <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_RECORD_OPTIONS_DETAILS', $this->item->id); ?></p>
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('mcropen', 'params'); ?>
                    <?php echo $this->form->getInput('mcropen', 'params'); ?></li>
            </ul>
        </fieldset>
        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_SUBURB_OPTIONS'), 'suburb-options'); ?>
        <fieldset class="panelform">
            <ul class="adminformlist">
                <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_SUBURB_OPTIONS_DETAILS', $this->item->id); ?></p>
                <li><?php echo $this->form->getLabel('msropen', 'params'); ?>
                    <?php echo $this->form->getInput('msropen', 'params'); ?></li>
            </ul>
        </fieldset>
        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_ICONSTYLE_OPTIONS'), 'iconstyle-options'); ?>
        <fieldset class="panelform">
            <ul class="adminformlist">
                <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_ICONSTYLE_OPTIONS_DETAILS', $this->item->id); ?></p>
                <li><?php echo $this->form->getLabel('icscale', 'params'); ?>
                    <?php echo $this->form->getInput('icscale', 'params'); ?></li>
            </ul>
        </fieldset>
        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_LABELSTYLE_OPTOIONS'), 'labelstyle-options'); ?>
        <fieldset class="panelform">
            <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_LABELSTYLE_OPTOIONS_DETAILS', $this->item->id); ?></p>
            <ul>
                <li><?php echo $this->form->getLabel('lscolor', 'params'); ?>
                    <?php echo $this->form->getInput('lscolor', 'params'); ?></li>
                <li><?php echo $this->form->getLabel('lscolormode', 'params'); ?>
                    <?php echo $this->form->getInput('lscolormode', 'params'); ?></li>
                <li><?php echo $this->form->getLabel('lsscale', 'params'); ?>
                    <?php echo $this->form->getInput('lsscale', 'params'); ?></li>

            </ul>
        </fieldset>
        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_LOOKAT_OPTIONS'), 'lookat-options'); ?>
        <fieldset class="panelform">
            <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_LOOKAT_OPTIONS_DETAILS', $this->item->id); ?></p>
            <ul>
                <li><?php echo $this->form->getLabel('lng'); ?>
                    <?php echo $this->form->getInput('lng'); ?></li>
                <li><?php echo $this->form->getLabel('lat'); ?>
                    <?php echo $this->form->getInput('lat'); ?></li>
                <li><?php echo $this->form->getLabel('altitude', 'params'); ?>
                    <?php echo $this->form->getInput('altitude', 'params'); ?></li>

                <li><?php echo $this->form->getLabel('rmaxlines', 'params'); ?>
                    <?php echo $this->form->getInput('rmaxlines', 'params'); ?></li>
                <li><?php echo $this->form->getLabel('range', 'params'); ?>
                    <?php echo $this->form->getInput('range', 'params'); ?></li>
                <li><?php echo $this->form->getLabel('tilt', 'params'); ?>
                    <?php echo $this->form->getInput('tilt', 'params'); ?></li>
                <li><?php echo $this->form->getLabel('heading', 'params'); ?>
                    <?php echo $this->form->getInput('heading', 'params'); ?></li>
                <li><?php echo $this->form->getLabel('gxaltitudeMode', 'params'); ?>
                    <?php echo $this->form->getInput('gxaltitudeMode', 'params'); ?></li>
            </ul>
        </fieldset>
        <?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_STYLE_OPTIONS'), 'style-options'); ?>
        <fieldset class="panelform">
            <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_STYLE_OPTIONS_DETAILS', $this->item->id); ?></p>
            <ul class="adminformlist">
                <li><?php echo $this->form->getLabel('style'); ?>
                    <?php echo $this->form->getInput('style'); ?></li>
            </ul>
        </fieldset>
        <?php echo JHtml::_('sliders.end'); ?>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="tooltype" value="" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
<div class="clr"></div>
