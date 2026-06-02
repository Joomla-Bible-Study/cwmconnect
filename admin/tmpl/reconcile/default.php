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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Cwmconnect\Administrator\View\Reconcile\HtmlView $this */

$token = Session::getFormToken();
?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span>
            <?php echo Text::_('COM_CWMCONNECT_RECONCILE_INTRO'); ?>
        </div>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-success">
                <span class="icon-check-circle" aria-hidden="true"></span>
                <?php echo Text::_('COM_CWMCONNECT_RECONCILE_NONE'); ?>
            </div>
        <?php else : ?>
            <table class="table">
                <caption class="visually-hidden"><?php echo Text::_('COM_CWMCONNECT_RECONCILE_TITLE'); ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                        <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_CWMCONNECT_EMAIL'); ?></th>
                        <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('JCATEGORY'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_CWMCONNECT_RECONCILE_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->items as $item) : ?>
                        <tr>
                            <td>
                                <strong><?php echo $this->escape($item->name); ?></strong>
                                <div class="small text-muted">#<?php echo (int) $item->id; ?></div>
                            </td>
                            <td class="small d-none d-md-table-cell"><?php echo $this->escape((string) $item->email_to); ?></td>
                            <td class="small d-none d-md-table-cell"><?php echo $this->escape((string) $item->category_title); ?></td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <form action="<?php echo Route::_('index.php?option=com_cwmconnect'); ?>" method="post" class="d-flex align-items-center gap-1">
                                        <select name="pc_person_id" class="form-select form-select-sm w-auto" aria-label="<?php echo Text::_('COM_CWMCONNECT_RECONCILE_MERGE_INTO'); ?>">
                                            <option value="0"><?php echo Text::_('COM_CWMCONNECT_RECONCILE_PICK_PERSON'); ?></option>
                                            <?php foreach ($this->syncedOptions as $pcId => $label) : ?>
                                                <option value="<?php echo (int) $pcId; ?>"><?php echo $this->escape($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                                        <input type="hidden" name="task" value="reconcile.merge">
                                        <input type="hidden" name="<?php echo $token; ?>" value="1">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <span class="icon-link" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_RECONCILE_MERGE'); ?>
                                        </button>
                                    </form>
                                    <form action="<?php echo Route::_('index.php?option=com_cwmconnect'); ?>" method="post"
                                          onsubmit="return confirm('<?php echo $this->escape(Text::_('COM_CWMCONNECT_RECONCILE_DELETE_CONFIRM')); ?>');">
                                        <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                                        <input type="hidden" name="task" value="reconcile.delete">
                                        <input type="hidden" name="<?php echo $token; ?>" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('JACTION_DELETE'); ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php echo $this->pagination->getListFooter(); ?>
        <?php endif; ?>
    </div>
</div>
