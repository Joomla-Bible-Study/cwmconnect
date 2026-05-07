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

/** @var \CWM\Component\Churchdirectory\Administrator\View\Position\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_churchdirectory&view=position&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="position-form" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'positionTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'positionTab', 'details', empty($this->item->id) ? Text::_('COM_CHURCHDIRECTORY_NEW_POSITION') : Text::sprintf('COM_CHURCHDIRECTORY_EDIT_POSITION', $this->item->id)); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('id'); ?>
            </div>
            <div class="col-lg-3">
                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_POSITIONS_DETAILS'); ?></legend>
                    <?php echo $this->form->renderField('published'); ?>
                    <?php echo $this->form->renderField('access'); ?>
                    <?php echo $this->form->renderField('language'); ?>
                </fieldset>

                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHURCHDIRECTORY_POSITIONS_DETAILS'); ?></legend>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo Text::_('COM_CHURCHDIRECTORY_FIELD_NAME_LABEL'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_CHURCHDIRECTORY_ID_LABEL'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($this->members)) : ?>
                            <?php foreach ($this->members as $member) : ?>
                                <tr>
                                    <th scope="row"><?php echo (int) $member['id']; ?></th>
                                    <td><?php echo $this->escape($member['name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="2" class="text-center">
                                    <?php echo Text::_('COM_CHURCHDIRECTORY_FIELD_NO_RECORDS'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
