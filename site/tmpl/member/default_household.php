<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\HouseholdVisibility;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Member\HtmlView $this */

if ($this->householdMembers === [] && $this->hiddenHouseholdCount === 0) {
    return;
}

$showsNames    = HouseholdVisibility::showsChildNames($this->householdScope);
$visibleByName = $this->householdMembers;
$hiddenCount   = (int) $this->hiddenHouseholdCount;
?>
<div class="cwmconnect-household mt-4">
    <h3 class="h5"><?php echo Text::_('COM_CWMCONNECT_MEMBER_HOUSEHOLD_HEADING'); ?></h3>

    <?php if ($visibleByName !== []) : ?>
        <ul class="list-unstyled">
            <?php foreach ($visibleByName as $hm) :
                $url  = Route::_('index.php?option=com_cwmconnect&view=member&id=' . (int) $hm->id);
                $name = trim(($hm->name ?: '') ?: ($hm->lname ?: ''));
                $isHidden = (int) ($hm->display_in_directory ?? 1) === 0;
                ?>
                <li>
                    <?php if ($isHidden && $showsNames) : ?>
                        <span class="text-muted"><?php echo $this->escape($name); ?></span>
                        <span class="badge bg-light text-dark"><?php echo Text::_('COM_CWMCONNECT_MEMBER_HOUSEHOLD_HIDDEN_TAG'); ?></span>
                    <?php else : ?>
                        <a href="<?php echo $url; ?>"><?php echo $this->escape($name); ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!$showsNames && $hiddenCount > 0) : ?>
        <p class="text-muted small">
            <?php echo Text::plural('COM_CWMCONNECT_MEMBER_HOUSEHOLD_AND_N_CHILDREN', $hiddenCount); ?>
        </p>
    <?php endif; ?>
</div>
