<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.framework');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

$app   = JFactory::getApplication();
$input = $app->input;

$assoc = isset($app->item_associations) ? $app->item_associations : 0;
?>
<script type="text/javascript">
    Joomla.submitbutton = function (task) {
        if (task == 'kml.cancel' || document.formvalidator.isValid(document.id('kml-form'))) {
            Joomla.submitform(task, document.getElementById('kml-form'));
        }
        else {
            alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
        }
    }
</script>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=kml&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="kml-form" class="form-validate form-horizontal">
    <div class="row-fluid">
        <div class="span8 form-horizontal">
            <fieldset>
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#details"
                           data-toggle="tab"><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_NEW_KML') : JText::sprintf('COM_CHURCHDIRECTORY_EDIT_KML', $this->item->id); ?></a>
                    </li>
					<?php
					$fieldSets = $this->form->getFieldsets('params');
					foreach ($fieldSets as $name => $fieldSet) :
						?>
                        <li>
                            <a href="#params-<?php echo $name;?>"
                               data-toggle="tab"><?php echo JText::_($fieldSet->label);?>
                            </a>
                        </li>
						<?php endforeach; ?>
					<?php
					$fieldSets = $this->form->getFieldsets('metadata');
					foreach ($fieldSets as $name => $fieldSet) :
						?>
                        <li><a href="#metadata-<?php echo $name;?>"
                               data-toggle="tab"><?php echo JText::_($fieldSet->label);?></a></li>
						<?php endforeach; ?>
					<?php if ($assoc): ?>
                    <li><a href="#associations"
                           data-toggle="tab"><?php echo JText::_('JGLOBAL_FIELDSET_ASSOCIATIONS');?></a></li>
					<?php endif; ?>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="details">
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('name'); ?></div>
                        </div>
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
                        </div>
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('open', 'params'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('open', 'params'); ?></div>
                        </div>
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('access'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('access'); ?></div>
                        </div>
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('language'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('language'); ?></div>
                        </div>
                        <div class="control-group">
                            <div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
                            <div class="controls"><?php echo $this->form->getInput('id'); ?></div>
                        </div>
                        <div class="control-group">
                            <?php echo $this->form->getLabel('description'); ?>
	                        <div class="clearfix"></div>
                            <?php echo $this->form->getInput('description'); ?>
                        </div>
                    </div>
            </fieldset>
        </div>
        <!-- End Newsfeed -->
        <!-- Begin Sidebar -->
        <div class="span4">
			<?php echo JHtml::_('sliders.start', 'kml-slider'); ?>
			<?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_RECORD_OPTIONS'), 'kmloptions-details'); ?>
            <fieldset class="form-vertical">
                <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_RECORD_OPTIONS_DETAILS', $this->item->id); ?></p>
                <ul class="adminformlist">
                    <li><?php echo $this->form->getLabel('mcropen', 'params'); ?>
						<?php echo $this->form->getInput('mcropen', 'params'); ?></li>
                </ul>
            </fieldset>
			<?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_SUBURB_OPTIONS'), 'suburb-options'); ?>
            <fieldset class="form-vertical">
                <ul class="adminformlist">
                    <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_SUBURB_OPTIONS_DETAILS', $this->item->id); ?></p>
                    <li><?php echo $this->form->getLabel('msropen', 'params'); ?>
						<?php echo $this->form->getInput('msropen', 'params'); ?></li>
                </ul>
            </fieldset>
			<?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_ICONSTYLE_OPTIONS'), 'iconstyle-options'); ?>
            <fieldset class="form-vertical">
                <ul class="adminformlist">
                    <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_KML_ICONSTYLE_OPTIONS_DETAILS', $this->item->id); ?></p>
                    <li><?php echo $this->form->getLabel('icscale', 'params'); ?>
						<?php echo $this->form->getInput('icscale', 'params'); ?></li>
                </ul>
            </fieldset>
			<?php echo JHtml::_('sliders.panel', JText::_('COM_CHURCHDIRECTORY_KML_LABELSTYLE_OPTOIONS'), 'labelstyle-options'); ?>
            <fieldset class="form-vertical">
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
            <fieldset class="form-vertical">
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
            <fieldset class="form-vertical">
                <p><?php echo empty($this->item->id) ? JText::_('COM_CHURCHDIRECTORY_DETAILS') : JText::sprintf('COM_CHURCHDIRECTORY_STYLE_OPTIONS_DETAILS', $this->item->id); ?></p>
                <ul class="adminformlist">
                    <li><?php echo $this->form->getLabel('style'); ?>
						<?php echo $this->form->getInput('style'); ?></li>
                </ul>
            </fieldset>
			<?php echo JHtml::_('sliders.end'); ?>
            <input type="hidden" name="task" value=""/>
			<?php echo JHtml::_('form.token'); ?>
        </div>
</form>
