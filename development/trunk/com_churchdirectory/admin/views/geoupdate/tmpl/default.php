<?php
/**
 * ChurchDirectory Contact manager component for Joomla! 1.7
 *
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('_JEXEC') or die;

$option = JRequest::getCmd('option', 'com_churchdirectory');
?>

<?php if (!empty($this->table)): ?>
    <h1><?php echo JText::_('CHURCHDIRECTORY_LBL_GEOUPDATEINPROGRESS'); ?></h1>
<?php else: ?>
    <h1><?php echo JText::_('CHURCHDIRECTORY_LBL_GEOUPDATECOMPLETE'); ?></h1>
<?php endif; ?>

<div id="progressbar-outer">
    <div id="progressbar-inner"></div>
</div>

<?php if (!empty($this->table)): ?>
    <form action="index.php" name="adminForm" id="adminForm">
        <input type="hidden" name="option" value="com_churchdirectory" />
        <input type="hidden" name="view" value="geoupdate" />
        <input type="hidden" name="task" value="update" />
        <input type="hidden" name="from" value="<?php echo $this->table ?>" />
        <input type="hidden" name="tmpl" value="component" />
    </form>
<?php endif; ?>

<?php if ($this->percent == 100): ?>
    <div class="disclaimer">
        <h3><?php echo JText::_('CHURCHDIRECTORY_LBL_AUTOCLOSE_IN_3S'); ?></h3>
    </div>
<?php endif; ?>
