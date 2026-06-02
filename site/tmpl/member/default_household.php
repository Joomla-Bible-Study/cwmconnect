<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\HouseholdVisibility;
use CWM\Component\Cwmconnect\Site\Helper\Layout;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Member\HtmlView $this */

if ($this->householdMembers === [] && $this->hiddenHouseholdCount === 0) {
    return;
}

$showsNames  = HouseholdVisibility::showsChildNames($this->householdScope);
$hiddenCount = (int) $this->hiddenHouseholdCount;
?>
<?php if ($this->householdMembers !== []) : ?>
    <ul class="list-unstyled mb-0 cwm-household">
        <?php foreach ($this->householdMembers as $hm) :
            $url      = Route::_('index.php?option=com_cwmconnect&view=member&id=' . (int) $hm->id);
            $name     = trim(($hm->name ?: '') ?: ($hm->lname ?: ''));
            $isHidden = (int) ($hm->display_in_directory ?? 1) === 0;
            $photo    = Layout::render('photo', [
                'id'       => (int) $hm->id,
                'hasPhoto' => !empty($hm->image),
                'alt'      => $name,
                'sizes'    => '40px',
                'width'    => 40,
                'height'   => 40,
                'rounded'  => true,
                'class'    => 'flex-shrink-0',
            ]);
            ?>
            <li class="d-flex align-items-center gap-2 mb-2">
                <?php if ($isHidden && $showsNames) : ?>
                    <?php echo $photo; ?>
                    <span class="text-body-secondary"><?php echo $this->escape($name); ?></span>
                    <span class="badge bg-body-secondary text-body-secondary"><?php echo Text::_('COM_CWMCONNECT_MEMBER_HOUSEHOLD_HIDDEN_TAG'); ?></span>
                <?php else : ?>
                    <a class="d-flex align-items-center gap-2 text-decoration-none text-body" href="<?php echo $url; ?>">
                        <?php echo $photo; ?>
                        <span><?php echo $this->escape($name); ?></span>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!$showsNames && $hiddenCount > 0) : ?>
    <p class="text-body-secondary small mb-0">
        <?php echo Text::plural('COM_CWMCONNECT_MEMBER_HOUSEHOLD_AND_N_CHILDREN', $hiddenCount); ?>
    </p>
<?php endif; ?>
