<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Site\Helper\RenderHelper;
use CWM\Component\Churchdirectory\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

?>
<?php if ($this->params->get('show_articles')) : ?>
    <div class="churchdirectory-articles">

        <ol>
            <?php foreach ($this->item->articles as $article) : ?>
                <li>
                    <?php $link = Route::_('index.php?option=com_content&view=article&id=' . $article->id); ?>
                    <?php echo '<a href="' . $link . '">' ?>
                    <?php echo $article->text = htmlspecialchars($article->title, ENT_COMPAT, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
<?php endif; ?>
