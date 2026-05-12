<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RenderHelper;
use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('behavior.tooltip');
if (isset($this->error)) : ?>
<div class="cwmconnect-error">
	<?php echo $this->error; ?>
</div>
<?php endif; ?>

<div class="cwmconnect-form">
    <form id="cwmconnect-form" action="<?php echo Route::_('index.php'); ?>" method="post" class="form-validate">
        <fieldset>
            <legend><?php echo Text::_('COM_CWMCONNECT_FORM_LABEL'); ?></legend>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('cwmconnect_name'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('cwmconnect_name'); ?></div>
            </div>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('cwmconnect_email'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('cwmconnect_email'); ?></div>
            </div>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('cwmconnect_subject'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('cwmconnect_subject'); ?></div>
            </div>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('cwmconnect_message'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('cwmconnect_message'); ?></div>
            </div>
			<?php if ($this->params->get('show_email_copy'))
		{ ?>
            <div class="control-group">
                <div class="control-label"><?php echo $this->form->getLabel('cwmconnect_email_copy'); ?></div>
                <div class="controls"><?php echo $this->form->getInput('cwmconnect_email_copy'); ?></div>
            </div>
			<?php } ?>
			<?php // Dynamically load any additional fields from plugins. ?>
			<?php foreach ($this->form->getFieldsets() as $fieldset): ?>
			<?php if ($fieldset->name != 'member'): ?>
				<?php $fields = $this->form->getFieldset($fieldset->name); ?>
				<?php foreach ($fields as $field): ?>
                    <div class="control-group">
						<?php if ($field->hidden): ?>
                        <div class="controls">
							<?php echo $field->input; ?>
                        </div>
						<?php else: ?>
                        <div class="control-label">
							<?php echo $field->label; ?>
							<?php if (!$field->required && $field->type != "Spacer"): ?>
                            <span class="optional"><?php echo Text::_('COM_CWMCONNECT_OPTIONAL'); ?></span>
							<?php endif; ?>
                        </div>
                        <div class="controls"><?php echo $field->input; ?></div>
						<?php endif; ?>
                    </div>
					<?php endforeach; ?>
				<?php endif ?>
			<?php endforeach; ?>
            <div class="form-actions">
                <button class="btn btn-primary validate"
                        type="submit"><?php echo Text::_('COM_CWMCONNECT_MEMBER_SEND'); ?></button>
                <input type="hidden" name="option" value="com_cwmconnect"/>
                <input type="hidden" name="task" value="member.submit"/>
                <input type="hidden" name="return" value="<?php echo $this->return_page; ?>"/>
                <input type="hidden" name="id" value="<?php echo $this->member->slug; ?>"/>
				<?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </fieldset>
    </form>
</div>
