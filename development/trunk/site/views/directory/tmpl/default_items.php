<?php
/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @version $Id: default.php 71 $
 * @package		com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 Joomla Bible Study, All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JHtml::core();
$printed_items = 0;
$printed_rows = 0;
//$items_per_page = 3;
//$items_per_row = 3;


$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
echo '<div style="page-break-after:always"></div>';
?>
<?php if (empty($this->items)) : ?>
    <p> <?php echo JText::_('COM_CHURCHDIRECTORY_NO_CONTACTS'); ?>	 </p>
<?php endif; ?>
<?php
foreach ($this->items as $item) {
    if ($printed_rows == $this->params->get('items_per_page')) {
        echo '<div style="page-break-after:always"></div>';
        $printed_rows = 0;
    }
    if ($printed_items == $this->params->get('items_per_row')) {
        $printed_items = 0;
    }
    ?>
    <div id="directory-items" class="sectiontableentry<?php echo $item->id + 1; ?>">
        <?php
        if ($item->image == null) {
            echo '<img src="' . $this->baseurl . '/media/com_churchdirectory/images/200-photo_not_available.jpg" alt="No Image Avalible" class="directory-img" /><br /><br />';
        } else {
            echo '<img src="' . $this->baseurl . '/images/members/' . $item->image . '" align="center" hspace="6" alt="' . $item->name . '" class="directory-img" /><br /><br />';
        }
        ?>
        <a href="<?php echo JRoute::_(ChurchDirectoryHelperRoute::getChurchDirectoryRoute($item->slug, $item->catid)); ?>">
            <?php echo $item->name; ?>
        </a><br />
        <?php
        if ($this->params->get('show_position_headings')) :
            if ($item->con_position != null) {

                echo '<b>Position:</b> ' . $item->con_position . '<br />';
            }
        endif;
        if ($item->address != null) {
            echo $item->address . '<br />';
        }
        if ($item->suburb != null) {
            echo $item->suburb . ', ' . $item->state . ' ' . $item->postcode;
        }
        if ($item->postcodeaddon == null) {
            echo '<br /><br />';
        } else {
            echo '-' . $item->postcodeaddon . '<br /><br />';
        }
        if ($this->params->get('show_telephone_headings')) :
            if ($item->telephone != null) {
                echo '<b>Ph:</b> ' . $item->telephone . '<br />';
            }
        endif;
        if ($item->mobile != null) {
            echo '<b>Mobile:</b> ' . $item->mobile . '<br />';
        }
        if ($item->fax != null) {
            echo '<b>Fax:</b> ' . $item->fax . '<br /></span>';
        }
        if ($item->misc != null) {
            echo '<span class="directory-title">Misc: </span>' . $item->misc;
        }
        if ($item->children != null) {
            echo '<span class="directory-title">Children: </span>';
            echo $item->children;
        }
        ?>
    </div>
    <?php
    $printed_items++;
    if ($printed_items == $items_per_row) {
        ?>
        <div style="clear: both"></div>
        <?php
        $printed_rows++;
    }
}
echo '<div style="page-break-after:always"></div>';