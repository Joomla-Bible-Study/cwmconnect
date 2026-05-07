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

/** @var \CWM\Component\Churchdirectory\Administrator\View\Dirheader\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_churchdirectory&view=dirheader&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="dirheader-form" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'dirheaderTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'dirheaderTab', 'details', empty($this->item->id) ? Text::_('COM_CHURCHDIRECTORY_NEW_DIRHEADER') : Text::sprintf('COM_CHURCHDIRECTORY_EDIT_DIRHEADER', $this->item->id)); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('description'); ?>
            </div>
            <div class="col-lg-3">
                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_DIRHEADERE_DETAILS'); ?></legend>
                    <?php echo $this->form->renderField('id'); ?>
                    <?php echo $this->form->renderField('published'); ?>
                    <?php echo $this->form->renderField('section'); ?>
                    <?php echo $this->form->renderField('access'); ?>
                    <?php echo $this->form->renderField('featured'); ?>
                    <?php echo $this->form->renderField('language'); ?>
                    <?php echo $this->form->renderField('image'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'dirheaderTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <div class="row">
            <div class="col-lg-6">
                <?php echo $this->form->renderField('created_by'); ?>
                <?php echo $this->form->renderField('created_by_alias'); ?>
                <?php echo $this->form->renderField('created'); ?>
                <?php echo $this->form->renderField('publish_up'); ?>
                <?php echo $this->form->renderField('publish_down'); ?>
            </div>
            <?php if (!empty($this->item->modified_by)) : ?>
                <div class="col-lg-6">
                    <?php echo $this->form->renderField('modified_by'); ?>
                    <?php echo $this->form->renderField('modified'); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
