<?php
/**
 * Default view for cpanel
 *
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
$version = version_compare(JVERSION, '3.0', 'ge');
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
if ($version):
	JHtml::_('bootstrap.tooltip');
	JHtml::_('dropdown.init');
	JHtml::_('formbehavior.chosen', 'select');
else :
	JHtml::_('behavior.tooltip');
endif;
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
    <div class="fltlft pull-left">
        <p>Welcome to the new and improved Church Directory System this i a alpha release and has lot of bugs
            and things not completed.<br/>

            All core function should be working. Directory rendering is till not fully functional and working on
            family unit.<br/><br/>

            Thanks for supporting the work.<br/><br/>

            Joomla Bible Study Team</p>
    </div>
    <div class="fltrt pull-right span1">
        <div id="cpanel">
			<?php
			if (!$version)
			{
				echo LiveUpdate::getIcon();
			}
			?>
        </div>
    </div>

    <div style="float:<?php echo ($lang->isRTL()) ? 'right' : 'left'; ?>;">
        <div class="icon">
            <a href="index.php?option=com_churchdirectory&view=geoupdate&tmpl=component" class="modal"
               rel="{handler: 'iframe', size: {x: 600, y: 250}}">
                <img
                        src="<?php echo rtrim(JURI::base(), '/'); ?>/../media/com_admintools/images/cleantmp-32.png"
                        border="0" alt="<?php echo JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE') ?>"/>
					<span>
						<?php echo JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE') ?><br/>
					</span>
            </a>
        </div>
    </div>
</div>
    <!-- End Content -->
</div>
</form>
