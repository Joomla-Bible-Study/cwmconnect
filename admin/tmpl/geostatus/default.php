<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Churchdirectory\Administrator\View\Geostatus\HtmlView $this */

$user      = Factory::getApplication()->getIdentity();
$userId    = (int) ($user?->id ?? 0);
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_churchdirectory&view=geostatus'); ?>"
      method="post" name="adminForm" id="adminForm">
    <div id="j-main-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <table class="table" id="memberList">
                <caption class="visually-hidden">
                    <?php echo Text::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE_STATUS'); ?>
                </caption>
                <thead>
                    <tr>
                        <td style="width:1%" class="text-center">
                            <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </td>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHURCHDIRECTORY_FIELD_ADDRESS', 'a.address', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHURCHDIRECTORY_FIELD_STATE', 'a.state', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHURCHDIRECTORY_FIELD_SUBURB', 'a.suburb', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHURCHDIRECTORY_FIELD_ZIP', 'a.postcode', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHURCHDIRECTORY_FIELD_STATUS', 'u.status', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" style="width:1%">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->items as $i => $item) :
                        $editLink = Route::_('index.php?option=com_churchdirectory&task=member.edit&id=' . (int) $item->id);
                        ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                            </td>
                            <td>
                                <a href="<?php echo $editLink; ?>"><?php echo $this->escape($item->name); ?></a>
                                <div class="small">
                                    <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                </div>
                                <div class="small">
                                    <?php echo $this->escape($item->category_title ?? ''); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo $this->escape((string) ($item->address ?? '')) ?: Text::_('COM_CHURCHDIRECTORY_LBL_EMPTY'); ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php echo $this->escape((string) ($item->state ?? '')) ?: Text::_('COM_CHURCHDIRECTORY_LBL_EMPTY'); ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php echo $this->escape((string) ($item->suburb ?? '')) ?: Text::_('COM_CHURCHDIRECTORY_LBL_EMPTY'); ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php echo $this->escape((string) ($item->postcode ?? '')) ?: Text::_('COM_CHURCHDIRECTORY_LBL_EMPTY'); ?>
                            </td>
                            <td>
                                <?php echo isset($item->status) ? $item->status : Text::_('COM_CHURCHDIRECTORY_LBL_EMPTY'); ?>
                            </td>
                            <td class="text-center">
                                <?php echo (int) $item->id; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php echo $this->pagination?->getListFooter(); ?>
        <?php endif; ?>

        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
