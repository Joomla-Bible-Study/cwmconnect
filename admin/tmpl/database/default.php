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

/** @var \CWM\Component\Connect\Administrator\View\Database\HtmlView $this */

$activeTab = $this->errorCount === 0 ? 'other' : 'problems';
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=database'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container">
        <?php if ($this->errorCount === 0) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_INSTALLER_MSG_DATABASE_OK'); ?>
            </div>
        <?php else : ?>
            <div class="alert alert-danger">
                <span class="icon-exclamation-circle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('ERROR'); ?></span>
                <?php echo Text::_('COM_INSTALLER_MSG_DATABASE_ERRORS'); ?>
            </div>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.startTabSet', 'databaseTab', ['active' => $activeTab]); ?>

        <?php if ($this->errorCount > 0) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'databaseTab', 'problems', Text::plural('COM_INSTALLER_MSG_N_DATABASE_ERROR_PANEL', $this->errorCount)); ?>
            <fieldset class="options-form">
                <legend><?php echo Text::_('COM_INSTALLER_MSG_N_DATABASE_ERROR_PANEL'); ?></legend>
                <ul>
                    <?php if (!$this->filterParams) : ?>
                        <li><?php echo Text::_('COM_INSTALLER_MSG_DATABASE_FILTER_ERROR'); ?></li>
                    <?php endif; ?>

                    <?php if (strncmp($this->schemaVersion, $this->manifestVersion, 5) !== 0) : ?>
                        <li><?php echo Text::sprintf('COM_CWMCONNECT_DATABASE_SCHEMA_DOES_NOT_MATCH', $this->schemaVersion, $this->manifestVersion); ?></li>
                    <?php endif; ?>

                    <?php if ($this->updateVersion !== $this->manifestVersion) : ?>
                        <li><?php echo Text::sprintf('COM_INSTALLER_MSG_DATABASE_UPDATEVERSION_ERROR', $this->updateVersion, $this->manifestVersion); ?></li>
                    <?php endif; ?>

                    <?php foreach ($this->errors as $error) :
                        $key  = 'COM_INSTALLER_MSG_DATABASE_' . $error->queryType;
                        $msgs = $error->msgElements ?? [];
                        $file = basename((string) ($error->file ?? ''));
                        $msg0 = $msgs[0] ?? ' ';
                        $msg1 = $msgs[1] ?? ' ';
                        $msg2 = $msgs[2] ?? ' ';
                        ?>
                        <li><?php echo Text::sprintf($key, $file, $msg0, $msg1, $msg2); ?></li>
                    <?php endforeach; ?>
                </ul>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'databaseTab', 'other', Text::_('COM_INSTALLER_MSG_DATABASE_INFO')); ?>
        <fieldset class="options-form">
            <legend><?php echo Text::_('COM_INSTALLER_MSG_DATABASE_INFO'); ?></legend>
            <ul>
                <li><?php echo Text::sprintf('COM_INSTALLER_MSG_DATABASE_SCHEMA_VERSION', $this->schemaVersion); ?></li>
                <li><?php echo Text::sprintf('COM_INSTALLER_MSG_DATABASE_UPDATE_VERSION', $this->updateVersion); ?></li>
                <li><?php echo Text::sprintf('COM_INSTALLER_MSG_DATABASE_DRIVER', Factory::getDbo()->name); ?></li>
                <li><?php echo Text::sprintf('COM_INSTALLER_MSG_DATABASE_CHECKED_OK', \count($this->results['ok'] ?? [])); ?></li>
                <li><?php echo Text::sprintf('COM_INSTALLER_MSG_DATABASE_SKIPPED', \count($this->results['skipped'] ?? [])); ?></li>
            </ul>
        </fieldset>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

        <input type="hidden" name="task" value="database.fix"/>
        <input type="hidden" name="boxchecked" value="0"/>
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
