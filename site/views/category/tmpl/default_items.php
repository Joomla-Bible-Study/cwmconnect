<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<ul class="category list-striped" style="list-style: none; padding: 0;">
	<?php foreach ($this->items as $i => $item) : ?>
		<?php if (!strstr($item->con_position, $this->params->get('teamleaders'), true))
		{
			?>
			<?php if (in_array($item->access, $this->user->getAuthorisedViewLevels())) : ?>
			<?php if ($this->items[$i]->state == 0) : ?>
				<li class="churchdirectory-list system-unpublished cat-list-row<?php echo $i % 2; ?>">
			<?php else: ?>
				<li class="churchdirectory-list cat-list-row<?php echo $i % 2; ?>">
			<?php endif; ?>

			<span class="pull-right">
				<?php if ($this->params->get('show_telephone_headings') AND !empty($item->telephone)) : ?>
					<?php echo JText::sprintf('COM_CHURCHDIRECTORY_TELEPHONE_NUMBER', $item->telephone); ?><br/>
				<?php endif; ?>

				<?php if ($this->params->get('show_mobile_headings') AND !empty ($item->mobile)) : ?>
					<?php echo JText::sprintf('COM_CHURCHDIRECTORY_MOBILE_NUMBER', $item->mobile); ?><br/>
				<?php endif; ?>

				<?php if ($this->params->get('show_fax_headings') AND !empty($item->fax)) : ?>
					<?php echo JText::sprintf('COM_CHURCHDIRECTORY_FAX_NUMBER', $item->fax); ?><br/>
				<?php endif; ?>
					</span>

			<p>
            <?php if ($this->params->get('show_image_headings')) : ?>
                <?php if ( $item->image != null) : ?>
                    <?php echo JHtml::image($item->image, JText::_('COM_CHURCHDIRECTORY_IMAGE_DETAILS'), array('align' => 'middle', 'height' => '100px', 'width' => '100px')); ?>
                <?php else: ?>
                    <?php echo JHtml::image('media/com_churchdirectory/images/200-photo_not_available.jpg', JText::_('COM_CHURCHDIRECTORY_IMAGE_DETAILS'), array('align' => 'middle', 'height' => '100px', 'width' => '100px')); ?>
                <?php endif; ?>
            <?php endif; ?>
            <br/>
			<strong class="list-title">
				<a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>">
					<?php echo $item->name; if($this->params->get('show_lname')){echo ' '.$item->lname;} ?></a>
				<?php if ($this->items[$i]->published == 0): ?>
					<span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
				<?php endif; ?>

			</strong><br/>
			<?php if ($this->params->get('show_position_headings')) : ?>

			<?php if ($item->con_position && $this->params->get('show_position')) : ?>
			<dl class="contact-position dl-horizontal">
				<dt>
					<?php if ($item->con_position != '-1'): ?>
						<?php echo JText::_('COM_CHURCHDIRECTORY_POSITION'); ?>
					<?php endif; ?>
				</dt>
				<dd>
					<?php echo $this->renderHelper->getPosition($item->con_position); ?>
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
		<?php } ?>
	<?php endforeach; ?>
</ul>
