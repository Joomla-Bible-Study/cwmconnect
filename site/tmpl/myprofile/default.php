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
use Joomla\CMS\Uri\Uri;

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

			<?php // My live map feeds?>
			<div class="card mb-3">
				<div class="card-header"><h3 class="card-title h6 mb-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEEDS_HEADING'); ?></h3></div>
				<div class="card-body">
					<p class="text-muted small"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEEDS_INTRO'); ?></p>

					<?php if ($this->newFeedCleartext !== '') :
					    $feedUrl = Uri::root() . 'index.php?option=com_cwmconnect&view=members&format=kml&token=' . urlencode($this->newFeedCleartext);
					    $dlUrl   = $feedUrl . '&networklink=1';
					    ?>
						<div class="alert alert-success">
							<h4 class="alert-heading h6"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_READY_HEADING'); ?></h4>
							<p class="mb-2">
								<a href="<?php echo $this->escape($dlUrl); ?>" class="btn btn-success btn-sm" download="church-directory-live.kml">
									<span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_DOWNLOAD_BTN'); ?>
								</a>
							</p>
							<p class="small text-muted mb-2"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_DOWNLOAD_HINT'); ?></p>
							<label class="small mb-1" for="cwm-feed-url"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_URL_LABEL'); ?></label>
							<input type="text" id="cwm-feed-url" class="form-control form-control-sm font-monospace" value="<?php echo $this->escape($feedUrl); ?>" readonly onclick="this.select();">
							<p class="small text-muted mb-0 mt-1"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_URL_WARNING'); ?></p>
						</div>
					<?php endif; ?>

					<?php if ($this->feeds !== []) : ?>
						<div class="table-responsive">
							<table class="table table-sm align-middle">
								<thead>
									<tr>
										<th scope="col"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_COL_NAME'); ?></th>
										<th scope="col" class="d-none d-sm-table-cell"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_COL_CREATED'); ?></th>
										<th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_COL_LASTUSED'); ?></th>
										<th scope="col"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_COL_STATUS'); ?></th>
										<th scope="col"><span class="visually-hidden"><?php echo Text::_('JACTION_EDIT'); ?></span></th>
									</tr>
								</thead>
								<tbody>
								<?php foreach ($this->feeds as $feed) :
								    $isActive = ($feed->status ?? '') === 'active';
								    ?>
									<tr>
										<td><?php echo $this->escape((string) $feed->label); ?></td>
										<td class="small d-none d-sm-table-cell"><?php echo HTMLHelper::_('date', $feed->created_at, Text::_('DATE_FORMAT_LC4')); ?></td>
										<td class="small d-none d-md-table-cell">
											<?php echo $feed->last_used_at
								                ? HTMLHelper::_('date', $feed->last_used_at, Text::_('DATE_FORMAT_LC4'))
								                : Text::_('COM_CWMCONNECT_MYPROFILE_FEED_NEVER_USED'); ?>
										</td>
										<td>
											<span class="badge bg-<?php echo $isActive ? 'success' : 'secondary'; ?>">
												<?php echo $isActive
								                    ? Text::_('COM_CWMCONNECT_MYPROFILE_FEED_STATUS_ACTIVE')
								                    : Text::_('COM_CWMCONNECT_MYPROFILE_FEED_STATUS_EXPIRED'); ?>
											</span>
										</td>
										<td class="text-end text-nowrap">
											<form action="<?php echo Route::_('index.php?option=com_cwmconnect&task=myprofile.regenerateKmlFeed'); ?>" method="post" class="d-inline">
												<input type="hidden" name="feed_id" value="<?php echo (int) $feed->id; ?>">
												<button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_REGENERATE'); ?></button>
												<?php echo HTMLHelper::_('form.token'); ?>
											</form>
											<form action="<?php echo Route::_('index.php?option=com_cwmconnect&task=myprofile.revokeKmlFeed'); ?>" method="post" class="d-inline">
												<input type="hidden" name="feed_id" value="<?php echo (int) $feed->id; ?>">
												<button type="submit" class="btn btn-outline-danger btn-sm"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_REVOKE'); ?></button>
												<?php echo HTMLHelper::_('form.token'); ?>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<form action="<?php echo Route::_('index.php?option=com_cwmconnect&task=myprofile.revokeKml'); ?>" method="post" class="mb-3">
							<button type="submit" class="btn btn-link btn-sm text-danger p-0"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_REVOKE_ALL'); ?></button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
					<?php else : ?>
						<p class="text-muted small"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEEDS_EMPTY'); ?></p>
					<?php endif; ?>

					<?php if ($this->atFeedCap) : ?>
						<p class="small text-muted mb-0"><?php echo Text::sprintf('COM_CWMCONNECT_MYPROFILE_FEED_CAP_NOTE', $this->maxFeeds); ?></p>
					<?php else : ?>
						<hr>
						<h4 class="h6"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_CREATE_HEADING'); ?></h4>
						<form action="<?php echo Route::_('index.php?option=com_cwmconnect&task=myprofile.createKmlFeed'); ?>" method="post" class="row g-2 align-items-end">
							<div class="col-sm-6">
								<label class="form-label small" for="cwm-feed-label"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_NAME_LABEL'); ?></label>
								<input type="text" name="feed_label" id="cwm-feed-label" class="form-control form-control-sm" maxlength="120"
								       placeholder="<?php echo $this->escape(Text::_('COM_CWMCONNECT_MYPROFILE_FEED_NAME_PLACEHOLDER')); ?>">
							</div>
							<div class="col-sm-4">
								<label class="form-label small" for="cwm-feed-expires"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_EXPIRES_LABEL'); ?></label>
								<input type="date" name="feed_expires" id="cwm-feed-expires" class="form-control form-control-sm">
							</div>
							<div class="col-sm-2">
								<button type="submit" class="btn btn-primary btn-sm w-100"><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_FEED_CREATE_BTN'); ?></button>
							</div>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
