<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$this->printed_items = (int) 0;
$this->printed_rows  = (int) 0;
$this->letter        = null;
$this->rows_per_page = (int) $this->params->get('rows_per_page');
$this->items_per_row = (int) $this->params->get('items_per_row');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
?>

<div class="directory<?php echo $this->pageclass_sfx; ?> container" style="width: 8.5in">
	<?php echo $this->pageclass_sfx;
	echo '<a name="top"></a>';
	if ($this->params->get('dr_allow_kml') && JFactory::getApplication()->input->get('format') != 'pdf')
	{
		echo '<div class="pull-right"><a href="' . JRoute::_("index.php?option=com_churchdirectory&view=directory&format=kml") .
			'" class="btn">KML</a> </div>';
	}
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
	foreach ($this->items as $s1 => $sort1)
	{
		$this->items = $sort1;
		echo $this->loadTemplate('items');
	}
	// Last call to close out table.
	echo '<a name="bottom"></a></div></div>';
	echo $this->header->footer; ?>
</div>
