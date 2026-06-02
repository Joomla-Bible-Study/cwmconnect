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

use CWM\Component\Cwmconnect\Administrator\View\Cpanel\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container">
        <?php if ($this->schemaFindings) : ?>
            <div class="alert alert-warning" role="alert">
                <h4 class="alert-heading"><?php echo Text::_('COM_CWMCONNECT_CPANEL_SCHEMA_FINDINGS_HEADING'); ?></h4>
                <p class="mb-2"><?php echo Text::_('COM_CWMCONNECT_CPANEL_SCHEMA_FINDINGS_BODY'); ?></p>
                <a class="btn btn-warning"
                   href="<?php echo Route::_('index.php?option=com_installer&view=database'); ?>">
                    <?php echo Text::_('COM_CWMCONNECT_CPANEL_SCHEMA_FINDINGS_LINK'); ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-9">
                <p><?php echo Text::_('COM_CWMCONNECT_CPANEL_WELCOME'); ?></p>
                <?php if ($this->xml !== null) : ?>
                    <p><?php echo Text::sprintf('COM_CWMCONNECT_CPANEL_VERSION', (string) $this->xml->version); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($this->pcEnabled) : ?>
            <div class="row mt-4">
                <div class="col-md-9">
                    <div class="card" id="pc-sync-card">
                        <div class="card-header">
                            <h3 class="card-title mb-0"><?php echo Text::_('COM_CWMCONNECT_PC_CARD_TITLE'); ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                <?php echo Text::_('COM_CWMCONNECT_PC_CARD_INTRO'); ?>
                            </p>

                            <div class="btn-group" role="group" aria-label="Planning Center actions">
                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        data-pc-action="test">
                                    <span class="icon-link" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_CWMCONNECT_PC_BTN_TEST'); ?>
                                </button>
                                <button type="button"
                                        class="btn btn-primary"
                                        data-pc-action="sync">
                                    <span class="icon-refresh" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_CWMCONNECT_PC_BTN_SYNC'); ?>
                                </button>
                                <a class="btn btn-outline-secondary"
                                   href="<?php echo \Joomla\CMS\Router\Route::_('index.php?option=com_cwmconnect&view=pcmappings'); ?>">
                                    <span class="icon-list" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_CWMCONNECT_PC_BTN_MAPPINGS'); ?>
                                </a>
                                <a class="btn btn-outline-secondary"
                                   href="<?php echo \Joomla\CMS\Router\Route::_('index.php?option=com_cwmconnect&view=reconcile'); ?>">
                                    <span class="icon-users" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_CWMCONNECT_PC_BTN_RECONCILE'); ?>
                                </a>
                            </div>

                            <div class="mt-3" id="pc-sync-status" role="status" aria-live="polite"></div>
                            <div class="mt-2" id="pc-sync-result"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</form>
