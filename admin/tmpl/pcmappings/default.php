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

use CWM\Component\Cwmconnect\Administrator\View\Pcmappings\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var HtmlView $this */

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=pcmappings'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CWMCONNECT_PCMAPPINGS_EMPTY'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="pcmappingsList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_CWMCONNECT_MANAGER_PCMAPPINGS'); ?>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_PCMAPPINGS_HEADING_PC_FIELD', 'a.pc_field_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_PCMAPPINGS_HEADING_PC_FIELD_ID', 'a.pc_field_id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_PCMAPPINGS_HEADING_JOOMLA_FIELD', 'f.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'a.updated_at', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td>
                                <td>
                                    <a href="<?php echo Route::_('index.php?option=com_cwmconnect&task=pcmapping.edit&id=' . (int) $item->id); ?>">
                                        <?php echo $this->escape($item->pc_field_name ?: $item->pc_field_slug ?: ('PC field #' . (int) $item->pc_field_id)); ?>
                                    </a>
                                    <?php if ($item->pc_field_slug && $item->pc_field_name) : ?>
                                        <div class="small text-muted">
                                            <?php echo $this->escape($item->pc_field_slug); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo (int) $item->pc_field_id; ?>
                                </td>
                                <td>
                                    <?php if ($item->joomla_field_title) : ?>
                                        <?php echo $this->escape($item->joomla_field_title); ?>
                                        <div class="small text-muted">
                                            <?php echo $this->escape((string) ($item->joomla_field_name ?? '')); ?>
                                        </div>
                                    <?php else : ?>
                                        <span class="text-danger">
                                            <?php echo Text::sprintf('COM_CWMCONNECT_PCMAPPINGS_MISSING_JOOMLA_FIELD', (int) $item->joomla_field_id); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $this->escape((string) ($item->updated_at ?: $item->created_at)); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
