<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \CWM\Component\Churchdirectory\Site\View\Featured\HtmlView $this */
?>
<div class="blog-featured<?php echo $this->pageclass_sfx; ?>">
    <?php if ($this->params->get('show_page_heading') != 0) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
    <?php endif; ?>

    <?php echo $this->loadTemplate('items'); ?>

    <?php
    $showPagination = $this->params->def('show_pagination', 2) == 1
        || ($this->params->get('show_pagination') == 2 && $this->pagination->get('pages.total') > 1);
    ?>
    <?php if ($showPagination) : ?>
        <div class="pagination">
            <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                <p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
            <?php endif; ?>
            <?php echo $this->pagination->getPagesLinks(); ?>
        </div>
    <?php endif; ?>
</div>
