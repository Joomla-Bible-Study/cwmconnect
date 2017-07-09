<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('jquery.framework');

?>
<?php echo $this->renderHelper->getSearchField($this->params); ?>
<div class="directory container">
	<h1>Search Results</h1>
	<?php if (!empty($this->items)) : ?>
	<table class="table table-striped">
		<thead class="thead-default">
		<tr>
			<th>#</th>
			<th>Member</th>
			<th>Profile Link</th>
		</tr>
		</thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
			<tr>
				<th role="row"><?php echo $i + 1; ?></th>
				<td><?php echo $item->name; ?></td>
				<td><a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catid)); ?>"
				       class"btn btn-info">View
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
	<p>No Resultes</p>
	<?php endif; ?>
</div>
