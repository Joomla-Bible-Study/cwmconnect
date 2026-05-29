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

/** @var \CWM\Component\Cwmconnect\Site\View\Members\PdfView $this */

$currentLetter = null;

/**
 * Render one directory entry (photo-left, details-right). $isStaff adds the
 * position line under the name. Defined as a closure so the staff section and
 * the main listing share identical markup.
 */
$renderEntry = function (object $item, bool $isStaff): void {
    $photo    = $this->memberPhotoPath($item);
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
                <div class="name"><?php echo $this->escape($this->memberName($item)); ?></div>

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
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #2b2b2b; }
    h1 { font-size: 18pt; margin: 0 0 2mm; color: #1a1a1a; }
    .meta { font-size: 8pt; color: #888; margin-bottom: 6mm; }

    /* Cover page. */
    .cover { text-align: center; padding-top: 30mm; }
    .cover img.cover-img { margin-bottom: 10mm; border: 0.5pt solid #ccc; }
    .cover .cover-name { font-size: 24pt; font-weight: bold; color: #1a1a1a; margin-bottom: 5mm; }
    .cover .cover-line { font-size: 11pt; color: #555; line-height: 1.6; }

    /* Section heading (e.g. "Our Staff"). */
    .section-heading {
        font-size: 16pt; font-weight: bold; color: #444;
        border-bottom: 1.5pt solid #888;
        margin: 0 0 4mm; padding-bottom: 1mm;
    }

    /* Alphabetical section divider. */
    .letter {
        font-size: 15pt; font-weight: bold; color: #555;
        border-bottom: 1pt solid #999;
        margin: 4mm 0 2mm; padding-bottom: 1mm;
    }

    /* One member entry — a 2-cell table so the photo and details sit
       side by side and the whole block never splits across a page. */
    .entry { width: 100%; page-break-inside: avoid; margin-bottom: 4mm; }
    .entry td { vertical-align: top; padding: 0; }
    .photo-cell { width: 32mm; }
    /* mpdf only reliably honours sizing as an inline attribute/style, so the
       width is set inline on the <img> below; this just styles the border. */
    .entry img.photo { border: 0.5pt solid #ccc; }
    .no-photo {
        width: 30mm; height: 36mm;
        background: #eef0f2; border: 0.5pt solid #ccc;
        color: #9aa0a6; font-size: 18pt; font-weight: bold;
        text-align: center;
    }
    .no-photo span { vertical-align: middle; }

    .details { padding-left: 4mm; }
    .details .name { font-size: 12pt; font-weight: bold; color: #1a1a1a; }
    .details .line { font-size: 9.5pt; line-height: 1.35; }
    .details .position { font-style: italic; color: #555; }
    .details .muted { color: #777; }
    .details .email { color: #1a5276; }

    /* Hairline under each entry. */
    .rule { border-bottom: 0.25pt solid #e2e2e2; margin-bottom: 4mm; }

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

<?php // ── Staff section ────────────────────────────────────────────────?>
<?php if ($this->staff !== []) : ?>
    <h2 class="section-heading"><?php echo Text::_('COM_CWMCONNECT_PDF_STAFF_HEADING'); ?></h2>
    <?php foreach ($this->staff as $member) : ?>
        <?php $renderEntry($member, true); ?>
    <?php endforeach; ?>
    <div class="pagebreak"></div>
<?php endif; ?>

<?php // ── Member listing ───────────────────────────────────────────────?>
<h1><?php echo Text::_('COM_CWMCONNECT_PDF_TITLE'); ?></h1>
<div class="meta">
    <?php echo Text::sprintf('COM_CWMCONNECT_PDF_GENERATED', date('F j, Y'), \count($this->items)); ?>
</div>

<?php foreach ($this->items as $item) : ?>
    <?php if ($this->showSectionHeaders) : ?>
        <?php
        $surname = trim((string) ($item->surname ?? '')) ?: trim((string) ($item->lname ?? ''));
        $letter  = strtoupper(mb_substr($surname, 0, 1)) ?: '#';
        ?>
        <?php if ($letter !== $currentLetter) : ?>
            <?php $currentLetter = $letter; ?>
            <div class="letter"><?php echo $this->escape($letter); ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php $renderEntry($item, false); ?>
<?php endforeach; ?>
