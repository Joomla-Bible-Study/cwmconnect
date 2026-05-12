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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Connect\Administrator\View\Geoupdate\HtmlView $this */
?>
<?php if ($this->more) : ?>
    <h1><?php echo Text::_('COM_CWMCONNECT_LBL_GEOUPDATEINPROGRESS'); ?></h1>
<?php else : ?>
    <h1><?php echo Text::_('COM_CWMCONNECT_LBL_GEOUPDATEDONE'); ?></h1>
<?php endif; ?>

<div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"
     aria-valuenow="<?php echo (int) $this->percentage; ?>">
    <div class="progress-bar progress-bar-striped progress-bar-animated"
         style="width: <?php echo (int) $this->percentage; ?>%"></div>
</div>

<form action="index.php" name="adminForm" id="adminForm" method="get">
    <input type="hidden" name="option" value="com_cwmconnect"/>
    <input type="hidden" name="view" value="geoupdate"/>
    <?php if ($this->state === 'start') : ?>
        <input type="hidden" name="task" value="geoupdate.browse"/>
    <?php else : ?>
        <input type="hidden" name="task" value="geoupdate.run"/>
    <?php endif; ?>
    <input type="hidden" name="tmpl" value="component"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php if (!$this->more) : ?>
    <div class="alert alert-info">
        <p><?php echo Text::_('COM_CWMCONNECT_LBL_AUTOCLOSE_IN_3S'); ?></p>
    </div>
    <script>
        window.setTimeout(function () {
            if (window.parent && window.parent.document) {
                window.parent.document.location = 'index.php?option=com_cwmconnect&view=geostatus';
            }
        }, 3000);
    </script>
<?php endif; ?>
