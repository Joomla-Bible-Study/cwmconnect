<?php
/**
 * @version             $Id: default.php 71 $
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

$cparams = JComponentHelper::getParams('com_media');
?>
<div class="churchdirectory<?php echo $this->pageclass_sfx ?>">
    <?php if ($this->params->get('show_page_heading', 1)) : ?>
        <h1>
            <?php echo $this->escape($this->params->get('page_heading')); ?>
        </h1>
    <?php endif; ?>
    <?php if ($this->churchdirectory->name && $this->params->get('show_name')) : ?>
        <h2>
            <span class="churchdirectory-name"><?php echo $this->churchdirectory->name; ?></span>
        </h2>
    <?php endif; ?>
    <?php if ($this->params->get('show_churchdirectory_category') == 'show_no_link') : ?>
        <h3>
            <span class="churchdirectory-category"><?php echo $this->churchdirectory->category_title; ?></span>
        </h3>
    <?php endif; ?>
    <?php if ($this->params->get('show_churchdirectory_category') == 'show_with_link') : ?>
        <?php $churchdirectoryLink = ChurchDirectoryHelperRoute::getCategoryRoute($this->churchdirectory->catid); ?>
        <h3>
            <span class="churchdirectory-category"><a href="<?php echo $churchdirectoryLink; ?>">
                    <?php echo $this->escape($this->churchdirectory->category_title); ?></a>
            </span>
        </h3>
    <?php endif; ?>
    <?php if ($this->params->get('show_churchdirectory_list') && count($this->churchdirectories) > 1) : ?>
        <form action="#" method="get" name="selectForm" id="selectForm">
            <?php echo JText::_('COM_CHURCHDIRECTORY_SELECT_CONTACT'); ?>
            <?php echo JHtml::_('select.genericlist', $this->churchdirectories, 'id', 'class="inputbox" onchange="document.location.href = this.value"', 'link', 'name', $this->churchdirectory->link); ?>
        </form>
    <?php endif; ?>
    <?php if ($this->params->get('presentation_style') != 'plain') { ?>
        <?php echo JHtml::_($this->params->get('presentation_style') . '.start', 'churchdirectory-slider'); ?>
        <?php echo JHtml::_($this->params->get('presentation_style') . '.panel', JText::_('COM_CHURCHDIRECTORY_DETAILS'), 'basic-details');
    } ?>
    <?php if ($this->params->get('presentation_style') == 'plain'): ?>
        <?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_DETAILS') . '</h3>'; ?>
    <?php endif; ?>
    <?php if ($this->churchdirectory->image && $this->params->get('show_image')) : ?>
        <div class="churchdirectory-image">
            <?php echo JHtml::_('image', $this->churchdirectory->image, JText::_('COM_CHURCHDIRECTORY_IMAGE_DETAILS'), array('align' => 'middle')); ?>
        </div>
    <?php endif; ?>

    <?php if ($this->churchdirectory->con_position && $this->params->get('show_position')) : ?>
        <p class="churchdirectory-position"><?php echo $this->churchdirectory->con_position; ?></p>
    <?php endif; ?>

    <?php echo $this->loadTemplate('address'); ?>

    <?php if ($this->params->get('allow_vcard')) : ?>
        <?php echo JText::_('COM_CHURCHDIRECTORY_DOWNLOAD_INFORMATION_AS'); ?>
        <a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&amp;view=churchdirectory&amp;id=' . $this->churchdirectory->id . '&amp;format=vcf'); ?>">
            <?php echo JText::_('COM_CHURCHDIRECTORY_VCARD'); ?></a>
    <?php endif; ?>
    <p></p>
    <?php if ($this->params->get('show_email_form') && ($this->churchdirectory->email_to || $this->churchdirectory->user_id)) : ?>

        <?php if ($this->params->get('presentation_style') != 'plain'): ?>
            <?php echo JHtml::_($this->params->get('presentation_style') . '.panel', JText::_('COM_CHURCHDIRECTORY_EMAIL_FORM'), 'display-form'); ?>
        <?php endif; ?>
        <?php if ($this->params->get('presentation_style') == 'plain'): ?>
            <?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_EMAIL_FORM') . '</h3>'; ?>
        <?php endif; ?>
        <?php echo $this->loadTemplate('form'); ?>
    <?php endif; ?>
    <?php if ($this->params->get('show_links')) : ?>
        <?php echo $this->loadTemplate('links'); ?>
    <?php endif; ?>
    <?php if ($this->params->get('show_articles') && $this->churchdirectory->user_id && $this->churchdirectory->articles) : ?>
        <?php if ($this->params->get('presentation_style') != 'plain'): ?>
            <?php echo JHtml::_($this->params->get('presentation_style') . '.panel', JText::_('JGLOBAL_ARTICLES'), 'display-articles'); ?>
        <?php endif; ?>
        <?php if ($this->params->get('presentation_style') == 'plain'): ?>
            <?php echo '<h3>' . JText::_('JGLOBAL_ARTICLES') . '</h3>'; ?>
        <?php endif; ?>
        <?php echo $this->loadTemplate('articles'); ?>
    <?php endif; ?>
    <?php if ($this->params->get('show_profile') && $this->churchdirectory->user_id && JPluginHelper::isEnabled('user', 'profile')) : ?>
        <?php if ($this->params->get('presentation_style') != 'plain'): ?>
            <?php echo JHtml::_($this->params->get('presentation_style') . '.panel', JText::_('COM_CHURCHDIRECTORY_PROFILE'), 'display-profile'); ?>
        <?php endif; ?>
        <?php if ($this->params->get('presentation_style') == 'plain'): ?>
            <?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_PROFILE') . '</h3>'; ?>
        <?php endif; ?>
        <?php echo $this->loadTemplate('profile'); ?>
    <?php endif; ?>
    <?php if ($this->churchdirectory->misc && $this->params->get('show_misc')) : ?>
        <?php if ($this->params->get('presentation_style') != 'plain') { ?>
            <?php echo JHtml::_($this->params->get('presentation_style') . '.panel', JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION'), 'display-misc');
        } ?>
        <?php if ($this->params->get('presentation_style') == 'plain'): ?>
            <?php echo '<h3>' . JText::_('COM_CHURCHDIRECTORY_OTHER_INFORMATION') . '</h3>'; ?>
    <?php endif; ?>
        <div class="churchdirectory-miscinfo">
            <div class="<?php echo $this->params->get('marker_class'); ?>">
    <?php echo $this->params->get('marker_misc'); ?>
            </div>
            <div class="churchdirectory-misc">
    <?php echo $this->churchdirectory->misc; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($this->params->get('presentation_style') != 'plain') { ?>
    <?php echo JHtml::_($this->params->get('presentation_style') . '.end');
} ?>
</div>
