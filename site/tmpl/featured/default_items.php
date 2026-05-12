<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Cwmconnect\Site\View\Featured\HtmlView $this */

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<?php if (empty($this->items)) : ?>
    <p><?php echo Text::_('COM_CWMCONNECT_NO_MEMBERS'); ?></p>
<?php else : ?>
    <form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
          name="adminForm" id="adminForm">
        <fieldset class="filters">
            <legend class="hidelabeltxt"><?php echo Text::_('JGLOBAL_FILTER_LABEL'); ?></legend>
            <?php if ($this->params->get('show_pagination_limit')) : ?>
                <div class="display-limit">
                    <?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?>&#160;
                    <?php echo $this->pagination->getLimitBox(); ?>
                </div>
            <?php endif; ?>
            <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
            <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
        </fieldset>

        <table class="category">
            <?php if ($this->params->get('show_headings')) : ?>
                <thead>
                <tr>
                    <th class="item-num"><?php echo Text::_('JGLOBAL_NUM'); ?></th>
                    <th class="item-title">
                        <?php echo HTMLHelper::_('grid.sort', 'COM_CWMCONNECT_MEMBER_EMAIL_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?>
                    </th>
                    <?php if ($this->params->get('show_position_headings')) : ?>
                        <th class="item-position">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_CWMCONNECT_POSITIONS', 'a.con_position', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_email_headings')) : ?>
                        <th class="item-email"><?php echo Text::_('JGLOBAL_EMAIL'); ?></th>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_telephone_headings')) : ?>
                        <th class="item-phone"><?php echo Text::_('COM_CWMCONNECT_TELEPHONE'); ?></th>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_mobile_headings')) : ?>
                        <th class="item-phone"><?php echo Text::_('COM_CWMCONNECT_MOBILE'); ?></th>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_fax_headings')) : ?>
                        <th class="item-phone"><?php echo Text::_('COM_CWMCONNECT_FAX'); ?></th>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_suburb_headings')) : ?>
                        <th class="item-suburb">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_CWMCONNECT_SUBURB', 'a.suburb', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_state_headings')) : ?>
                        <th class="item-state">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_CWMCONNECT_STATE', 'a.state', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_country_headings')) : ?>
                        <th class="item-state">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_CWMCONNECT_COUNTRY', 'a.country', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                </tr>
                </thead>
            <?php endif; ?>

            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
                    <td class="item-num"><?php echo $i; ?></td>
                    <td class="item-title">
                        <a href="<?php echo Route::_(RouteHelper::getMemberRoute($item->slug, $item->catid)); ?>">
                            <?php echo $this->escape($item->name); ?>
                        </a>
                    </td>
                    <?php if ($this->params->get('show_position_headings')) : ?>
                        <td class="item-position"><?php echo $this->renderHelper->getPosition($item->con_position); ?></td>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_email_headings')) : ?>
                        <td class="item-email"><?php echo $item->email_to; ?></td>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_telephone_headings')) : ?>
                        <td class="item-phone"><?php echo $this->escape($item->telephone); ?></td>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_mobile_headings')) : ?>
                        <td class="item-phone"><?php echo $this->escape($item->mobile); ?></td>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_fax_headings')) : ?>
                        <td class="item-phone"><?php echo $this->escape($item->fax); ?></td>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_suburb_headings')) : ?>
                        <td class="item-suburb"><?php echo $this->escape($item->suburb); ?></td>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_state_headings')) : ?>
                        <td class="item-state"><?php echo $this->escape($item->state); ?></td>
                    <?php endif; ?>
                    <?php if ($this->params->get('show_country_headings')) : ?>
                        <td class="item-state"><?php echo $this->escape($item->country); ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($this->params->get('show_pagination')) : ?>
            <div class="pagination">
                <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                    <p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
                <?php endif; ?>
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
        <?php endif; ?>
    </form>
<?php endif; ?>

<div class="item-separator"></div>
