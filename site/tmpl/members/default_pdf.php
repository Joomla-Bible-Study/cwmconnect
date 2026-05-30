<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Cwmconnect\Site\Service\DirectoryPdfPresenter $this */

$currentLetter = null;

// Base body size drives everything else via em units, so the "large print"
// option scales the whole document.
$fontBase = $this->appearance['fontBasePt'] ?? 10.0;

/**
 * Emit an alphabetical (A, B, C…) surname divider when the leading letter
 * changes. Shares $currentLetter by reference across the listing passes.
 */
$emitDivider = function (object $item) use (&$currentLetter): void {
    $surname = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));
    $letter  = strtoupper(mb_substr($surname, 0, 1)) ?: '#';

    if ($letter !== $currentLetter) {
        $currentLetter = $letter;
        echo '<div class="letter">' . $this->escape($letter) . '</div>';
    }
};

/**
 * Photo-detail entry: photo left, details right. $isStaff adds the position
 * line. Used by the staff section and the photo_detail listing.
 */
$renderEntry = function (object $item, bool $isStaff): void {
    $photo    = $this->memberPhotoSrc($item);
    $position = trim((string) ($item->con_position ?? ''));
    $locality = $this->memberLocality($item);
    ?>
    <table class="entry">
        <tr>
            <td class="photo-cell">
                <?php if ($photo !== null) : ?>
                    <img class="photo" src="<?php echo $this->escape($photo); ?>" width="106" alt="" />
                <?php else : ?>
                    <div class="no-photo"><span><?php echo $this->escape($this->memberInitials($item)); ?></span></div>
                <?php endif; ?>
            </td>
            <td class="details">
                <div class="name"><?php echo $this->escape($this->memberName($item)); ?><?php if ($this->isHidden($item)) : ?> <span class="hidden-badge">hidden</span><?php endif; ?></div>

                <?php if ($isStaff && $position !== '') : ?>
                    <div class="line position"><?php echo $this->escape($position); ?></div>
                <?php endif; ?>

                <?php if (!empty($item->address)) : ?>
                    <div class="line"><?php echo $this->escape((string) $item->address); ?></div>
                <?php endif; ?>

                <?php if ($locality !== '') : ?>
                    <div class="line"><?php echo $this->escape($locality); ?></div>
                <?php endif; ?>

                <?php if (!$isStaff && ($anniversary = $this->memberAnniversary($item))) : ?>
                    <div class="line muted"><?php echo Text::sprintf('COM_CWMCONNECT_PDF_ANNIVERSARY', $this->escape($anniversary)); ?></div>
                <?php endif; ?>

                <?php foreach (array_filter([(string) ($item->telephone ?? ''), (string) ($item->mobile ?? '')]) as $phone) : ?>
                    <div class="line"><?php echo $this->escape($phone); ?></div>
                <?php endforeach; ?>

                <?php if (!empty($item->email_to)) : ?>
                    <div class="line email"><?php echo $this->escape((string) $item->email_to); ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <div class="rule"></div>
    <?php
};

/**
 * Photo grid: compact cards (photo + name + primary phone), three per row.
 * Contact detail is intentionally minimal — pair with an appended roster for
 * full contact info.
 */
$renderGrid = function (array $items): void {
    $columns = 3;
    $count   = \count($items);

    for ($i = 0; $i < $count; $i += $columns) :
        ?>
        <table class="grid">
            <tr>
                <?php for ($c = 0; $c < $columns; $c++) : ?>
                    <?php $item = $items[$i + $c] ?? null; ?>
                    <td class="grid-cell">
                        <?php if ($item !== null) : ?>
                            <?php $src = $this->memberPhotoSrc($item); ?>
                            <?php if ($src !== null) : ?>
                                <img class="grid-photo" src="<?php echo $this->escape($src); ?>" width="92" alt="" />
                            <?php else : ?>
                                <div class="grid-nophoto"><span><?php echo $this->escape($this->memberInitials($item)); ?></span></div>
                            <?php endif; ?>
                            <div class="grid-name"><?php echo $this->escape($this->memberName($item)); ?><?php if ($this->isHidden($item)) : ?> <span class="hidden-badge">hidden</span><?php endif; ?></div>
                            <?php $phone = trim((string) ($item->telephone ?? '')) ?: trim((string) ($item->mobile ?? '')); ?>
                            <?php if ($phone !== '') : ?>
                                <div class="grid-line"><?php echo $this->escape($phone); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
        </table>
        <?php
    endfor;
};

