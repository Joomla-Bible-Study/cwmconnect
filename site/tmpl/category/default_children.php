<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Connect\Site\View\Category\HtmlView $this */

$class = ' class="first"';
?>
<?php if (\count($this->children[$this->category->id]) > 0 && $this->maxLevel != 0) : ?>
    <ul class="list-striped list-condensed">
        <?php foreach ($this->children[$this->category->id] as $id => $child) : ?>
            <?php if (
                $this->params->get('show_empty_categories')
                || $child->numitems
                || \count($child->getChildren())
            ) : ?>
                <?php if (!isset($this->children[$this->category->id][$id + 1])) {
                    $class = ' class="last"';
                } ?>
                <li<?php echo $class; ?>>
                    <?php $class = ''; ?>
                    <h4 class="item-title">
                        <a href="<?php echo Route::_(RouteHelper::getCategoryRoute($child->id)); ?>">
                            <?php echo $this->escape($child->title); ?>
                        </a>
                        <?php if ($this->params->get('show_cat_items') == 1) : ?>
                            <span class="badge bg-info float-end"
                                  title="<?php echo Text::_('COM_CWMCONNECT_CAT_NUM'); ?>">
                                <?php echo $child->numitems; ?>
                            </span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($this->params->get('show_subcat_desc') == 1 && $child->description) : ?>
                        <small class="category-desc">
                            <?php echo HTMLHelper::_('content.prepare', $child->description, '', 'com_cwmconnect.category'); ?>
                        </small>
                    <?php endif; ?>
                    <?php if (\count($child->getChildren()) > 0) :
                        $this->children[$child->id] = $child->getChildren();
                        $this->category             = $child;
                        $this->maxLevel--;
                        echo $this->loadTemplate('children');
                        $this->category = $child->getParent();
                        $this->maxLevel++;
                    endif; ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
