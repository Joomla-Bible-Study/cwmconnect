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

use CWM\Component\Churchdirectory\Administrator\View\Cpanel\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_churchdirectory'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container">
        <div class="row">
            <div class="col-md-9">
                <p><?php echo Text::_('COM_CHURCHDIRECTORY_CPANEL_WELCOME'); ?></p>
                <?php if ($this->xml !== null) : ?>
                    <p><?php echo Text::sprintf('COM_CHURCHDIRECTORY_CPANEL_VERSION', (string) $this->xml->version); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
