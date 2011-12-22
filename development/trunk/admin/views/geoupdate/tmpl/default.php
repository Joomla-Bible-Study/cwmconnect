<?php

// Protect from unauthorized access
defined('_JEXEC') or die;

$option = JRequest::getCmd('option','com_churchdirectory');
?>

<?php if(!empty($this->table)): ?>
<h1><?php echo JText::_('ATOOLS_LBL_OPTIMIZEINPROGRESS'); ?></h1>
<?php else: ?>
<h1><?php echo JText::_('ATOOLS_LBL_OPTIMIZECOMPLETE'); ?></h1>
<?php endif; ?>

<div id="progressbar-outer">
	<div id="progressbar-inner"></div>
</div>

<?php if(!empty($this->table)): ?>
<form action="index.php" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_churchdirectory" />
	<input type="hidden" name="view" value="geoupdate" />
	<input type="hidden" name="task" value="update" />
	<input type="hidden" name="from" value="<?php echo $this->table ?>" />
	<input type="hidden" name="tmpl" value="component" />
</form>
<?php endif; ?>

<?php if($this->percent == 100): ?>
<div class="disclaimer">
	<h3><?php echo JText::_('ATOOLS_LBL_AUTOCLOSE_IN_3S'); ?></h3>
</div>
<?php endif; ?>
