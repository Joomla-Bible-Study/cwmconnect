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

use Joomla\CMS\Language\Text;

?>
<div class="btn-toolbar p-3">
    <joomla-toolbar-button task="dirheader.batch" class="ms-auto">
        <button type="submit" class="btn btn-success">
            <?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
        </button>
    </joomla-toolbar-button>
</div>
