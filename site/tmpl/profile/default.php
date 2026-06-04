<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use CWM\Component\Cwmconnect\Site\Helper\HouseholdVisibility;
use CWM\Component\Cwmconnect\Site\Helper\Layout;
use CWM\Component\Cwmconnect\Site\Helper\SocialLinks;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Profile\HtmlView $this */

$item        = $this->item;
$role        = trim((string) ($item->pc_office_role ?? '')) ?: trim((string) ($item->pc_positions ?? ''));
$social      = SocialLinks::fromJson($item->pc_social ?? null);
$showAddress = trim((string) ($item->address ?? '') . ($item->suburb ?? '') . ($item->state ?? '') . ($item->postcode ?? '')) !== '';
$listUrl     = Route::_('index.php?option=com_cwmconnect&view=members');
$profileLink = static fn(int $id): string => Route::_('index.php?option=com_cwmconnect&view=profile&id=' . $id);
?>
<div class="com-cwmconnect-profile container">
	<p class="mb-3">
		<a href="<?php echo $listUrl; ?>" class="link-secondary text-decoration-none">
			<span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_PROFILE_BACK_TO_DIRECTORY'); ?>
		</a>
	</p>

	<div class="row g-4">
		<div class="col-md-4 col-lg-3">
			<?php echo Layout::render('photo', [
			    'id'       => (int) $item->id,
			    'hasPhoto' => (string) ($item->image ?? '') !== '',
			    'alt'      => (string) $item->name,
			    'class'    => 'img-fluid rounded shadow-sm w-100',
			    'sizes'    => '(max-width: 768px) 100vw, 300px',
			]); ?>
		</div>

		<div class="col-md-8 col-lg-9">
			<h1 class="h2 mb-1"><?php echo $this->escape((string) $item->name); ?></h1>
			<?php if ($role !== '') : ?>
				<p class="text-body-secondary fs-5 mb-2"><?php echo $this->escape($role); ?></p>
			<?php endif; ?>
			<?php if (trim((string) ($item->household_name ?? '')) !== '') : ?>
				<p class="mb-3"><span class="badge rounded-pill bg-body-secondary text-body-secondary"><?php echo $this->escape((string) $item->household_name); ?></span></p>
			<?php endif; ?>

			<?php // Contact?>
			<dl class="row mb-0">
				<?php if (!empty($item->email_to)) : ?>
					<dt class="col-sm-3"><?php echo Text::_('JGLOBAL_EMAIL'); ?></dt>
					<dd class="col-sm-9"><a href="mailto:<?php echo $this->escape((string) $item->email_to); ?>"><?php echo $this->escape((string) $item->email_to); ?></a></dd>
				<?php endif; ?>
				<?php if (!empty($item->telephone)) : ?>
					<dt class="col-sm-3"><?php echo Text::_('COM_CWMCONNECT_TELEPHONE'); ?></dt>
					<dd class="col-sm-9"><a href="tel:<?php echo $this->escape((string) $item->telephone); ?>"><?php echo $this->escape((string) $item->telephone); ?></a></dd>
				<?php endif; ?>
				<?php if (!empty($item->mobile)) : ?>
					<dt class="col-sm-3"><?php echo Text::_('COM_CWMCONNECT_MOBILE'); ?></dt>
					<dd class="col-sm-9"><a href="tel:<?php echo $this->escape((string) $item->mobile); ?>"><?php echo $this->escape((string) $item->mobile); ?></a></dd>
				<?php endif; ?>
				<?php if ($showAddress) : ?>
					<dt class="col-sm-3"><?php echo Text::_('COM_CWMCONNECT_ADDRESS'); ?></dt>
					<dd class="col-sm-9">
						<?php echo nl2br($this->escape(trim(implode("\n", array_filter([
						    trim((string) ($item->address ?? '')),
						    trim(implode(' ', array_filter([(string) ($item->suburb ?? ''), (string) ($item->state ?? ''), (string) ($item->postcode ?? '')]))),
						    trim((string) ($item->country ?? '')),
						]))))); ?>
					</dd>
				<?php endif; ?>
			</dl>

			<?php if ($social !== []) : ?>
				<div class="mt-3">
					<h2 class="h6 text-body-secondary"><?php echo Text::_('COM_CWMCONNECT_PROFILE_SOCIAL'); ?></h2>
					<ul class="list-unstyled d-flex flex-wrap gap-2 mb-0">
						<?php foreach ($social as $link) : ?>
							<li>
								<a class="btn btn-outline-secondary btn-sm cwm-social cwm-social-<?php echo $this->escape($link['key']); ?>"
								   href="<?php echo $this->escape($link['url']); ?>" target="_blank" rel="noopener noreferrer nofollow">
									<?php echo $this->escape($link['label']); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($this->householdMembers !== [] || $this->hiddenHouseholdCount > 0) : ?>
		<hr class="my-4">
		<h2 class="h4 mb-3"><?php echo Text::_('COM_CWMCONNECT_PROFILE_HOUSEHOLD'); ?></h2>
		<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
			<?php foreach ($this->householdMembers as $member) : ?>
				<div class="col">
					<?php echo Layout::render('membercard', [
					    'id'         => (int) $member->id,
					    'name'       => (string) $member->name,
					    'hasPhoto'   => (string) ($member->image ?? '') !== '',
					    'profileUrl' => $profileLink((int) $member->id),
					    'position'   => '',
					    'household'  => '',
					]); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ($this->hiddenHouseholdCount > 0) : ?>
			<p class="text-body-secondary mt-2 mb-0">
				<?php echo Text::plural('COM_CWMCONNECT_PROFILE_HOUSEHOLD_OTHERS_N', $this->hiddenHouseholdCount); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
