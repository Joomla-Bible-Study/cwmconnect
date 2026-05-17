<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Administrator\View\Pcmapping\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=pcmapping&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="pcmapping-form" class="form-validate">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'pcmappingTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'pcmappingTab', 'details', Text::_('COM_CWMCONNECT_PCMAPPING_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-8">
                <?php echo $this->form->renderField('id'); ?>
                <?php echo $this->form->renderField('pc_field_id'); ?>
                <?php echo $this->form->renderField('pc_field_slug'); ?>
                <?php echo $this->form->renderField('pc_field_name'); ?>
                <?php echo $this->form->renderField('joomla_field_id'); ?>
            </div>
            <div class="col-lg-4">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_CWMCONNECT_PCMAPPING_HELP_BLURB'); ?>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
