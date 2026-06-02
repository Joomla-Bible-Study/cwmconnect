<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Member\HtmlView $this */

// J5/J6: behavior.formvalidation + behavior.tooltip were removed. Load the
// keepalive + form-validate web assets and Bootstrap tooltips instead.
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$fields = ['cwmconnect_name', 'cwmconnect_email', 'cwmconnect_subject', 'cwmconnect_message'];

?>
<?php if (isset($this->error)) : ?>
    <div class="alert alert-danger"><?php echo $this->error; ?></div>
<?php endif; ?>

<form id="cwmconnect-form" action="<?php echo Route::_('index.php'); ?>" method="post" class="form-validate cwmconnect-form">
    <?php foreach ($fields as $field) : ?>
        <div class="mb-3">
            <?php echo $this->form->getLabel($field); ?>
            <?php echo $this->form->getInput($field); ?>
        </div>
    <?php endforeach; ?>

    <?php // Dynamically load any additional fields from plugins.?>
    <?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
        <?php if ($fieldset->name !== 'member') : ?>
            <?php foreach ($this->form->getFieldset($fieldset->name) as $field) : ?>
                <div class="mb-3">
                    <?php if ($field->hidden) : ?>
                        <?php echo $field->input; ?>
                    <?php else : ?>
                        <?php echo $field->label; ?>
                        <?php if (!$field->required && $field->type !== 'Spacer') : ?>
                            <span class="text-body-tertiary small">(<?php echo Text::_('COM_CWMCONNECT_OPTIONAL'); ?>)</span>
                        <?php endif; ?>
                        <?php echo $field->input; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($this->params->get('show_email_copy')) : ?>
        <div class="form-check mb-3">
            <?php echo $this->form->getInput('cwmconnect_email_copy'); ?>
            <?php echo $this->form->getLabel('cwmconnect_email_copy'); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex">
        <button class="btn btn-primary validate" type="submit">
            <span class="icon-envelope" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MEMBER_SEND'); ?>
        </button>
    </div>

    <input type="hidden" name="option" value="com_cwmconnect">
    <input type="hidden" name="task" value="member.submit">
    <input type="hidden" name="return" value="<?php echo $this->return_page; ?>">
    <input type="hidden" name="id" value="<?php echo $this->member->slug; ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
