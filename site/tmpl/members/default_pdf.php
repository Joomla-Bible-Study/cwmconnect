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
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; }
    h1 { font-size: 16pt; margin-bottom: 4mm; color: #222; }
    .meta { font-size: 8pt; color: #888; margin-bottom: 6mm; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f0f0f0; text-align: left; padding: 2mm 3mm; font-size: 9pt; border-bottom: 0.5pt solid #999; }
    td { padding: 2mm 3mm; font-size: 9pt; border-bottom: 0.25pt solid #ddd; vertical-align: top; }
    tr:nth-child(even) td { background: #fafafa; }
    .no-wrap { white-space: nowrap; }
</style>

<h1><?php echo Text::_('COM_CWMCONNECT_PDF_TITLE'); ?></h1>
<div class="meta">
    <?php echo Text::sprintf('COM_CWMCONNECT_PDF_GENERATED', date('F j, Y'), \count($this->items)); ?>
</div>

<table>
    <thead>
        <tr>
            <th><?php echo Text::_('COM_CWMCONNECT_PDF_COL_NAME'); ?></th>
            <th><?php echo Text::_('COM_CWMCONNECT_PDF_COL_LASTNAME'); ?></th>
            <th><?php echo Text::_('COM_CWMCONNECT_PDF_COL_EMAIL'); ?></th>
            <th><?php echo Text::_('COM_CWMCONNECT_PDF_COL_PHONE'); ?></th>
            <th><?php echo Text::_('COM_CWMCONNECT_PDF_COL_MOBILE'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->items as $item) : ?>
            <tr>
                <td><?php echo $this->escape((string) $item->name); ?></td>
                <td><?php echo $this->escape((string) $item->lname); ?></td>
                <td><?php echo $this->escape((string) ($item->email_to ?? '')); ?></td>
                <td class="no-wrap"><?php echo $this->escape((string) ($item->telephone ?? '')); ?></td>
                <td class="no-wrap"><?php echo $this->escape((string) ($item->mobile ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
