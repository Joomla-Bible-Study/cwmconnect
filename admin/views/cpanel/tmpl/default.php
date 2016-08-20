<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.modal');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.multiselect');
?>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory'); ?>" method="post" name="adminForm"
      id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>
			<!-- Begin Content -->
			<div class="pull-left span8">
				<p>Welcome to the new Church Directory System this is a bata release it is not completed.<br/>
				   All core function should be working. Directory rendering is till not fully functional. You can render
				   KML and csv reprots on the church directory for backup.<br/><br/>
				   Thanks for supporting the work.<br/><br/>
				   Joomla Bible Study Team</p>
				<p>Version: <?php echo $this->xml->version; ?></p>
			</div>
			<div class="pull-right span4">
				<div id="cpanel" class="btn-group">
					<a href="index.php?option=com_churchdirectory&view=geoupdate&tmpl=component" class="modal btn"
					   rel="{handler: 'iframe', size: {x: 600, y: 250}}">
						<img src="<?php echo rtrim(JURI::base(), '/'); ?>/../media/com_churchdirectory/images/icons/icon-32-geoupdate.png"
						     border="0" alt="<?php echo JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE') ?>" width="32"
						     height="32" align='middle' style="float: none"/>
						<span>
						<?php echo JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE') ?><br/>
					</span>
					</a>
				</div>
			</div>
			<!-- End Content -->
		</div>
</form>
