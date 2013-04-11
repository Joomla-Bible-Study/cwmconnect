<?php
/**
 * Default view for category
 *
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$isModal = JFactory::getApplication()->input->get('print') == 1; // 'print=1' will only be present in the url of the modal window, not in the presentation of the page
if ($isModal)
{
	$href = '"#" onclick="window.print(); return false;"';
}
else
{
	$href = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';
	$href = "window.open(this.href,'win2','" . $href . "'); return false;";
	$href = '"index.php?option=com_churchdirectory&tmpl=component&print=1" ' . $href;
}
?>

<div class="churchdirectory-category<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->def('show_page_heading', 1))
	{
		?>
		<div class="page-header">
			<h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
			</h1>
		</div>
	<?php } ?>
	<?php if ($this->params->get('show_category_title', 1))
	{
		?>
		<h2>
			<?php echo JHtml::_('content.prepare', $this->category->title); ?>
		</h2>
	<?php } ?>
	<div class="pull-right">
		<a href=<?php echo $href; ?>>
			<?php echo JHTML::_('image.site', 'printButton.png', '/images/M_images/', null, null, JText::_('Print')); ?>
		</a>
	</div>
	<?php if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1))
	{
		?>
		<div class="category-desc">
			<?php if ($this->params->get('show_description_image') && $this->category->getParams()->get('image'))
			{
				?>
				<img src="<?php echo $this->category->getParams()->get('image'); ?>"/>
			<?php } ?>
			<?php if ($this->params->get('show_description') && $this->category->description)
			{
				?>
				<?php echo JHtml::_('content.prepare', $this->category->description); ?>
			<?php } ?>
			<div class="clr"></div>
		</div>
	<?php } ?>

	<?php echo $this->loadTemplate('items'); ?>

	<?php if (!empty($this->children[$this->category->id]) && $this->maxLevel != 0)
	{
		?>
		<div class="cat-children">
			<h3><?php echo JText::_('JGLOBAL_SUBCATEGORIES'); ?></h3>
			<?php echo $this->loadTemplate('children'); ?>
		</div>
	<?php } ?>
	<div class="clearfix"></div>
	<?php
	if ($this->params->def('show_page_birthann', 1))
	{
		echo $this->loadTemplate('birthann');
	}?>
</div>
