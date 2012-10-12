
<?php
/**
 * Default view for Birthday and Annversary
 * @package ChurchDirectory
 * @subpackage Model.BirthdayAnniversary
 * @Copyright (C) 2007 - 2011 Joomla Bible Study Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.JoomlaBibleStudy.org
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;
?>
<div class="churchdirectory_model_wrapper">
    <?php if ($birthdays): ?>
        <?php if ($params->get('show_page_heading', 1)) : ?>
            <h3>
                <?php echo JText::_('MOD_BIRTHDAYANNIVERSARY_BIRTHDAY'); ?>
            </h3>
        <?php endif; ?>
        <table width="100%">
            <tr>
                <th class="churchdirectory_model_theder"><?php echo JText::_('MOD_BIRTHDAYANNIVERSARY_DAY') ?></th>
                <th class="churchdirectory_model_theder"><?php echo JText::_('MOD_BIRTHDAYANNIVERSARY_MEMBER_NAME') ?></th>
            </tr>
            <?php foreach ($birthdays AS $bday): ?>
                <tr>
                    <td class="churchdirectory_model_row"><?php echo $bday['day']; ?></td>
                    <td class="churchdirectory_model_row"><?php echo $bday['name']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <br />
    <?php if ($anniversary): ?>
        <?php if ($params->get('show_page_heading', 1)) : ?>
            <h3>
                <?php echo JText::_('MOD_BIRTHDAYANNIVERSARY_ANNIVERSARY'); ?>
            </h3>
        <?php endif; ?>
        <table width="100%">
            <tr>
                <th class="churchdirectory_model_theder"><?php echo JText::_('MOD_BIRTHDAYANNIVERSARY_DAY') ?></th>
                <th class="churchdirectory_model_theder"><?php echo JText::_('MOD_BIRTHDAYANNIVERSARY_MEMBER_NAME') ?></th>
            </tr>
            <?php foreach ($anniversary AS $annday): ?>
                <tr>
                    <td class="churchdirectory_model_row"><?php echo $annday['day']; ?></td>
                    <td class="churchdirectory_model_row"><?php echo $annday['name']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>