/**
 * Text roster: one line per member, no photos. Name in bold followed by a
 * dot-separated contact string. Optionally grouped by alphabetical dividers.
 */
$renderRoster = function (array $items, bool $dividers) use (&$currentLetter, $emitDivider): void {
    $currentLetter = null;

    foreach ($items as $item) :
        if ($dividers) {
            $emitDivider($item);
        }

        $bits = array_filter([
            trim((string) ($item->address ?? '')),
            $this->memberLocality($item),
            trim((string) ($item->telephone ?? '')),
            trim((string) ($item->mobile ?? '')),
            trim((string) ($item->email_to ?? '')),
        ]);
        ?>
        <div class="roster-row">
            <span class="roster-name"><?php echo $this->escape($this->memberName($item)); ?></span><?php if ($this->isHidden($item)) : ?> <span class="hidden-badge">hidden</span><?php endif; ?>
            <?php if ($bits !== []) : ?>
                <span class="roster-info"><?php echo $this->escape(' — ' . implode(' · ', $bits)); ?></span>
            <?php endif; ?>
        </div>
        <?php
    endforeach;
};
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: <?php echo $fontBase; ?>pt; color: #2b2b2b; }
    h1 { font-size: 1.8em; margin: 0 0 2mm; color: #1a1a1a; }
    .meta { font-size: 0.8em; color: #888; margin-bottom: 6mm; }

    /* Cover page. */
    .cover { text-align: center; padding-top: 30mm; }
    .cover img.cover-img { margin-bottom: 10mm; border: 0.5pt solid #ccc; }
    .cover .cover-name { font-size: 2.4em; font-weight: bold; color: #1a1a1a; margin-bottom: 5mm; }
    .cover .cover-line { font-size: 1.1em; color: #555; line-height: 1.6; }

    /* Section heading (e.g. "Our Staff", "Member Roster"). */
    .section-heading {
        font-size: 1.6em; font-weight: bold; color: #444;
        border-bottom: 1.5pt solid #888;
        margin: 0 0 4mm; padding-bottom: 1mm;
    }

    /* Alphabetical section divider. */
    .letter {
        font-size: 1.5em; font-weight: bold; color: #555;
        border-bottom: 1pt solid #999;
        margin: 4mm 0 2mm; padding-bottom: 1mm;
    }

    /* Photo-detail entry — a 2-cell table so the photo and details sit side by
       side and the whole block never splits across a page. */
    .entry { width: 100%; page-break-inside: avoid; margin-bottom: 4mm; }
    .entry td { vertical-align: top; padding: 0; }
    .photo-cell { width: 32mm; }
    /* mpdf only reliably honours sizing as an inline attribute, so photo width
       is set inline on the <img>; this just styles the border. */
    .entry img.photo { border: 0.5pt solid #ccc; }
    .no-photo {
        width: 28mm; height: 37mm;
        background: #eef0f2; border: 0.5pt solid #ccc;
        color: #9aa0a6; font-size: 1.8em; font-weight: bold;
        text-align: center;
    }
    .no-photo span { vertical-align: middle; }

    .details { padding-left: 4mm; }
    .details .name { font-size: 1.2em; font-weight: bold; color: #1a1a1a; }
    .details .line { font-size: 0.95em; line-height: 1.35; }
    .details .position { font-style: italic; color: #555; }
    .details .muted { color: #777; }
    .details .email { color: #1a5276; }

    .rule { border-bottom: 0.25pt solid #e2e2e2; margin-bottom: 4mm; }

    /* Photo grid (compact cards). */
    .grid { width: 100%; page-break-inside: avoid; }
    .grid td.grid-cell { width: 33%; text-align: center; vertical-align: top; padding: 0 2mm 5mm; }
    .grid img.grid-photo { border: 0.5pt solid #ccc; }
    .grid .grid-nophoto {
        width: 24mm; height: 32mm; margin: 0 auto;
        background: #eef0f2; border: 0.5pt solid #ccc;
        color: #9aa0a6; font-size: 1.4em; font-weight: bold; text-align: center;
    }
    .grid .grid-name { font-weight: bold; font-size: 0.95em; margin-top: 1.5mm; }
    .grid .grid-line { font-size: 0.85em; color: #555; }

    /* Text roster. */
    .roster-row { font-size: 0.95em; line-height: 1.4; margin-bottom: 1.5mm; padding-bottom: 1mm; border-bottom: 0.25pt solid #eee; }
    .roster-row .roster-name { font-weight: bold; color: #1a1a1a; }
    .roster-row .roster-info { color: #555; }

    .hidden-badge { background: #dc3545; color: #fff; font-size: 0.7em; padding: 0 1mm; border-radius: 1mm; }

    .pagebreak { page-break-after: always; }
</style>

<?php // ── Cover page ────────────────────────────────────────────────?>
<?php if (!empty($this->cover['enabled'])) : ?>
    <div class="cover">
        <?php if (!empty($this->cover['image'])) : ?>
            <img class="cover-img" src="<?php echo $this->escape((string) $this->cover['image']); ?>" width="560" alt="" />
        <?php endif; ?>
        <div class="cover-name"><?php echo $this->escape((string) $this->cover['name']); ?></div>
        <?php foreach (preg_split('/\r\n|\r|\n/', (string) $this->cover['address']) as $addressLine) : ?>
            <?php if (trim($addressLine) !== '') : ?>
                <div class="cover-line"><?php echo $this->escape(trim($addressLine)); ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php $contact = array_filter([(string) $this->cover['phone'], (string) $this->cover['email'], (string) $this->cover['website']]); ?>
        <?php if ($contact !== []) : ?>
            <div class="cover-line"><?php echo $this->escape(implode('  •  ', $contact)); ?></div>
        <?php endif; ?>
    </div>
    <div class="pagebreak"></div>
<?php endif; ?>

<?php // ── Staff section (always photo-detail style) ─────────────────────?>
<?php if ($this->staff !== []) : ?>
    <h2 class="section-heading"><?php echo Text::_('COM_CWMCONNECT_PDF_STAFF_HEADING'); ?></h2>
    <?php foreach ($this->staff as $member) : ?>
        <?php $renderEntry($member, true); ?>
    <?php endforeach; ?>
    <div class="pagebreak"></div>
<?php endif; ?>

<?php // ── Member listing ───────────────────────────────────────────────?>
<?php if ($this->showTitleBlock) : ?>
    <h1><?php echo Text::_('COM_CWMCONNECT_PDF_TITLE'); ?></h1>
    <div class="meta">
        <?php echo Text::sprintf('COM_CWMCONNECT_PDF_GENERATED', date('F j, Y'), \count($this->items)); ?>
    </div>
<?php endif; ?>

<?php
$currentLetter = null;

if ($this->pdfLayout === 'photo_grid') {
    $renderGrid($this->items);
} elseif ($this->pdfLayout === 'roster') {
    $renderRoster($this->items, $this->showSectionHeaders);
} else {
    foreach ($this->items as $item) {
        if ($this->showSectionHeaders) {
            $emitDivider($item);
        }

        $renderEntry($item, false);
    }
}

// Photos-front / roster-back: append a text roster after the photo pages.
if ($this->appendRoster && $this->pdfLayout !== 'roster') {
    echo '<div class="pagebreak"></div>';
    echo '<h2 class="section-heading">' . Text::_('COM_CWMCONNECT_PDF_ROSTER_HEADING') . '</h2>';
    $renderRoster($this->items, $this->showSectionHeaders);
}
