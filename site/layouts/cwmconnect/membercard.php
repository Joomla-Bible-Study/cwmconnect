<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * Directory member card: photo + name + optional position + household chip,
 * the whole card linking to the member's profile. Used by every member grid.
 *
 * @var  array  $displayData
 *   - id          int     Member id.
 *   - name        string  Display name.
 *   - hasPhoto    bool    Whether the member has a photo.
 *   - profileUrl  string  Link to the profile.
 *   - position    string  Pre-rendered position name(s), or ''.
 *   - household   string  Household name chip, or ''.
 */

use CWM\Component\Cwmconnect\Site\Helper\Layout;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$id         = (int) ($displayData['id'] ?? 0);
$name       = (string) ($displayData['name'] ?? '');
$hasPhoto   = (bool) ($displayData['hasPhoto'] ?? false);
$profileUrl = (string) ($displayData['profileUrl'] ?? '');
$position   = trim((string) ($displayData['position'] ?? ''));
$household  = trim((string) ($displayData['household'] ?? ''));
?>
<a class="card h-100 text-decoration-none text-body shadow-sm cwm-member-card" href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES); ?>">
    <?php
    echo Layout::render('photo', [
        'id'       => $id,
        'hasPhoto' => $hasPhoto,
        'alt'      => $name,
        'class'    => 'card-img-top',
        'sizes'    => '(max-width: 600px) 50vw, 300px',
    ]);
?>
    <div class="card-body p-2 text-center">
        <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($name, ENT_QUOTES); ?></div>
        <?php if ($position !== '') : ?>
            <div class="small text-body-secondary text-truncate"><?php echo htmlspecialchars($position, ENT_QUOTES); ?></div>
        <?php endif; ?>
        <?php if ($household !== '') : ?>
            <div class="mt-1"><span class="badge rounded-pill bg-body-secondary text-body-secondary"><?php echo htmlspecialchars($household, ENT_QUOTES); ?></span></div>
        <?php endif; ?>
    </div>
</a>
