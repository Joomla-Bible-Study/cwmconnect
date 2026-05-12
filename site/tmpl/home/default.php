<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Churchdirectory\Site\View\Home\HtmlView $this */

HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('bootstrap.tooltip');

$login = $this->user === null || $this->user->guest;
$check = \in_array($this->params->get('accesslevel'), $this->user?->getAuthorisedViewLevels() ?? [], false);
$count = \count($this->items);
?>
<div class="chdhome" style="padding: 5px;">
    <h1 class="center">
        <?php if ($this->params->get('show_page_heading', 0)) : ?>
            <?php echo $this->escape($this->params->get('page_heading')); ?>
        <?php endif; ?>
    </h1>

    <div class="span2 pull-left">
        <a href="index.php?option=com_users&amp;return=<?php echo $this->return; ?>">
            <button class="btn btn-primary">
                <?php echo $login ? Text::_('JLOGIN') : Text::_('JLOGOUT'); ?>
            </button>
        </a>
    </div>
    <div class="pull-right">
        <?php echo $this->renderHelper->getSearchField($this->params); ?>
    </div>
    <div class="clearfix"></div>

    <p class="center"><?php echo $this->params->get('home_intro', 'No Intro Text'); ?></p>

    <?php if ($login) : ?>
        <div class="chdlogin" style="padding-bottom: 40px">
            <div class="chdintro">
                <?php echo Text::_('COM_CHURCHDIRECTORY_HOME_INTRO'); ?>
                <?php if ($this->params->get('form')) : ?>
                    <a href="<?php echo $this->escape($this->params->get('form')); ?>">
                        <?php echo Text::_('COM_CHURCHDIRECTORY_AUTH_FORM'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$check) : ?>
        <span class="chdpleasereg">Please register as a church member. This directory is for church members only</span>
    <?php else : ?>
        <div class="row-fluid">
            <div class="span12">
                <?php
                $split = $count / 2;
                foreach ($this->items as $i => $item) :
                    $route = Route::_(RouteHelper::getMemberRoute($item->slug, $item->catid));
                    ?>
                    <div class="span6 pull-left" style="margin-left: 0">
                        <div class="center">
                            <a href="<?php echo $route; ?>">
                                <?php if ($item->image && $item->image !== '/') : ?>
                                    <img src="<?php echo $this->escape($item->image); ?>"
                                         alt="<?php echo $this->escape($item->name); ?>"
                                         style="max-width:240px;" class="img-polaroid"><br/>
                                <?php endif; ?>
                            </a>
                            <div class="cd-home-positions">
                                <a href="<?php echo $route; ?>">
                                    <span class="buld" style="font-size: x-large;">
                                        <?php echo $this->escape($item->name); ?>
                                    </span>
                                </a>
                                <br/>
                                <span class="small">
                                    <?php echo $this->renderHelper->getPosition($item->con_position); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
