<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$this->printed_items = (int) 0;
$this->printed_rows = (int) 0;
$this->letter = null;
$this->rows_per_page = (int) $this->params->get('rows_per_page');
$this->items_per_row = (int) $this->params->get('items_per_row');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
JLoader::register('DirectoryHeaderHelper', JPATH_SITE . '/components/com_churchdirectory/helpers/directoryheader.php');
?>

<div class="directory<?php echo $this->pageclass_sfx; ?> row-fluid">
	<?php echo $this->pageclass_sfx;

	if ($this->params->get('dr_show_page_title', 1))
	{
		?>
		<h1>
			<?php echo $this->escape($this->params->get('page_heading')); ?>
		</h1>
	<?php
	}
	?>
	<?php
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
	echo DirectoryHeaderHelper::getHeader($params = $this->params);
	echo '<div class="clearfix"></div><hr />';

	foreach ($this->items as $s1 => $sort1)
	{
		if (0)
		{
			// First Sort Section
			foreach ($sort1 as $s2 => $sort2)
			{
				?>
				<?php $this->items = $sort2;
				echo $this->loadTemplate('items');
				?>
			<?php
			}
		}
		else
		{
			$this->items = $sort1;
			echo $this->loadTemplate('items');
		}
	}
	// Last call to close out table.
	echo '</td></tr></tbody></table>'; ?>
</div>
