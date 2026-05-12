<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Churchdirectory\Site\View\Category\HtmlView $this */

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$input   = Factory::getApplication()->getInput();
$isModal = $input->getInt('print') === 1;
$itemId  = $input->getInt('Itemid');

if ($isModal) {
    $href    = '"#" onclick="window.print(); return false;"';
    $kmlhref = '';
} else {
    $popup   = "window.open(this.href,'win2','status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,"
        . "resizable=yes,width=640,height=480,directories=no,location=no'); return false;";
    $href    = '"index.php?option=com_churchdirectory&view=category&id=' . $this->category->id
        . '&Itemid=' . $itemId . '&tmpl=component&print=1" onclick="' . $popup . '"';
    $kmlhref = '"index.php?option=com_churchdirectory&view=category&id=' . $this->category->id
        . '&Itemid=' . $itemId . '&tmpl=component&format=kml"';
}
?>
<div class="churchdirectory-category<?php echo $this->pageclass_sfx; ?>">
    <?php if ($this->params->def('show_page_heading', 1)) : ?>
        <div class="page-header">
            <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
        </div>
    <?php endif; ?>

    <?php if ($this->params->get('show_category_title', 1)) : ?>
        <h2><?php echo HTMLHelper::_('content.prepare', $this->category->title); ?></h2>
    <?php endif; ?>

    <a href="<?php echo Route::_('index.php?option=com_churchdirectory&view=home'); ?>">Members Home -&gt;</a>
    <?php echo $this->escape($this->category->title); ?>

    <div class="float-end">
        <a href=<?php echo $href; ?>>
            <?php echo HTMLHelper::image('media/com_churchdirectory/images/printButton.png', 'Print', ''); ?>
        </a>
        <?php if ($kmlhref) : ?>
            <a href=<?php echo $kmlhref; ?>>
                <?php echo HTMLHelper::image('media/com_churchdirectory/images/kmlButton.png', 'KML', ''); ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) : ?>
        <div class="category-desc">
            <?php if ($this->params->get('show_description_image') && $this->category->getParams()->get('image')) : ?>
                <img src="<?php echo $this->escape($this->category->getParams()->get('image')); ?>" alt="">
            <?php endif; ?>
            <?php if ($this->params->get('show_description') && $this->category->description) : ?>
                <?php echo HTMLHelper::_('content.prepare', $this->category->description); ?>
            <?php endif; ?>
            <div class="clr"></div>
        </div>
    <?php endif; ?>

    <?php if (empty($this->items)) : ?>
        <p><?php echo Text::_('COM_CHURCHDIRECTORY_NO_MEMBERS'); ?></p>
    <?php else : ?>
        <form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
              name="adminForm" id="adminForm">
            <?php if ($this->params->get('show_pagination_limit') && !$isModal) : ?>
                <fieldset class="filters btn-toolbar">
                    <?php if ($this->params->get('filter_field') == 1) : ?>
                        <div class="btn-group">
                            <label class="filter-search-lbl element-invisible" for="filter-search">
                                <span class="badge bg-warning"><?php echo Text::_('JUNPUBLISHED'); ?></span>
                                <?php echo Text::_('COM_CHURCHDIRECTORY_FILTER_LABEL'); ?>&#160;
                            </label>
                            <input type="text" name="filter-search" id="filter-search"
                                   value="<?php echo $this->escape($this->state->get('list.filter')); ?>"
                                   class="form-control"
                                   onchange="document.adminForm.submit();"
                                   title="<?php echo Text::_('COM_CHURCHDIRECTORY_FILTER_SEARCH_DESC'); ?>"
                                   placeholder="<?php echo Text::_('COM_CHURCHDIRECTORY_FILTER_SEARCH_DESC'); ?>"/>
                        </div>
                    <?php endif; ?>
                    <div class="btn-group float-end">
                        <label for="limit" class="element-invisible">
                            <?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?>
                        </label>
                        <?php echo $this->pagination->getLimitBox(); ?>
                    </div>
                </fieldset>
            <?php endif; ?>

            <?php echo $this->loadTemplate('teamleaders'); ?>
            <?php echo $this->loadTemplate('items'); ?>

            <?php if ($this->params->get('show_pagination')) : ?>
                <div class="pagination">
                    <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                        <p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
                    <?php endif; ?>
                    <?php echo $this->pagination->getPagesLinks(); ?>
                </div>
            <?php endif; ?>

            <div>
                <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
                <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
            </div>
        </form>
    <?php endif; ?>

    <?php if (!empty($this->children[$this->category->id]) && $this->maxLevel != 0) : ?>
        <div class="cat-children">
            <h3><?php echo Text::_('JGLOBAL_SUBCATEGORIES'); ?></h3>
            <?php echo $this->loadTemplate('children'); ?>
        </div>
    <?php endif; ?>

    <div class="clearfix"></div>

    <?php if ($this->params->def('show_page_birthann', 0)) : ?>
        <?php echo $this->loadTemplate('birthann'); ?>
    <?php endif; ?>
</div>
