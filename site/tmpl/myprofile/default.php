<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use CWM\Component\Cwmconnect\Site\Helper\SocialLinks;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Myprofile\HtmlView $this */

$socialLinks = SocialLinks::fromJson($this->item->pc_social ?? null);

$saveAction = Route::_('index.php?option=com_cwmconnect&task=myprofile.save');

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('form.validate');
?>
<div class="com-cwmconnect-myprofile">
	<h1><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_HEADING'); ?></h1>

	<?php if ($this->isPcLinked) : ?>
		<div class="alert alert-info d-flex align-items-center" role="status">
			<span class="icon-link me-2" aria-hidden="true" style="font-size:1.2em;"></span>
			<div>
				<?php echo Text::sprintf(
				    'COM_CWMCONNECT_MYPROFILE_PC_NOTICE',
				    '<a href="https://my.planningcenteronline.com" target="_blank" rel="noopener">'
				        . Text::_('COM_CWMCONNECT_MYPROFILE_MY_PCO_LINK')
				        . '</a>',
				); ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="row g-4">
		<?php // ── Left column: profile form ──?>
		<div class="col-lg-8">
			<?php if ($this->form !== null) : ?>
				<form action="<?php echo $saveAction; ?>" method="post" class="form-validate">

					<?php // Identity?>
					<div class="card mb-3">
						<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FIELDSET_IDENTITY'); ?></h3></div>
						<div class="card-body">
							<div class="row">
								<?php foreach ($this->form->getFieldset('identity') as $field) : ?>
									<?php if ($field->hidden) {
									    echo $field->input;
									    continue;
									} ?>
									<div class="col-md-6 mb-3">
										<?php echo $field->renderField(); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<?php // Contact?>
					<div class="card mb-3">
						<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FIELDSET_CONTACT'); ?></h3></div>
						<div class="card-body">
							<div class="row">
								<?php foreach ($this->form->getFieldset('contact') as $field) : ?>
									<div class="col-md-6 mb-3">
										<?php echo $field->renderField(); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<?php // Social profiles (read-only — synced from Planning Center)?>
					<?php if ($socialLinks !== []) : ?>
						<div class="card mb-3">
							<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FIELDSET_SOCIAL'); ?></h3></div>
							<div class="card-body">
								<p class="text-muted small mb-2"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_SOCIAL_NOTE'); ?></p>
								<ul class="list-unstyled d-flex flex-wrap gap-2 mb-0">
									<?php foreach ($socialLinks as $link) : ?>
										<li>
											<a class="btn btn-outline-secondary btn-sm cwm-social cwm-social-<?php echo $this->escape($link['key']); ?>"
											   href="<?php echo $this->escape($link['url']); ?>"
											   target="_blank" rel="noopener noreferrer nofollow">
												<?php echo $this->escape($link['label']); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					<?php endif; ?>

					<?php // Address?>
					<div class="card mb-3">
						<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FIELDSET_ADDRESS'); ?></h3></div>
						<div class="card-body">
							<div class="row">
								<?php foreach ($this->form->getFieldset('address') as $field) : ?>
									<div class="col-md-6 mb-3">
										<?php echo $field->renderField(); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<?php // Dates?>
					<div class="card mb-3">
						<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FIELDSET_DATES'); ?></h3></div>
						<div class="card-body">
							<div class="row">
								<?php foreach ($this->form->getFieldset('dates') as $field) : ?>
									<div class="col-md-6 mb-3">
										<?php echo $field->renderField(); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<div class="mb-4">
						<button type="submit" class="btn btn-primary btn-lg">
							<span class="icon-save" aria-hidden="true"></span>
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

		<?php // ── Right column: settings sidebar ──?>
		<div class="col-lg-4">
			<?php // Directory visibility?>
			<?php if ($this->form !== null) : ?>
				<div class="card mb-3">
					<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FIELDSET_DIRECTORY'); ?></h3></div>
					<div class="card-body">
						<?php foreach ($this->form->getFieldset('directory') as $field) : ?>
							<div class="mb-3">
								<?php echo $field->renderField(); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php // KML Feed management?>
			<div class="card mb-3">
				<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_HEADING'); ?></h3></div>
				<div class="card-body">
					<?php if ($this->hasActiveToken) : ?>
						<p class="text-success mb-2">
							<span class="icon-checkmark" aria-hidden="true"></span>
							<?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_ACTIVE'); ?>
						</p>
						<div class="d-grid gap-2">
							<a href="<?php echo Route::_('index.php?option=com_cwmconnect&task=members.kmlFeed'); ?>" class="btn btn-outline-primary btn-sm">
								<span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_DOWNLOAD'); ?>
							</a>
							<form action="<?php echo Route::_('index.php?option=com_cwmconnect&task=myprofile.revokeKml'); ?>" method="post">
								<button type="submit" class="btn btn-outline-danger btn-sm w-100">
									<span class="icon-ban-circle" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_REVOKE'); ?>
								</button>
								<?php echo HTMLHelper::_('form.token'); ?>
							</form>
						</div>
					<?php else : ?>
						<p class="text-muted small mb-2"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_NONE'); ?></p>
						<a href="<?php echo Route::_('index.php?option=com_cwmconnect&task=members.kmlFeed'); ?>" class="btn btn-primary btn-sm w-100">
							<span class="icon-location" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MYPROFILE_KML_CONNECT'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
