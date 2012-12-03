<?php
/**
 * Default view for directory
 * @package		ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
JLoader::register('DirectoryHeaderHelper', JPATH_SITE . '/components/com_churchdirectory/helpers/directoryheader.php');
?>
<div class="directory<?php echo $this->pageclass_sfx; ?>">
    <div>
        <?php echo $this->pageclass_sfx; ?>
        <?php if ($this->params->get('dr_show_page_title', 1)) : ?>
            <h1>
                <?php echo $this->escape($this->params->get('page_heading')); ?>
            </h1>
        <?php endif; ?>
        <?php if ($this->params->get('dr_show_description')) : ?>
            <?php //If there is a description in the menu parameters use that; ?>
            <?php if ($this->params->get('categories_description')) : ?>
                <div class="category-desc base-desc">
                    <?php echo JHtml::_('content.prepare', $this->params->get('categories_description')); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($this->params->get('dr_allow_kml')) : ?>
            <?php echo JText::_('COM_CHURCHDIRECTORY_DOWNLOAD_INFORMATION_AS'); ?>
            <a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&amp;view=directory&amp;format=kml'); ?>">
                <?php echo JText::_('COM_CHURCHDIRECTORY_KMLFILE'); ?></a>
            <?php endif; ?>
        <?php
        echo DirectoryHeaderHelper::getHeader($params = $this->params);
        ?>
    </div>
    <?php
    echo $this->loadTemplate('items');
    ?>
</div>