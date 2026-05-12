<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Churchdirectory\Administrator\View\Reports\HtmlView $this */

$user           = Factory::getApplication()->getIdentity();
$userGroups     = $user ? (array) $user->groups : [];
$reportsLevel   = (int) ($this->state?->get('reportslevel', 8) ?? 8);
$canRunReports  = \in_array($reportsLevel, $userGroups, true) || ($user && $user->authorise('core.admin'));
$token          = Session::getFormToken();
?>
<form action="<?php echo Route::_('index.php?option=com_churchdirectory&view=reports'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container">
        <?php if (!$canRunReports) : ?>
            <div class="alert alert-warning">
                <span class="icon-exclamation-triangle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('WARNING'); ?></span>
                <?php echo Text::_('COM_CHURCHDIRECTORY_REPORTS_RESTRICTED'); ?>
            </div>
        <?php else : ?>
            <div class="row">
                <div class="col-md-3">
                    <h2>Members CSV</h2>
                    <p>Download every member as a CSV file.</p>
                    <a class="btn btn-primary"
                       href="<?php echo Route::_('index.php?option=com_churchdirectory&task=reports.export&report=all&cdtype=csv&' . $token . '=1'); ?>">
                        <img src="<?php echo Uri::root() . 'media/com_churchdirectory/images/csv_file.png'; ?>"
                             alt="" style="max-height:48px"/>
                        <span>Report CSV</span>
                    </a>
                </div>
                <div class="col-md-3">
                    <h2>Google Earth KML</h2>
                    <p>Download every member as a KML file for Google Maps or Google Earth.</p>
                    <a class="btn btn-primary"
                       href="<?php echo Route::_('index.php?option=com_churchdirectory&task=reports.export&report=directory&cdtype=kml&' . $token . '=1'); ?>">KML</a>
                </div>
                <div class="col-md-3">
                    <h2>PDF</h2>
                    <p>Download every member as a PDF file.</p>
                    <a class="btn btn-primary"
                       href="<?php echo Route::_('index.php?option=com_churchdirectory&task=reports.export&report=directory&cdtype=pdf&' . $token . '=1'); ?>">PDF</a>
                </div>
                <div class="col-md-3">
                    <h2>Missing Photos</h2>
                    <p>Generate a report of members with missing photos.</p>
                    <a class="btn btn-primary"
                       href="<?php echo Route::_('index.php?option=com_churchdirectory&task=reports.export&report=missing&cdtype=missingphotos&' . $token . '=1'); ?>">Missing Photos</a>
                </div>
            </div>
        <?php endif; ?>

        <input type="hidden" name="task" value=""/>
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
