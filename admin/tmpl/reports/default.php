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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Cwmconnect\Administrator\View\Reports\HtmlView $this */

$app            = Factory::getApplication();
$user           = $app->getIdentity();
$userGroups     = $user ? (array) $user->groups : [];
$reportsLevel   = (int) ($this->state?->get('reportslevel', 8) ?? 8);
$canRunReports  = \in_array($reportsLevel, $userGroups, true) || ($user && $user->authorise('core.admin'));
$isAdmin        = $user && $user->authorise('core.admin');
$token          = Session::getFormToken();
$pdfPath        = (string) $app->getUserState('com_cwmconnect.reports.pdf_path', '');

if ($pdfPath !== '') {
    $app->setUserState('com_cwmconnect.reports.pdf_path', null);
}
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=reports'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container">
        <?php if (!$canRunReports) : ?>
            <div class="alert alert-warning">
                <span class="icon-exclamation-triangle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('WARNING'); ?></span>
                <?php echo Text::_('COM_CWMCONNECT_REPORTS_RESTRICTED'); ?>
            </div>
        <?php else : ?>
            <div class="row">
                <div class="col-md-3">
                    <h2>Members CSV</h2>
                    <p>Download every member as a CSV file.</p>
                    <a class="btn btn-primary"
                       href="<?php echo Route::_('index.php?option=com_cwmconnect&task=reports.export&report=all&cdtype=csv&' . $token . '=1'); ?>">
                        <img src="<?php echo Uri::root() . 'media/com_cwmconnect/images/csv_file.png'; ?>"
                             alt="" style="max-height:48px"/>
                        <span>Report CSV</span>
                    </a>
                </div>
                <div class="col-md-3">
                    <h2>Google Earth KML</h2>
                    <p>Download every member as a KML file for Google Maps or Google Earth.</p>
                    <a class="btn btn-primary"
                       href="<?php echo Route::_('index.php?option=com_cwmconnect&task=reports.export&report=directory&cdtype=kml&' . $token . '=1'); ?>">KML</a>
                </div>
                <div class="col-md-3">
                    <h2><?php echo Text::_('COM_CWMCONNECT_REPORTS_PRINT_DIRECTORY'); ?></h2>
                    <p><?php echo Text::_('COM_CWMCONNECT_REPORTS_PRINT_DIRECTORY_DESC'); ?></p>
                    <?php if ($isAdmin) : ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="includeHidden" name="include_hidden" value="1">
                            <label class="form-check-label" for="includeHidden">
                                <?php echo Text::_('COM_CWMCONNECT_REPORTS_INCLUDE_HIDDEN'); ?>
                            </label>
                        </div>
                    <?php endif; ?>
                    <a class="btn btn-primary" id="pdfExportBtn"
                       href="<?php echo Route::_('index.php?option=com_cwmconnect&task=reports.export&report=directory&cdtype=pdf&' . $token . '=1'); ?>">
                        <span class="icon-file-pdf" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_REPORTS_GENERATE_PDF'); ?>
                    </a>
                    <?php if ($pdfPath !== '') : ?>
                        <div class="alert alert-success mt-2">
                            <a href="<?php echo Uri::root() . $this->escape($pdfPath); ?>" target="_blank" rel="noopener">
                                <span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_REPORTS_DOWNLOAD_PDF'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <h2>Missing Photos</h2>
                    <p>Generate a report of members with missing photos.</p>
                    <a class="btn btn-primary"
                       href="<?php echo Route::_('index.php?option=com_cwmconnect&task=reports.export&report=missing&cdtype=missingphotos&' . $token . '=1'); ?>">Missing Photos</a>
                </div>
            </div>
        <?php endif; ?>

        <input type="hidden" name="task" value=""/>
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
<?php if ($isAdmin) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('pdfExportBtn');
    var cb  = document.getElementById('includeHidden');
    if (!btn || !cb) return;
    var base = btn.href;
    cb.addEventListener('change', function () {
        btn.href = cb.checked ? base + '&include_hidden=1' : base;
    });
});
</script>
<?php endif; ?>
