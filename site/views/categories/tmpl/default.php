<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

?>
<div class="categories-list<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading', 1)) : ?>
    <h1>
		<?php echo $this->escape($this->params->get('page_heading')); ?>
    </h1>
	<?php endif; ?>
	<?php if ($this->params->get('show_base_description')) : ?>
	<?php // If there is a description in the menu parameters use that; ?>
	<?php if ($this->params->get('categories_description')) : ?>
        <div class="category-desc base-desc">
			<?php echo JHtml::_('content.prepare', $this->params->get('categories_description')); ?>
        </div>
		<?php else: ?>
		<?php // Otherwise get one from the database if it exists.  ?>
		<?php if ($this->parent->description) : ?>
            <div class="category-desc base-desc">
				<?php echo JHtml::_('content.prepare', $this->parent->description); ?>
            </div>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
	<?php
	echo $this->loadTemplate('items');
	?>
</div>
