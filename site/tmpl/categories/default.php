<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \CWM\Component\Churchdirectory\Site\View\Categories\HtmlView $this */

Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('bootstrap.collapse');
?>
<div class="categories-list<?php echo $this->pageclass_sfx; ?>">
    <?php
    echo LayoutHelper::render('joomla.content.categories_default', $this);
    echo $this->loadTemplate('items');
    ?>
</div>
