<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$printed_items = 0;
$printed_rows = 0;
$heading = null;
$letter = null;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
JLoader::register('DirectoryHeaderHelper', JPATH_SITE . '/components/com_churchdirectory/helpers/directoryheader.php');


$items = RenderHelper::groupit(array('items' => $this->items, 'field' => 'category_title'));

foreach ($items as $s1 => $sort1)
{
	$new_rows1[$s1] = RenderHelper::groupit(array('items' => $items[$s1], 'field' => 'suburb'));

}?>

<div class="directory<?php echo $this->pageclass_sfx; ?> row-fluid">
	<?php echo $this->pageclass_sfx; ?>
	<?php if ($this->params->get('dr_show_page_title', 1))
	{
		?>
		<h1>
			<?php echo $this->escape($this->params->get('page_heading')); ?>
		</h1>
	<?php } ?>
	<?php if ($this->params->get('dr_show_description'))
	{
		?>
		<?php // If there is a description in the menu parameters use that; ?>
		<?php if ($this->params->get('categories_description'))
	{
		?>
		<div class="category-desc base-desc">
			<?php echo JHtml::_('content.prepare', $this->params->get('categories_description')); ?>
		</div>
	<?php } ?>
	<?php } ?>
	<?php echo DirectoryHeaderHelper::getHeader($params = $this->params); ?>
	<?php
	foreach ($new_rows1 as $s1 => $sort1)
	{
		//if ($letter != ucfirst($sort1->name[0]))
		//{
			//$letter = ucfirst($item->name[0]);
			// @FIXME still need to fix this error. It is not working quite right yet.
			//echo '<div class="clearfix"></div><hr/><a name="' . $letter . '"></a><h2>' . $letter . '</h2>';
		//}
		?>
		<?php
		// First Sort Section
		foreach ($sort1 as $s2 => $sort2)
		{
			?>
			<?php $this->sort2 = $sort2;
			echo $this->loadTemplate('items');
			?>
		<?php
		}
	}?>
</div>
