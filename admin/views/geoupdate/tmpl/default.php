<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();


JHTML::_('behavior.framework');
JHtml::_('behavior.modal');
?>
<?php if ($this->more): ?>
<h1><?php echo JText::_('COM_CHURCHDIRECTORY_LBL_GEOUPDATEINPROGRESS'); ?></h1>
<?php else: ?>
<h1><?php echo JText::_('COM_CHURCHDIRECTORY_LBL_GEOUPDATEDONE'); ?></h1>
<?php endif; ?>


<div class="progress progress-striped active">
    <div class="bar" style="width: <?php echo $this->percentage ?>%"></div>
</div>

<form action="index.php" name="adminForm" id="adminForm">
    <input type="hidden" name="option" value="com_churchdirectory"/>
    <input type="hidden" name="view" value="geoupdate"/>
    <input type="hidden" name="task" value="run"/>
    <input type="hidden" name="tmpl" value="component"/>
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
