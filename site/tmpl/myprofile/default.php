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

	<div class="card mb-4">
		<div class="card-header">
			<h3 class="card-title mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_HEADING'); ?></h3>
		</div>
		<div class="card-body">
			<?php if ($this->hasActiveToken) : ?>
				<p class="text-success">
					<span class="icon-checkmark" aria-hidden="true"></span>
					<?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_ACTIVE'); ?>
				</p>
				<div class="d-flex gap-2">
					<a href="<?php echo Route::_('index.php?option=com_cwmconnect&task=members.kmlFeed'); ?>" class="btn btn-outline-primary btn-sm">
						<span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_DOWNLOAD'); ?>
					</a>
					<form action="<?php echo Route::_('index.php?option=com_cwmconnect&task=myprofile.revokeKml'); ?>" method="post" class="d-inline">
						<button type="submit" class="btn btn-outline-danger btn-sm">
							<span class="icon-ban-circle" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_REVOKE'); ?>
						</button>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
			<?php else : ?>
				<p><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_NONE'); ?></p>
				<a href="<?php echo Route::_('index.php?option=com_cwmconnect&task=members.kmlFeed'); ?>" class="btn btn-primary btn-sm">
					<span class="icon-location" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_CONNECT'); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

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
