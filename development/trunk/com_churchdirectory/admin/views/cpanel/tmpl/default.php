<?php
/**
 * Default view for cpanel
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
?>
<div class="fltlft">
    <p>Welcome to the new and improved Church Directory System this i a alpha release and has lot of bugs and things not completed.<br />

        All core function should be working. Directory rendering is till not fully functional and working on family unit.<br /><br />

        Thanks for supporting the work.<br /><br />

        Joomla Bible Study Team</p>
</div>
<div class="fltrt">
    <div id="cpanel" style="padding-left: 20px">
        <?php if ($this->versionName != 'ture'): echo LiveUpdate::getIcon();
        endif; ?>
    </div>
</div>
