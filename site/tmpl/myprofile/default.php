<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Myprofile\HtmlView $this */

$saveAction = Route::_('index.php?option=com_cwmconnect&task=myprofile.save');
?>
<div class="com-cwmconnect-myprofile">
	<h1><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_HEADING'); ?></h1>

	<?php if ($this->isPcLinked) : ?>
		<div class="alert alert-info" role="status">
			<?php echo Text::sprintf(
			    'COM_CWMCONNECT_MYPROFILE_PC_NOTICE',
			    '<a href="https://my.planningcenteronline.com" target="_blank" rel="noopener">'
			        . Text::_('COM_CWMCONNECT_MYPROFILE_MY_PCO_LINK')
			        . '</a>',
			); ?>
		</div>
	<?php endif; ?>

	<?php if ($this->form !== null) : ?>
		<form action="<?php echo $saveAction; ?>" method="post" class="form-validate">
			<?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
				<fieldset>
					<?php if (!empty($fieldset->label)) : ?>
						<legend><?php echo Text::_($fieldset->label); ?></legend>
					<?php endif; ?>

					<?php foreach ($this->form->getFieldset($fieldset->name) as $field) : ?>
						<div class="control-group">
							<div class="control-label"><?php echo $field->label; ?></div>
							<div class="controls"><?php echo $field->input; ?></div>
						</div>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>

			<div class="form-actions">
				<button type="submit" class="btn btn-primary">
					<?php echo Text::_('COM_CWMCONNECT_MYPROFILE_SAVE'); ?>
				</button>
			</div>

			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php else : ?>
		<div class="alert alert-warning" role="alert">
			<?php echo Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_FORM_UNAVAILABLE'); ?>
		</div>
	<?php endif; ?>
</div>
