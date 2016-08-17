<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

JHtml::_('behavior.framework');
JHtml::_('behavior.modal');
?>
<?php if ($this->more): ?>
	<h1><?php echo JText::_('COM_CHURCHDIRECTORY_LBL_GEOUPDATEINPROGRESS'); ?></h1>
<?php else: ?>
	<h1><?php echo JText::_('COM_CHURCHDIRECTORY_LBL_GEOUPDATEDONE'); ?></h1>
<?php endif; ?>

<script type="text/javascript" language="javascript">
	if (typeof jQuery == 'function') {
		if (typeof jQuery.ui == 'object') {
			jQuery('#nojquerywarning').css('display', 'none');
		}
	}
</script>


<div class="progress progress-striped active">
	<div class="bar" style="width: <?php echo $this->percentage ?>%"></div>
</div>

<form action="index.php" name="adminForm" id="adminForm" method="get">
	<input type="hidden" name="option" value="com_churchdirectory"/>
	<input type="hidden" name="view" value="geoupdate"/>
	<?php if ($this->state === 'start')
	{ ?>
		<input type="hidden" name="task" value="geoupdate.browse"/>
	<?php }
	else
	{
		?>
		<input type="hidden" name="task" value="geoupdate.run"/>
	<?php } ?>
	<input type="hidden" name="tmpl" value="component"/>
	<?php echo JHtml::_('form.token'); ?>
</form>

<?php if (!$this->more): ?>
	<div class="alert alert-info">
		<p><?php echo JText::_('COM_CHURCHDIRECTORY_LBL_AUTOCLOSE_IN_3S'); ?></p>
	</div>
	<script type="text/javascript">
		window.setTimeout('closeme();', 3000);
		function closeme() {
			parent.SqueezeBox.close();
		}
	</script>
<?php endif; ?>
