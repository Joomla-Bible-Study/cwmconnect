<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Churchdirectory\Administrator\View\Kml\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_churchdirectory&view=kml&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="kml-form" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'kmlTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'kmlTab', 'details', empty($this->item->id) ? Text::_('COM_CHURCHDIRECTORY_NEW_KML') : Text::sprintf('COM_CHURCHDIRECTORY_EDIT_KML', $this->item->id)); ?>
        <div class="row">
            <div class="col-lg-8">
                <?php echo $this->form->renderField('id'); ?>
                <?php echo $this->form->renderField('alias'); ?>
                <?php echo $this->form->renderField('open', 'params'); ?>
                <?php echo $this->form->renderField('access'); ?>
                <?php echo $this->form->renderField('language'); ?>
                <?php echo $this->form->renderField('description'); ?>
            </div>
            <div class="col-lg-4">
                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_KML_RECORD_OPTIONS'); ?></legend>
                    <?php echo $this->form->renderField('published'); ?>
                    <?php echo $this->form->renderField('mcropen', 'params'); ?>
                </fieldset>

                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_KML_SUBURB_OPTIONS'); ?></legend>
                    <?php echo $this->form->renderField('msropen', 'params'); ?>
                </fieldset>

                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_KML_ICONSTYLE_OPTIONS'); ?></legend>
                    <?php echo $this->form->renderField('icscale', 'params'); ?>
                </fieldset>

                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_KML_LABELSTYLE_OPTOIONS'); ?></legend>
                    <?php echo $this->form->renderField('lscolor', 'params'); ?>
                    <?php echo $this->form->renderField('lscolormode', 'params'); ?>
                    <?php echo $this->form->renderField('lsscale', 'params'); ?>
                </fieldset>

                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_KML_LOOKAT_OPTIONS'); ?></legend>
                    <?php echo $this->form->renderField('lng'); ?>
                    <?php echo $this->form->renderField('lat'); ?>
                    <?php echo $this->form->renderField('altitude', 'params'); ?>
                    <?php echo $this->form->renderField('rmaxlines', 'params'); ?>
                    <?php echo $this->form->renderField('range', 'params'); ?>
                    <?php echo $this->form->renderField('tilt', 'params'); ?>
                    <?php echo $this->form->renderField('heading', 'params'); ?>
                    <?php echo $this->form->renderField('gxaltitudeMode', 'params'); ?>
                </fieldset>

                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_KML_STYLE_OPTIONS'); ?></legend>
                    <?php echo $this->form->renderField('style'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
