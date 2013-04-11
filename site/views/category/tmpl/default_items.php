<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$this->loadHelper('render');
$renderHelper = new renderHelper();
?>
<?php if (empty($this->items)) : ?>
<p> <?php echo JText::_('COM_CHURCHDIRECTORY_NO_MEMBERS'); ?>     </p>
<?php else : ?>
<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm"
      id="adminForm">
	<?php if ($this->params->get('show_pagination_limit')) : ?>
    <fieldset class="filters btn-toolbar">
		<?php if ($this->params->get('filter_field') != 'hide') : ?>
        <div class="btn-group">
            <label class="filter-search-lbl element-invisible" for="filter-search"><span
                    class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span><?php echo JText::_('COM_CHURCHDIRECTORY_FILTER_LABEL') . '&#160;'; ?>
            </label>
            <input type="text" name="filter-search" id="filter-search"
                   value="<?php echo $this->escape($this->state->get('list.filter')); ?>" class="inputbox"
                   onchange="document.adminForm.submit();"
                   title="<?php echo JText::_('COM_CHURCHDIRECTORY_FILTER_SEARCH_DESC'); ?>"
                   placeholder="<?php echo JText::_('COM_CHURCHDIRECTORY_FILTER_SEARCH_DESC'); ?>"/>
        </div>
		<?php endif; ?>

		<?php if ($this->params->get('show_pagination_limit')) : ?>
        <div class="btn-group pull-right">
            <label for="limit" class="element-invisible">
				<?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?>
            </label>
			<?php echo $this->pagination->getLimitBox(); ?>
        </div>
		<?php endif; ?>
    </fieldset>
	<?php endif; ?>

    <ul class="category list-striped" style="list-style: none; padding: 0;">
		<?php foreach ($this->items as $i => $item) : ?>

		<?php if (in_array($item->access, $this->user->getAuthorisedViewLevels())) : ?>
			<?php if ($this->items[$i]->state == 0) : ?>
						<li class="churchdirectory-list system-unpublished cat-list-row<?php echo $i % 2; ?>">
					<?php else: ?>
						<li class="churchdirectory-list cat-list-row<?php echo $i % 2; ?>">
					<?php endif; ?>

            <span class="pull-right">
				<?php if ($this->params->get('show_telephone_headings') AND !empty($item->telephone)) : ?>
				<?php echo JTEXT::sprintf('COM_CHURCHDIRECTORY_TELEPHONE_NUMBER', $item->telephone); ?><br/>
				<?php endif; ?>

				<?php if ($this->params->get('show_mobile_headings') AND !empty ($item->mobile)) : ?>
				<?php echo JTEXT::sprintf('COM_CHURCHDIRECTORY_MOBILE_NUMBER', $item->mobile); ?><br/>
				<?php endif; ?>

				<?php if ($this->params->get('show_fax_headings') AND !empty($item->fax)) : ?>
				<?php echo JTEXT::sprintf('COM_CHURCHDIRECTORY_FAX_NUMBER', $item->fax); ?><br/>
				<?php endif; ?>
					</span>

            <p>
				<?php if ($item->image != null) : ?>
				<?php echo JHtml::_('image', $item->image, JText::_('COM_CHURCHDIRECTORY_IMAGE_DETAILS'), array('align' => 'middle', 'height' => '100px', 'width' => '100px')); ?>
				<?php else: ?>
				<?php echo JHtml::_('image', 'media/com_churchdirectory/images/200-photo_not_available.jpg', JText::_('COM_CHURCHDIRECTORY_IMAGE_DETAILS'), array('align' => 'middle', 'height' => '100px', 'width' => '100px')); ?>
				<?php endif; ?><br/>
                <strong class="list-title">
                    <a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
						<?php echo $item->name; ?></a>
					<?php if ($this->items[$i]->published == 0): ?>
                    <span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
					<?php endif; ?>

                </strong><br/>
				<?php if ($this->params->get('show_position_headings')) : ?>

			    <?php if ($item->con_position && $this->params->get('show_position')) : ?>
                    <dl class="contact-position dl-horizontal">
                        <dt>
						    <?php if($item->con_position != '-1'): ?>
						    <?php echo JText::_('COM_CHURCHDIRECTORY_POSITION'); ?>
						    <?php endif; ?>
                        </dt>
                        <dd>
						    <?php echo $renderHelper->getPosition($item->con_position); ?>
                        </dd>
                    </dl>
				    <?php endif; ?>
				<?php endif; ?>
				<?php if ($this->params->get('show_email_headings')) : ?>
				<?php echo $item->email_to; ?>
				<?php endif; ?>
				<?php if ($this->params->get('show_suburb_headings') AND !empty($item->suburb)) : ?>
				<?php echo $item->suburb . ', '; ?>
				<?php endif; ?>

				<?php if ($this->params->get('show_state_headings') AND !empty($item->state)) : ?>
				<?php echo $item->state . ', '; ?>
				<?php endif; ?>

				<?php if ($this->params->get('show_country_headings') AND !empty($item->country)) : ?>
				<?php echo $item->country; ?><br/>
				<?php endif; ?>
            </p>
					</li>
				<?php endif; ?>
		<?php endforeach; ?>
    </ul>

	<?php if ($this->params->get('show_pagination')) : ?>
    <div class="pagination">
		<?php if ($this->params->def('show_pagination_results', 1)) : ?>
        <p class="counter">
			<?php echo $this->pagination->getPagesCounter(); ?>
        </p>
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
