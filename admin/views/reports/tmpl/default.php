<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

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
			if (!in_array($this->state->get('reportslevel', '8'), $user->groups))
			{
				JFactory::getApplication()->enqueueMessage('Only Super Admin can access reports', 'warning');
			}
			else
			{
				?>
				<div class="span12">
					<div class="span3">
						<h2>Members Reports</h2>
						<p>The fallowing button will output all Members in a CSV file.</p>

						<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&task=reports.export&report=all&cdtype=csv'); ?>">
							<img
									src="<?php echo JRoute::_(JUri::root() . 'media/com_churchdirectory/images/csv_file.png'); ?>"
									alt=""/>
							<span class="btn btn-default">Report CSV</span>
						</a>
					</div>
					<div class="span3">
						<h2>Google Earth KML</h2>
						<p>The fallowing button will output all Members in a KML file to use with Google maps or Google
							Earth.</p><a
								href="<?php echo JRoute::_("index.php?option=com_churchdirectory&view=reports&format=row&cdtype=kml&" . JSession::getFormToken() . "=1") ?>"
								class="btn btn-default">KML</a>
					</div>
					<div class="span3">
						<h2>PDF</h2>
						<p>The fallowing button will output all Members in a pdf file
							Earth.</p><a
								href="<?php echo JRoute::_("index.php?option=com_churchdirectory&view=reports&format=row&cdtype=pdf&" . JSession::getFormToken() . "=1") ?>"
								class="btn btn-default">PDF</a>
					</div>
				</div>
			<div class="span12">
					<div class="span3">
						<h2>Image Missing</h2>
						<p>Genrate a report of missing mimber photos</p><a
								href="<?php echo JRoute::_("index.php?option=com_churchdirectory&view=reports&format=row&cdtype=missingphotos&" . JSession::getFormToken() . "=1") ?>"
								class="btn btn-default">Missing Photos</a>
					</div>
				</div>
				<?php
			}
			?>
			<input type="hidden" name="task" value=""/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>

