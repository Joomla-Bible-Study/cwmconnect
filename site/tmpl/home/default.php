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

use CWM\Component\Cwmconnect\Site\Helper\Layout;
use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Home\HtmlView $this */

$login     = $this->user === null || $this->user->guest;
$check     = \in_array($this->params->get('accesslevel'), $this->user?->getAuthorisedViewLevels() ?? [], false);
$count     = \count($this->items);
$heading   = (string) $this->params->get('page_heading', '');
$intro     = (string) $this->params->get('home_intro', '');
$browseUrl = Route::_('index.php?option=com_cwmconnect&view=members');
?>
<div class="cwmconnect-home">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h2 mb-0"><?php echo $heading !== '' ? $this->escape($heading) : Text::_('COM_CWMCONNECT_DEFAULT_PAGE_TITLE'); ?></h1>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php echo $this->renderHelper->getSearchField($this->params); ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo Route::_('index.php?option=com_users&return=' . $this->return); ?>">
                <span class="icon-<?php echo $login ? 'lock' : 'out-2'; ?>" aria-hidden="true"></span>
                <?php echo $login ? Text::_('JLOGIN') : Text::_('JLOGOUT'); ?>
            </a>
        </div>
    </div>

    <?php if ($intro !== '') : ?>
        <p class="lead"><?php echo $intro; ?></p>
    <?php endif; ?>

    <p>
        <a class="btn btn-primary" href="<?php echo $browseUrl; ?>">
            <span class="icon-users" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_HOME_BROWSE_DIRECTORY'); ?>
        </a>
        <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_cwmconnect&view=households'); ?>">
            <span class="icon-home" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_HOME_BROWSE_HOUSEHOLDS'); ?>
        </a>
    </p>

    <?php if ($login) : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_CWMCONNECT_HOME_INTRO'); ?>
            <?php if ($this->params->get('form')) : ?>
                <a class="alert-link" href="<?php echo $this->escape($this->params->get('form')); ?>">
                    <?php echo Text::_('COM_CWMCONNECT_AUTH_FORM'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$check) : ?>
        <?php echo Layout::render('emptystate', [
            'icon'    => 'icon-lock',
            'message' => Text::_('COM_CWMCONNECT_HOME_MEMBERS_ONLY'),
        ]); ?>
    <?php elseif ($count === 0) : ?>
        <?php echo Layout::render('emptystate', [
            'icon'    => 'icon-users',
            'message' => Text::_('COM_CWMCONNECT_HOME_NO_FEATURED'),
            'ctaUrl'  => $browseUrl,
            'ctaText' => Text::_('COM_CWMCONNECT_HOME_BROWSE_DIRECTORY'),
        ]); ?>
    <?php else : ?>
        <h2 class="h4 mt-4 mb-3"><?php echo Text::_('COM_CWMCONNECT_HOME_FEATURED_HEADING'); ?></h2>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($this->items as $item) : ?>
                <div class="col">
                    <?php echo Layout::render('membercard', [
                        'id'         => (int) $item->id,
                        'name'       => $item->name,
                        'hasPhoto'   => $item->image && $item->image !== '/',
                        'profileUrl' => Route::_(RouteHelper::getMemberRoute($item->slug, $item->catid)),
                        'position'   => $this->renderHelper->getPosition($item->con_position),
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
