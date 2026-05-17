<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Categories\HtmlView $this */

$class = ' class="first"';

if ($this->maxLevelcat != 0 && \count($this->items[$this->parent->id]) > 0) :
    foreach ($this->items[$this->parent->id] as $id => $item) :
        if (
            $this->params->get('show_empty_categories_cat')
            || $item->numitems
            || \count($item->getChildren())
        ) :
            if (!isset($this->items[$this->parent->id][$id + 1])) {
                $class = ' class="last"';
            }
            ?>
            <div<?php echo $class; ?>>
                <?php $class = ''; ?>
                <h3 class="page-header item-title">
                    <a href="<?php echo Route::_(RouteHelper::getCategoryRoute($item->id, $item->language)); ?>">
                        <?php echo $this->escape($item->title); ?>
                    </a>
                    <?php if ($this->params->get('show_cat_items_cat') == 1) : ?>
                        <span class="badge bg-info"
                              title="<?php echo HTMLHelper::_('tooltipText', 'COM_CWMCONNECT_NUM_ITEMS'); ?>">
                            <?php echo Text::_('COM_CWMCONNECT_NUM_ITEMS'); ?>&nbsp;<?php echo $item->numitems; ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($this->maxLevelcat > 1 && \count($item->getChildren()) > 0) : ?>
                        <a id="category-btn-<?php echo $item->id; ?>" href="#category-<?php echo $item->id; ?>"
                           data-bs-toggle="collapse" class="btn btn-sm btn-secondary float-end">
                            <span class="icon-plus"></span>
                        </a>
                    <?php endif; ?>
                </h3>

                <?php if ($this->params->get('show_subcat_desc_cat') == 1 && $item->description) : ?>
                    <div class="category-desc">
                        <?php echo HTMLHelper::_('content.prepare', $item->description, '', 'com_cwmconnect.categories'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($this->maxLevelcat > 1 && \count($item->getChildren()) > 0) : ?>
                    <div class="collapse" id="category-<?php echo $item->id; ?>">
                        <?php
                        $this->items[$item->id] = $item->getChildren();
                    $this->parent           = $item;
                    $this->maxLevelcat--;
                    echo $this->loadTemplate('items');
                    $this->parent       = $item->getParent();
                    $this->maxLevelcat++;
                    ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
