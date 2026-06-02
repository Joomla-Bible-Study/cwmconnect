<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Cwmconnect\Administrator\View\Members\HtmlView $this */

$user      = $this->getCurrentUser();
$userId    = (int) $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder === 'a.ordering';

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_cwmconnect&task=members.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=members'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="membersList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_CWMCONNECT_MANAGER_MEMBERS'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_FIELD_LASTNAME', 'a.lname', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_FIELD_LINKED_USER_LABEL', 'ul.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                                </th>
                                <?php if (Multilanguage::isEnabled()) : ?>
                                    <th scope="col" class="w-5 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                                    </th>
                                <?php endif; ?>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody <?php if ($saveOrder) : ?>class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
                        <?php foreach ($this->items as $i => $item) :
                            $canCreate  = $user->authorise('core.create', 'com_cwmconnect.category.' . (int) $item->catid);
                            $canEdit    = $user->authorise('core.edit', 'com_cwmconnect.category.' . (int) $item->catid);
                            $canCheckin = $user->authorise('core.manage', 'com_checkin') || (int) $item->checked_out === $userId || (int) $item->checked_out === 0;
                            $canEditOwn = $user->authorise('core.edit.own', 'com_cwmconnect.category.' . (int) $item->catid) && (int) $item->created_by === $userId;
                            $canChange  = $user->authorise('core.edit.state', 'com_cwmconnect.category.' . (int) $item->catid) && $canCheckin;
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo (int) $item->catid; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php
                                    $iconClass = '';
                            if (!$canChange) {
                                $iconClass = ' inactive';
                            } elseif (!$saveOrder) {
                                $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                            }
                            ?>
                                    <span class="sortable-handler<?php echo $iconClass; ?>">
                                        <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                    </span>
                                    <?php if ($canChange && $saveOrder) : ?>
                                        <input type="text" name="order[]" size="5" value="<?php echo (int) $item->ordering; ?>" class="width-20 text-area-order hidden">
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'members.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
                                </td>
                                <td>
                                    <?php if ($item->checked_out) : ?>
                                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'members.', $canCheckin); ?>
                                    <?php endif; ?>
                                    <?php if ($canEdit || $canEditOwn) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_cwmconnect&task=member.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->name); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo $this->escape($item->name); ?>
                                    <?php endif; ?>
                                    <div class="small">
                                        <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                        <?php if (!empty($item->image)) : ?>
                                            <img class="cd-h-img" alt="" src="<?php echo Route::_('index.php?option=com_cwmconnect&task=photo.serve&id=' . (int) $item->id); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="small">
                                        <?php echo Text::_('JCATEGORY') . ': ' . $this->escape($item->category_title); ?>
                                    </div>
                                    <?php if (!empty($item->pc_membership)) : ?>
                                        <div class="small">
                                            <span class="badge bg-secondary"><?php echo $this->escape($item->pc_membership); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                    // Directory visibility: a member shows on the front end only
                                    // when published AND flagged for the directory. Flag the rest
                                    // with the reason so admins can see why someone is missing.
                                    if (empty($item->display_in_directory) || (int) $item->published === 0) :
                                        if (!empty($item->hidden_reason)) {
                                            $reasonKey = (string) $item->hidden_reason;
                                        } elseif ((int) $item->published === 0) {
                                            $reasonKey = 'inactive';
                                        } else {
                                            // Published but flagged off the directory: an admin
                                            // hid this individual (the full-directory sync lists
                                            // every active member otherwise).
                                            $reasonKey = 'manual';
                                        }
                            ?>
                                        <div class="small mt-1">
                                            <span class="badge bg-warning text-dark">
                                                <span class="icon-eye-slash" aria-hidden="true"></span>
                                                <?php echo Text::_('COM_CWMCONNECT_MEMBERS_HIDDEN_BADGE'); ?>
                                            </span>
                                            <span class="text-muted">
                                                <?php echo Text::_('COM_CWMCONNECT_MEMBERS_HIDDEN_REASON_' . strtoupper($reasonKey)); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item->funitname)) : ?>
                                        <div class="small">
                                            <?php echo Text::sprintf('COM_CWMCONNECT_FUNITNAME_SPRINTF', $this->escape($item->funitname)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $this->escape($item->lname); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php if (!empty($item->linked_user)) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->user_id); ?>">
                                            <?php echo $this->escape($item->linked_user); ?>
                                        </a>
                                        <div class="small"><?php echo $this->escape($item->email); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->access_level); ?>
                                </td>
                                <?php if (Multilanguage::isEnabled()) : ?>
                                    <td class="small d-none d-md-table-cell">
                                        <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>

                    <?php if (
                        $user->authorise('core.create', 'com_cwmconnect')
                        && $user->authorise('core.edit', 'com_cwmconnect')
                        && $user->authorise('core.edit.state', 'com_cwmconnect')
                    ) : ?>
                        <template id="joomla-dialog-batch">
                            <?php echo $this->loadTemplate('batch_body'); ?>
                            <?php echo $this->loadTemplate('batch_footer'); ?>
                        </template>
                    <?php endif; ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
