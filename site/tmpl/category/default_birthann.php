<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RenderHelper;
use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Cwmconnect\Site\View\Category\HtmlView $this */

$params      = $this->params;
$render      = new RenderHelper();
$birthdays   = $render->getBirthdays($params);
$anniversary = $render->getAnniversary($params);
?>
<div class="cwmconnect_model_wrapper cd_margin_top_20">
    <?php if ($birthdays) : ?>
        <?php if ($params->get('show_page_heading', 1)) : ?>
            <h3><?php echo Text::_('COM_BIRTHDAYANNIVERSARY_BIRTHDAY'); ?></h3>
        <?php endif; ?>
        <table style="width: 100%">
            <tr>
                <th class="cwmconnect_model_theder"><?php echo Text::_('COM_BIRTHDAYANNIVERSARY_DAY'); ?></th>
                <th class="cwmconnect_model_theder"><?php echo Text::_('COM_BIRTHDAYANNIVERSARY_MEMBER_NAME'); ?></th>
            </tr>
            <?php foreach ($birthdays as $bday) : ?>
                <tr>
                    <td class="cwmconnect_model_row"><?php echo $bday['day']; ?></td>
                    <td class="cwmconnect_model_row"><?php echo $this->escape($bday['name']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <br/>

    <?php if ($anniversary) : ?>
        <?php if ($params->get('show_page_heading', 1)) : ?>
            <h3><?php echo Text::_('COM_BIRTHDAYANNIVERSARY_ANNIVERSARY'); ?></h3>
        <?php endif; ?>
        <table style="width: 100%">
            <tr>
                <th class="cwmconnect_model_theder"><?php echo Text::_('COM_BIRTHDAYANNIVERSARY_DAY'); ?></th>
                <th class="cwmconnect_model_theder"><?php echo Text::_('COM_BIRTHDAYANNIVERSARY_MEMBER_NAME'); ?></th>
            </tr>
            <?php foreach ($anniversary as $annday) : ?>
                <tr>
                    <td class="cwmconnect_model_row"><?php echo $annday['day']; ?></td>
                    <td class="cwmconnect_model_row"><?php echo $this->escape($annday['name']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
