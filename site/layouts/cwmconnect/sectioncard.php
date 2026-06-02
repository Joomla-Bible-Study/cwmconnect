<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * Bootstrap 5 section card: a titled card (optional icon) wrapping pre-rendered
 * body HTML. Used to lay directory sections out consistently.
 *
 * @var  array  $displayData
 *   - title  string  Card header title.
 *   - icon   string  Optional icon-* class (e.g. 'icon-envelope').
 *   - body   string  Pre-rendered HTML for the card body.
 *   - class  string  Extra classes on the .card wrapper.
 *   - id     string  Optional element id (anchor target).
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$title = (string) ($displayData['title'] ?? '');
$icon  = trim((string) ($displayData['icon'] ?? ''));
$body  = (string) ($displayData['body'] ?? '');
$class = trim((string) ($displayData['class'] ?? ''));
$id    = trim((string) ($displayData['id'] ?? ''));
?>
<div class="card shadow-sm h-100 <?php echo htmlspecialchars($class, ENT_QUOTES); ?>"<?php echo $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES) . '"' : ''; ?>>
    <?php if ($title !== '') : ?>
        <div class="card-header bg-transparent fw-semibold">
            <?php if ($icon !== '') : ?><span class="<?php echo htmlspecialchars($icon, ENT_QUOTES); ?> me-2 text-primary" aria-hidden="true"></span><?php endif; ?>
            <?php echo htmlspecialchars($title, ENT_QUOTES); ?>
        </div>
    <?php endif; ?>
    <div class="card-body">
        <?php echo $body; ?>
    </div>
</div>
