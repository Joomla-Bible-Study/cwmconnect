<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Connect\Administrator\View\Info\HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=info'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container">
        <p>A Church Directory manager component. Displays a list of members and member details pages with various
            information and a mail-to form.</p>

        <h2>Online Documentation</h2>
        <p><a href="https://www.christianwebministries.org">English</a></p>

        <h2>Support ChurchDirectory</h2>
        <p>If you like this component please rate it at the Joomla Extensions Directory.</p>

        <h2>Need help?</h2>
        <p>Feel free to ask a question in our support forum.</p>

        <h2>Copyright</h2>
        <p>ChurchDirectory is free software released under the
            <a href="http://www.gnu.org/copyleft/gpl.html">GNU/GPL License v3</a>.</p>
        <p>Some parts of ChurchDirectory are derived from Joomla! contact component (com_contact). Copyright
            (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.</p>
    </div>
</form>
