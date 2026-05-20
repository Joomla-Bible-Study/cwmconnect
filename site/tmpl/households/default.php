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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Households\HtmlView $this */

$search = (string) $this->state->get('filter.search', '');
?>
<div class="cwmconnect-households">
    <form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=households'); ?>" method="get" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="option" value="com_cwmconnect">
        <input type="hidden" name="view" value="households">

        <div class="col-md-9">
            <label for="filter_search" class="form-label"><?php echo Text::_('COM_CWMCONNECT_HOUSEHOLDS_FILTER_SEARCH_LABEL'); ?></label>
            <input type="search" name="filter_search" id="filter_search" class="form-control"
                   value="<?php echo $this->escape($search); ?>"
                   placeholder="<?php echo $this->escape(Text::_('COM_CWMCONNECT_HOUSEHOLDS_FILTER_SEARCH_HINT')); ?>">
        </div>

        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary">
                <span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>
            </button>
        </div>
    </form>

    <?php if ($this->items === []) : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_CWMCONNECT_HOUSEHOLDS_EMPTY'); ?>
        </div>
    <?php else : ?>
        <ul class="list-group cwmconnect-household-list">
            <?php foreach ($this->items as $item) :
                $count = (int) ($item->visible_count ?? 0);
                $url   = Route::_('index.php?option=com_cwmconnect&view=members&filter_household_id=' . (int) $item->id);
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="<?php echo $url; ?>" class="text-decoration-none">
                        <span class="icon-users me-2" aria-hidden="true"></span>
                        <?php echo $this->escape((string) $item->name); ?>
                    </a>
                    <span class="badge bg-secondary rounded-pill">
                        <?php echo Text::plural('COM_CWMCONNECT_HOUSEHOLDS_MEMBER_COUNT_N', $count); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php echo $this->pagination?->getListFooter(); ?>
</div>
