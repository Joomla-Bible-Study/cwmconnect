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
$renderEntry = function (object $item, string $role = ''): void {
    $photo = $this->memberPhotoSrc($item);
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

                <?php if ($role !== '') : ?>
                    <div class="line position"><?php echo $this->escape($role); ?></div>
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
 * Compact member block for one cell of the two-column photo-detail grid:
 * a small photo beside name/address/anniversary/phone/email. No staff
 * position line (that section stays full-width via $renderEntry).
 */
$renderCellInner = function (object $item): void {
    $photo    = $this->memberPhotoSrc($item);
    $locality = $this->memberLocality($item);
    ?>
    <table class="cell-entry">
        <tr>
            <td class="cell-photo-cell">
                <?php if ($photo !== null) : ?>
                    <img class="photo" src="<?php echo $this->escape($photo); ?>" width="83" alt="" />
                <?php else : ?>
                    <div class="cell-no-photo"><span><?php echo $this->escape($this->memberInitials($item)); ?></span></div>
                <?php endif; ?>
            </td>
            <td class="cell-details">
                <div class="name"><?php echo $this->escape($this->memberName($item)); ?><?php if ($this->isHidden($item)) : ?> <span class="hidden-badge">hidden</span><?php endif; ?></div>

                <?php if (!empty($item->address)) : ?>
                    <div class="line"><?php echo $this->escape((string) $item->address); ?></div>
                <?php endif; ?>

                <?php if ($locality !== '') : ?>
                    <div class="line"><?php echo $this->escape($locality); ?></div>
                <?php endif; ?>

                <?php if ($anniversary = $this->memberAnniversary($item)) : ?>
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
    <?php
};

/**
 * Render a list of members as a two-column grid (two entries per row). The
 * outer table paginates between rows; each cell avoids splitting internally.
 */
$renderTwoColumn = function (array $members) use ($renderCellInner): void {
    $members = array_values($members);
    $count   = \count($members);

    echo '<table class="entry-grid"><tbody>';

    for ($i = 0; $i < $count; $i += 2) {
        echo '<tr><td class="entry-cell">';
        $renderCellInner($members[$i]);
        echo '</td><td class="entry-cell">';

        if (isset($members[$i + 1])) {
            $renderCellInner($members[$i + 1]);
        }

        echo '</td></tr>';
    }

    echo '</tbody></table>';
};

/**
 * Family-photo grid: one card per household — the family photo + the family
 * headline name ("SURNAME, Given and Given"), three per row. Mirrors the
 * reference directory's pictorial grid.
 */
$familyGrid = function (array $households): void {
    $columns = 3;
    $count   = \count($households);

    for ($i = 0; $i < $count; $i += $columns) :
        ?>
        <table class="grid">
            <tr>
                <?php for ($c = 0; $c < $columns; $c++) : ?>
                    <?php $household = $households[$i + $c] ?? null; ?>
                    <td class="grid-cell">
                        <?php if ($household !== null) : ?>
                            <?php $src = $this->householdPhotoSrc($household); ?>
                            <?php if ($src !== null) : ?>
                                <img class="grid-photo" src="<?php echo $this->escape($src); ?>" width="178" alt="" />
                            <?php else : ?>
                                <div class="grid-nophoto"><span><?php echo $this->escape(mb_strtoupper(mb_substr((string) $household['surname'], 0, 2))); ?></span></div>
                            <?php endif; ?>
                            <div class="grid-name"><?php echo $this->escape($this->householdDisplayName($household)); ?></div>
                        <?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
        </table>
        <?php
    endfor;
};

/**
 * One household block for the family detail grid: the family photo beside the
 * family headline, shared address, then each member's given name + personal
 * contact.
 */
$familyCellInner = function (array $household): void {
    $src      = $this->householdPhotoSrc($household);
    $head     = $this->householdHead($household);
    $locality = $this->householdLocality($household);
    ?>
    <table class="cell-entry">
        <tr>
            <td class="cell-photo-cell">
                <?php if ($src !== null) : ?>
                    <img class="photo" src="<?php echo $this->escape($src); ?>" width="83" alt="" />
                <?php else : ?>
                    <div class="cell-no-photo"><span><?php echo $this->escape(mb_strtoupper(mb_substr((string) $household['surname'], 0, 2))); ?></span></div>
                <?php endif; ?>
            </td>
            <td class="cell-details">
                <div class="name"><?php echo $this->escape($this->householdDisplayName($household)); ?></div>

                <?php if (!empty($head->address)) : ?>
                    <div class="line"><?php echo $this->escape((string) $head->address); ?></div>
                <?php endif; ?>
                <?php if ($locality !== '') : ?>
                    <div class="line"><?php echo $this->escape($locality); ?></div>
                <?php endif; ?>

                <?php foreach ($household['members'] as $member) : ?>
                    <?php
                    $contact = array_values(array_filter([
                        trim((string) ($member->telephone ?? '')) ?: trim((string) ($member->mobile ?? '')),
                        trim((string) ($member->email_to ?? '')),
                    ]));
                    ?>
                    <div class="line member">
                        <strong><?php echo $this->escape($this->memberGiven($member)); ?></strong><?php if ($contact !== []) : ?> — <?php echo $this->escape(implode(' · ', $contact)); ?><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>
    <?php
};

/**
 * Render households as a two-column family detail grid (two per row).
 */
$familyDetail = function (array $households) use ($familyCellInner): void {
    $households = array_values($households);
    $count      = \count($households);

    echo '<table class="entry-grid"><tbody>';

    for ($i = 0; $i < $count; $i += 2) {
        echo '<tr><td class="entry-cell">';
        $familyCellInner($households[$i]);
        echo '</td><td class="entry-cell">';

        if (isset($households[$i + 1])) {
            $familyCellInner($households[$i + 1]);
        }

        echo '</td></tr>';
    }

    echo '</tbody></table>';
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
                                <img class="grid-photo" src="<?php echo $this->escape($src); ?>" width="178" alt="" />
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
    .cover { text-align: center; padding-top: 12mm; }
    .cover img.cover-img { margin-bottom: 8mm; border: 0.5pt solid #ccc; }
    .cover .cover-name { font-size: 2.6em; font-weight: bold; color: #1a1a1a; margin-bottom: 4mm; }
    .cover .cover-line { font-size: 1.05em; color: #555; line-height: 1.55; }

    /* Welcome letter: a letterhead (church name + contact) over the body. */
    .welcome { padding-top: 4mm; }
    .welcome-head { margin-bottom: 8mm; }
    .welcome-church { font-size: 1.9em; font-weight: bold; color: #1a1a1a; margin-bottom: 2mm; }
    .welcome-line { font-size: 0.9em; color: #555; line-height: 1.5; }
    .welcome-body { font-size: 1em; line-height: 1.55; color: #2b2b2b; }
    .welcome-body p { margin: 0 0 3mm; }

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

    /* Photo-detail two-column member grid. */
    .entry-grid { width: 100%; }
    .entry-grid td.entry-cell {
        width: 50%; vertical-align: top;
        padding: 0 4mm 4mm 0;
        page-break-inside: avoid;
    }
    .cell-entry { width: 100%; }
    .cell-entry td { vertical-align: top; padding: 0; }
    .cell-photo-cell { width: 25mm; }
    .cell-entry img.photo { border: 0.5pt solid #ccc; }
    .cell-no-photo {
        width: 22mm; height: 29mm;
        background: #eef0f2; border: 0.5pt solid #ccc;
        color: #9aa0a6; font-size: 1.4em; font-weight: bold; text-align: center;
    }
    .cell-no-photo span { vertical-align: middle; }
    .cell-details { padding-left: 3mm; }
    .cell-details .name { font-size: 1.05em; font-weight: bold; color: #1a1a1a; }
    .cell-details .line { font-size: 0.82em; line-height: 1.3; }
    .cell-details .muted { color: #777; }
    .cell-details .email { color: #1a5276; }

    /* Photo grid (compact cards). */
    .grid { width: 100%; page-break-inside: avoid; }
    .grid td.grid-cell { width: 33%; text-align: center; vertical-align: top; padding: 0 2mm 6mm; }
    .grid img.grid-photo { border: 0.5pt solid #ccc; }
    .grid .grid-nophoto {
        width: 47mm; height: 63mm; margin: 0 auto;
        background: #eef0f2; border: 0.5pt solid #ccc;
        color: #9aa0a6; font-size: 2.6em; font-weight: bold; text-align: center;
    }
    .grid .grid-name { font-weight: bold; font-size: 0.95em; margin-top: 2mm; }
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
            <img class="cover-img" src="<?php echo $this->escape((string) $this->cover['image']); ?>" width="620" alt="" />
        <?php endif; ?>
        <div class="cover-name"><?php echo $this->escape((string) $this->cover['name']); ?></div>
        <?php foreach (preg_split('/\r\n|\r|\n/', (string) $this->cover['address']) as $addressLine) : ?>
            <?php if (trim($addressLine) !== '') : ?>
                <div class="cover-line"><?php echo $this->escape(trim($addressLine)); ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php $coverContact = array_filter([(string) $this->cover['phone'], (string) $this->cover['email']]); ?>
        <?php if ($coverContact !== []) : ?>
            <div class="cover-line"><?php echo $this->escape(implode('  |  ', $coverContact)); ?></div>
        <?php endif; ?>
        <?php if (!empty($this->cover['website'])) : ?>
            <div class="cover-line"><?php echo $this->escape((string) $this->cover['website']); ?></div>
        <?php endif; ?>
    </div>
    <div class="pagebreak"></div>
<?php endif; ?>

<?php // ── Welcome letter (trusted admin rich text on its own page) ────────?>
<?php if (trim((string) $this->welcome) !== '') : ?>
    <div class="welcome">
        <?php if (!empty($this->cover['name'])) : ?>
            <div class="welcome-head">
                <div class="welcome-church"><?php echo $this->escape((string) $this->cover['name']); ?></div>
                <?php foreach (preg_split('/\r\n|\r|\n/', (string) ($this->cover['address'] ?? '')) as $headLine) : ?>
                    <?php if (trim($headLine) !== '') : ?>
                        <div class="welcome-line"><?php echo $this->escape(trim($headLine)); ?></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php $welcomeContact = array_filter([(string) ($this->cover['phone'] ?? ''), (string) ($this->cover['email'] ?? ''), (string) ($this->cover['website'] ?? '')]); ?>
                <?php if ($welcomeContact !== []) : ?>
                    <div class="welcome-line"><?php echo $this->escape(implode('  |  ', $welcomeContact)); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="welcome-body"><?php echo $this->welcome; ?></div>
    </div>
    <div class="pagebreak"></div>
<?php endif; ?>

<?php // ── Front-matter sections: Church Board, Officers, Staff ───────────?>
<?php
$frontSections = [
    ['heading' => Text::_('COM_CWMCONNECT_PDF_BOARD_HEADING'),    'members' => $this->board],
    ['heading' => Text::_('COM_CWMCONNECT_PDF_OFFICERS_HEADING'), 'members' => $this->officers],
    ['heading' => Text::_('COM_CWMCONNECT_PDF_STAFF_HEADING'),    'members' => $this->staff],
];

$renderedSection = false;

foreach ($frontSections as $section) {
    if ($section['members'] === []) {
        continue;
    }

    $renderedSection = true;

    echo '<h2 class="section-heading">' . $this->escape($section['heading']) . '</h2>';

    foreach ($section['members'] as $sectionMember) {
        $renderEntry($sectionMember, $this->memberRole($sectionMember));
    }
}

if ($renderedSection) {
    echo '<div class="pagebreak"></div>';
}
?>

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
} elseif ($this->pdfLayout === 'family') {
    // Family-centric (mirrors the reference): a family-photo grid, then family
    // detail pages grouped under alphabetical surname dividers.
    $households = $this->households();

    $familyGrid($households);

    echo '<div class="pagebreak"></div>';
    echo '<h2 class="section-heading">' . Text::_('COM_CWMCONNECT_PDF_FAMILIES_HEADING') . '</h2>';

    if ($this->showSectionHeaders) {
        $groupedFamilies = [];

        foreach ($households as $household) {
            $letter                     = strtoupper(mb_substr((string) $household['surname'], 0, 1)) ?: '#';
            $groupedFamilies[$letter][] = $household;
        }

        foreach ($groupedFamilies as $letter => $group) {
            echo '<div class="letter">' . $this->escape((string) $letter) . '</div>';
            $familyDetail($group);
        }
    } else {
        $familyDetail($households);
    }
} else {
    // photo_detail — two columns, with full-width alphabetical dividers
    // between letter groups so the columns reset cleanly under each header.
    $grouped = [];

    if ($this->showSectionHeaders) {
        foreach ($this->items as $item) {
            $surname            = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));
            $letter             = strtoupper(mb_substr($surname, 0, 1)) ?: '#';
            $grouped[$letter][] = $item;
        }
    } else {
        $grouped[''] = $this->items;
    }

    foreach ($grouped as $letter => $members) {
        if ($letter !== '') {
            echo '<div class="letter">' . $this->escape($letter) . '</div>';
        }

        $renderTwoColumn($members);
    }
}

// Photos-front / roster-back: append a text roster after the photo pages.
if ($this->appendRoster && $this->pdfLayout !== 'roster') {
    echo '<div class="pagebreak"></div>';
    echo '<h2 class="section-heading">' . Text::_('COM_CWMCONNECT_PDF_ROSTER_HEADING') . '</h2>';
    $renderRoster($this->items, $this->showSectionHeaders);
}
