<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * Consistent "nothing here" block with an optional call-to-action button.
 *
 * @var  array  $displayData
 *   - message  string  The empty-state message.
 *   - icon     string  Optional icon-* class (default icon-info-circle).
 *   - ctaUrl   string  Optional CTA button URL.
 *   - ctaText  string  CTA button label.
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$message = (string) ($displayData['message'] ?? '');
$icon    = trim((string) ($displayData['icon'] ?? 'icon-info-circle'));
$ctaUrl  = trim((string) ($displayData['ctaUrl'] ?? ''));
$ctaText = (string) ($displayData['ctaText'] ?? '');
?>
<div class="cwm-empty-state text-center text-body-secondary py-5">
    <?php if ($icon !== '') : ?>
        <div class="display-6 mb-2"><span class="<?php echo htmlspecialchars($icon, ENT_QUOTES); ?>" aria-hidden="true"></span></div>
    <?php endif; ?>
    <p class="mb-3"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></p>
    <?php if ($ctaUrl !== '' && $ctaText !== '') : ?>
        <a class="btn btn-primary" href="<?php echo htmlspecialchars($ctaUrl, ENT_QUOTES); ?>">
            <?php echo htmlspecialchars($ctaText, ENT_QUOTES); ?>
        </a>
    <?php endif; ?>
</div>
