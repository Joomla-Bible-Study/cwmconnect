<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die;
?>
<div class="directory<?php echo $this->pageclass_sfx; ?> container" style="width: 8.5in">
	<?php echo $this->pageclass_sfx;
	echo '<a name="top"></a>';
	if ($this->params->get('dr_show_page_title', 1))
	{
		?>
		<h1>
			<?php echo $this->escape($this->params->get('page_heading')); ?>
		</h1>
		<?php
	}
	if ($this->params->get('dr_show_description'))
	{
		// If there is a description in the menu parameters use that;
		if ($this->params->get('categories_description'))
		{
			?>
			<div class="category-desc base-desc">
				<?php echo JHtml::_('content.prepare', $this->params->get('categories_description')); ?>
			</div>
			<?php
		}
	}
	echo $this->header->header;
	echo '<div class="clearfix"></div>';
	echo '<div class="center">' . $this->abclinks() . '</div>';
	echo '<hr />';
	?>
</div>
