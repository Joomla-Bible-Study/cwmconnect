<?php
/**
 * @version		$Id: default.php 71 $
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;
?>
<div class="churchdirectory-category<?php echo $this->pageclass_sfx; ?>">
<?php if ($this->params->def('show_page_heading', 1)) : ?>
        <h1>
        <?php echo $this->escape($this->params->get('page_heading')); ?>
        </h1>
        <?php endif; ?>
    <?php if ($this->params->get('show_category_title', 1)) : ?>
        <h2>
        <?php echo JHtml::_('content.prepare', $this->category->title); ?>
        </h2>
        <?php endif; ?>
    <?php if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) : ?>
        <div class="category-desc">
        <?php if ($this->params->get('show_description_image') && $this->category->getParams()->get('image')) : ?>
                <img src="<?php echo $this->category->getParams()->get('image'); ?>"/>
            <?php endif; ?>
            <?php if ($this->params->get('show_description') && $this->category->description) : ?>
                <?php echo JHtml::_('content.prepare', $this->category->description); ?>
            <?php endif; ?>
            <div class="clr"></div>
        </div>
<?php endif; ?>

    <?php echo $this->loadTemplate('items'); ?>

    <?php if (!empty($this->children[$this->category->id]) && $this->maxLevel != 0) : ?>
        <div class="cat-children">
            <h3><?php echo JText::_('JGLOBAL_SUBCATEGORIES'); ?></h3>
    <?php echo $this->loadTemplate('children'); ?>
        </div>
        <?php endif; ?>
</div>
