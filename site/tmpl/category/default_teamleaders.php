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
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Churchdirectory\Site\View\Category\HtmlView $this */

$user             = Factory::getApplication()->getIdentity();
$authorisedLevels = $user ? $user->getAuthorisedViewLevels() : [1];
$teamleaders      = (string) $this->params->get('teamleaders', '1');
?>
<ul class="category list-striped" style="list-style: none; padding: 0;">
    <?php foreach ($this->items as $i => $item) : ?>
        <?php
        // teamleaders renders only the team-leader subset.
        if (!str_contains((string) $item->con_position, $teamleaders)) {
            continue;
        }
        if (!\in_array($item->access, $authorisedLevels, false)) {
            continue;
        }
        $stateClass = $item->state == 0 ? 'system-unpublished ' : '';
        ?>
        <li class="churchdirectory-list <?php echo $stateClass; ?>cat-list-row<?php echo $i % 2; ?>">
            <span class="float-end">
                <?php if ($this->params->get('show_telephone_headings') && !empty($item->telephone)) : ?>
                    <?php echo Text::sprintf('COM_CHURCHDIRECTORY_TELEPHONE_NUMBER', $this->escape($item->telephone)); ?><br/>
                <?php endif; ?>
                <?php if ($this->params->get('show_mobile_headings') && !empty($item->mobile)) : ?>
                    <?php echo Text::sprintf('COM_CHURCHDIRECTORY_MOBILE_NUMBER', $this->escape($item->mobile)); ?><br/>
                <?php endif; ?>
                <?php if ($this->params->get('show_fax_headings') && !empty($item->fax)) : ?>
                    <?php echo Text::sprintf('COM_CHURCHDIRECTORY_FAX_NUMBER', $this->escape($item->fax)); ?><br/>
                <?php endif; ?>
            </span>

            <p>
                <?php
                $imgSrc = $item->image ?: 'media/com_churchdirectory/images/200-photo_not_available.jpg';
                echo HTMLHelper::image(
                    $imgSrc,
                    Text::_('COM_CHURCHDIRECTORY_IMAGE_DETAILS'),
                    ['height' => '100', 'width' => '100']
                );
                ?>
                <br/>
                <strong class="list-title">
                    <a href="<?php echo Route::_(RouteHelper::getMemberRoute($item->slug, $item->catid)); ?>">
                        <?php echo $this->escape($item->name); ?>
                    </a>
                    <?php if ($item->published == 0) : ?>
                        <span class="badge bg-warning"><?php echo Text::_('JUNPUBLISHED'); ?></span>
                    <?php endif; ?>
                </strong>
                <br/>

                <?php if ($this->params->get('show_position_headings') && $item->con_position && $this->params->get('show_position')) : ?>
                    <dl class="contact-position">
                        <dt>
                            <?php if ($item->con_position != '-1') : ?>
                                <?php echo Text::_('COM_CHURCHDIRECTORY_POSITIONS'); ?>
                            <?php endif; ?>
                        </dt>
                        <dd>
                            <?php echo $this->renderHelper->getPosition($item->con_position); ?>
                        </dd>
                    </dl>
                <?php endif; ?>

                <?php if ($this->params->get('show_email_headings')) : ?>
                    <?php echo $item->email_to; ?>
                <?php endif; ?>
                <?php if ($this->params->get('show_suburb_headings') && !empty($item->suburb)) : ?>
                    <?php echo $this->escape($item->suburb) . ', '; ?>
                <?php endif; ?>
                <?php if ($this->params->get('show_state_headings') && !empty($item->state)) : ?>
                    <?php echo $this->escape($item->state) . ', '; ?>
                <?php endif; ?>
                <?php if ($this->params->get('show_country_headings') && !empty($item->country)) : ?>
                    <?php echo $this->escape($item->country); ?><br/>
                <?php endif; ?>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
