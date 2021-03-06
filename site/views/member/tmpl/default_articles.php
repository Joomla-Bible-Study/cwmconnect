<?php
/**
 * Sub view member for articles
 * @package		ChurchDirectory.Site
 * @copyright           2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
?>
<?php if ($this->params->get('show_articles')) : ?>
    <div class="churchdirectory-articles">

        <ol>
            <?php foreach ($this->item->articles as $article) : ?>
                <li>
                    <?php $link = JRoute::_('index.php?option=com_content&view=article&id=' . $article->id); ?>
                    <?php echo '<a href="' . $link . '">' ?>
                    <?php echo $article->text = htmlspecialchars($article->title, ENT_COMPAT, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
<?php endif; ?>
