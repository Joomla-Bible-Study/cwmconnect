<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * Consistent page header: an optional back link, the page title, and an
 * optional subtitle (pre-rendered HTML, e.g. breadcrumb chips or a count).
 *
 * @var  array  $displayData
 *   - title     string  Page title.
 *   - backUrl   string  Optional "back" link URL.
 *   - backText  string  Back link label.
 *   - subtitle  string  Optional pre-rendered HTML under the title.
 *   - badge     string  Optional pre-rendered badge HTML next to the title.
 */

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$title    = (string) ($displayData['title'] ?? '');
$backUrl  = trim((string) ($displayData['backUrl'] ?? ''));
$backText = (string) ($displayData['backText'] ?? Text::_('COM_CWMCONNECT_HOME_BROWSE_DIRECTORY'));
$subtitle = (string) ($displayData['subtitle'] ?? '');
$badge    = (string) ($displayData['badge'] ?? '');
?>
<div class="cwm-page-header mb-3">
    <?php if ($backUrl !== '') : ?>
        <a class="small text-decoration-none d-inline-block mb-2" href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES); ?>">
            <span class="icon-arrow-left" aria-hidden="true"></span> <?php echo htmlspecialchars($backText, ENT_QUOTES); ?>
        </a>
    <?php endif; ?>
    <h1 class="h2 mb-1">
        <?php echo htmlspecialchars($title, ENT_QUOTES); ?>
        <?php echo $badge; ?>
    </h1>
    <?php if ($subtitle !== '') : ?>
        <div class="text-body-secondary"><?php echo $subtitle; ?></div>
    <?php endif; ?>
</div>
