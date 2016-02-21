<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$user = JFactory::getUser();

?>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=reports'); ?>" method="post"
      name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif;
			if (!in_array('8', $user->groups))
				{
					JFactory::getApplication()->enqueueMessage('Only Super Admin can access reports', 'worning');
				}
				else
				{
				?>
					<h2>Members Reports</h2>
					<p>The fallowing button will output all Members in a CSV file.</p>

					<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&task=reports.export&report=all'); ?>">
						<img src="<?php echo JRoute::_(JUri::root() . 'media/com_churchdirectory/images/csv_file.png'); ?>" alt="" />
						<span class="btn btn-default">Report CSV</span>
					</a>
				<?php
				}
			?>
			<input type="hidden" name="task" value=""/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>

