<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Cwmconnect\Site\View\Members\HtmlView $this */

$photosBase  = Uri::root(true) . '/media/com_cwmconnect/photos/';
$search      = (string) $this->state->get('filter.search', '');
$layoutMode  = $this->layoutMode === 'table' ? 'table' : 'grid';
$gridActive  = $layoutMode === 'grid' ? ' active' : '';
$tableActive = $layoutMode === 'table' ? ' active' : '';
?>
<div class="cwmconnect-members">
    <form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=members'); ?>" method="get" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="option" value="com_cwmconnect">
        <input type="hidden" name="view" value="members">

        <div class="col-md-6">
            <label for="filter_search" class="form-label"><?php echo Text::_('COM_CWMCONNECT_MEMBERS_FILTER_SEARCH_LABEL'); ?></label>
            <input type="search" name="filter_search" id="filter_search" class="form-control"
                   value="<?php echo $this->escape($search); ?>"
                   placeholder="<?php echo $this->escape(Text::_('COM_CWMCONNECT_MEMBERS_FILTER_SEARCH_HINT')); ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label"><?php echo Text::_('COM_CWMCONNECT_MEMBERS_LAYOUT_LABEL'); ?></label>
            <div class="btn-group d-flex" role="group" aria-label="<?php echo $this->escape(Text::_('COM_CWMCONNECT_MEMBERS_LAYOUT_LABEL')); ?>">
                <button type="submit" name="layout_mode" value="grid" class="btn btn-outline-secondary<?php echo $gridActive; ?>">
                    <span class="icon-grid-2" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MEMBERS_LAYOUT_GRID'); ?>
                </button>
                <button type="submit" name="layout_mode" value="table" class="btn btn-outline-secondary<?php echo $tableActive; ?>">
                    <span class="icon-list" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_MEMBERS_LAYOUT_TABLE'); ?>
                </button>
            </div>
        </div>

        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary">
                <span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>
            </button>
        </div>
    </form>

    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="<?php echo Route::_('index.php?option=com_cwmconnect&view=members&format=pdf'); ?>" class="btn btn-outline-secondary btn-sm">
            <span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_PDF_DOWNLOAD_BUTTON'); ?>
        </a>
        <a href="<?php echo Route::_('index.php?option=com_cwmconnect&view=members&format=kml'); ?>" class="btn btn-outline-secondary btn-sm">
            <span class="icon-location" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_KML_DOWNLOAD_BUTTON'); ?>
        </a>
    </div>

    <?php if ($this->items === []) : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_CWMCONNECT_MEMBERS_EMPTY'); ?>
        </div>
    <?php elseif ($layoutMode === 'grid') : ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 cwmconnect-photo-grid">
            <?php foreach ($this->items as $item) :
                $profileUrl = Route::_('index.php?option=com_cwmconnect&view=member&id=' . (int) $item->id);
                $imgPath    = (string) ($item->image ?? '');
                $imgUrl     = $imgPath !== '' ? $photosBase . rawurlencode($imgPath) : '';
                $name       = trim(($item->name ?: '') ?: ($item->lname ?: ''));
                ?>
                <div class="col">
                    <a class="card h-100 text-decoration-none text-body" href="<?php echo $profileUrl; ?>">
                        <?php if ($imgUrl !== '') : ?>
                            <img class="card-img-top" src="<?php echo $this->escape($imgUrl); ?>"
                                 alt="<?php echo $this->escape($name); ?>" loading="lazy">
                        <?php else : ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted" style="aspect-ratio: 1/1;">
                                <span class="icon-user" aria-hidden="true" style="font-size: 3rem;"></span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="fw-semibold"><?php echo $this->escape($name); ?></div>
                            <?php if ($item->category_title) : ?>
                                <div class="small text-muted"><?php echo $this->escape((string) $item->category_title); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <table class="table table-striped">
            <caption class="visually-hidden"><?php echo Text::_('COM_CWMCONNECT_MEMBERS_TABLE_CAPTION'); ?></caption>
            <thead>
                <tr>
                    <th scope="col"><?php echo Text::_('COM_CWMCONNECT_MEMBERS_TABLE_NAME'); ?></th>
                    <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_CWMCONNECT_MEMBERS_TABLE_CATEGORY'); ?></th>
                    <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_CWMCONNECT_MEMBERS_TABLE_HOUSEHOLD'); ?></th>
                    <th scope="col" class="d-none d-lg-table-cell"><?php echo Text::_('COM_CWMCONNECT_MEMBERS_TABLE_CONTACT'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $item) :
                $profileUrl = Route::_('index.php?option=com_cwmconnect&view=member&id=' . (int) $item->id);
                ?>
                <tr>
                    <td>
                        <a href="<?php echo $profileUrl; ?>">
                            <?php echo $this->escape(trim(($item->name ?: '') ?: ($item->lname ?: ''))); ?>
                        </a>
                    </td>
                    <td class="d-none d-md-table-cell"><?php echo $this->escape((string) ($item->category_title ?? '')); ?></td>
                    <td class="d-none d-md-table-cell"><?php echo $this->escape((string) ($item->household_name ?? '')); ?></td>
                    <td class="d-none d-lg-table-cell">
                        <?php if (!empty($item->email_to)) : ?>
                            <a href="mailto:<?php echo $this->escape((string) $item->email_to); ?>"><?php echo $this->escape((string) $item->email_to); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php echo $this->pagination?->getListFooter(); ?>
</div>
