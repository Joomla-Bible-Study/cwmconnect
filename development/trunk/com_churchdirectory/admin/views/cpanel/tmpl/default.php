<?php
/**
 * Default view for cpanel
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
if (CHURCHDIRECTORY_CHECKREL === TRUE):
    JHtml::_('jquerybehavior.tooltip');
    JHtml::_('dropdown.init');
else :
    JHtml::_('behavior.tooltip');
endif;
JHtml::_('behavior.multiselect');
?>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row-fluid">
        <?php if ($this->versionName === TRUE): ?>
            <!-- Begin Sidebar -->
            <div id="sidebar" class="span2">
                <div class="sidebar-nav">
                    <?php
                    // Display the submenu position modules
                    $this->submenumodules = JModuleHelper::getModules('submenu');
                    foreach ($this->submenumodules as $submenumodule) {
                        $output = JModuleHelper::renderModule($submenumodule);
                        $params = new JRegistry;
                        $params->loadString($submenumodule->params);
                        echo $output;
                    }
                    ?>
                </div>
            </div>
            <!-- End Sidebar -->
        <?php endif; ?>
        <!-- Begin Content -->
        <div class="span10">
            <div class="fltlft">
                <p>Welcome to the new and improved Church Directory System this i a alpha release and has lot of bugs and things not completed.<br />

                    All core function should be working. Directory rendering is till not fully functional and working on family unit.<br /><br />

                    Thanks for supporting the work.<br /><br />

                    Joomla Bible Study Team</p>
            </div>
            <div class="fltrt">
                <div id="cpanel" style="padding-left: 20px">
                    <?php
                    if ($this->versionName != TRUE): echo LiveUpdate::getIcon();
                    endif;
                    ?>
                </div>
            </div>
        </div>
        <!-- End Content -->
    </div>
</form>
