<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Directory\HtmlView $this */
?>
<div class="directory container">
    <?php echo Text::_('COM_CWMCONNECT_DIRECTORY_LANDING'); ?>

    <div class="directory-links">
        <?php if ($this->params->get('dr_allow_kml')) : ?>
            <div class="directory-link float-start" style="padding-right: 10px">
                <a href="<?php echo Route::_('index.php?option=com_cwmconnect&view=directory&format=kml'); ?>" class="btn btn-secondary">
                    KML
                </a>
            </div>
        <?php endif; ?>

        <div class="directory-link float-start">
            <a href="<?php echo Route::_('index.php?option=com_cwmconnect&view=directory&format=pdf'); ?>" class="btn btn-secondary">
                PDF
            </a>
        </div>
    </div>
</div>
