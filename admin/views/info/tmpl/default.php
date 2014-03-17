<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
?>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=info'); ?>" method="post" name="adminForm"
      id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
    </div>
        <div id="j-main-container" class="span10">
        <?php else : ?>
            <div id="j-main-container">
            <?php endif; ?>
    <p>A Church Directory manager component. Displays a list of members and member details pages with various
        informations and a mail-to form.</p>

    <h2>Online Documentation</h2>

    <p><a href="#">English</a></p>

    <h2>Support ChurchDirectory</h2>

    <p>If you like this component please rate it at the Joomla Extensions Directory. <a href="#">Rate here</a></p>

    <h2>Need help?</h2>

    <p>Feel free to ask a question in our <a href="#">support forum</a>.</p>

    <h2>Copyright</h2>

    <p>ChurchDirectory is free software released under the <a href="http://www.gnu.org/copyleft/gpl.html">GNU/GPL
        License v3</a>.</p>

    <p>Some parts of ChurchDirectory are derived from Joomla! contact component (com_contact). Copyright
        (C) 2005 - 2013 Joomla Bible Studys, Inc. All rights reserved. </p>
</div>
</form>

