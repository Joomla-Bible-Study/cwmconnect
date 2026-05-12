<?php

/**
 * @package    Mod_Birthdayanniversary
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;

/**
 * @var \Joomla\Registry\Registry                                       $params
 * @var array<int, array{name: string, id: int, day: int, access: int}> $birthdays
 * @var array<int, array{name: string, id: int, day: int, access: int}> $anniversary
 */
?>
<div class="churchdirectory_model_wrapper">
    <?php if ($birthdays) : ?>
        <?php if ($params->get('show_page_heading', 1)) : ?>
            <h3><?php echo Text::_('MOD_BIRTHDAYANNIVERSARY_BIRTHDAY'); ?></h3>
        <?php endif; ?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th class="churchdirectory_model_theder"><?php echo Text::_('MOD_BIRTHDAYANNIVERSARY_DAY'); ?></th>
                    <th class="churchdirectory_model_theder"><?php echo Text::_('MOD_BIRTHDAYANNIVERSARY_MEMBER_NAME'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($birthdays as $bday) : ?>
                    <tr>
                        <td class="churchdirectory_model_row"><?php echo (int) $bday['day']; ?></td>
                        <td class="churchdirectory_model_row"><?php echo htmlspecialchars($bday['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <br/>

    <?php if ($anniversary) : ?>
        <?php if ($params->get('show_page_heading', 1)) : ?>
            <h3><?php echo Text::_('MOD_BIRTHDAYANNIVERSARY_ANNIVERSARY'); ?></h3>
        <?php endif; ?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th class="churchdirectory_model_theder"><?php echo Text::_('MOD_BIRTHDAYANNIVERSARY_DAY'); ?></th>
                    <th class="churchdirectory_model_theder"><?php echo Text::_('MOD_BIRTHDAYANNIVERSARY_MEMBER_NAME'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anniversary as $annday) : ?>
                    <tr>
                        <td class="churchdirectory_model_row"><?php echo (int) $annday['day']; ?></td>
                        <td class="churchdirectory_model_row"><?php echo htmlspecialchars($annday['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
