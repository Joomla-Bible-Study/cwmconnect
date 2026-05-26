<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Cwmconnect\Administrator\View\Feedtokens\HtmlView $this */

$app       = Factory::getApplication();
$cleartext = (string) $app->getUserState('com_cwmconnect.feedtoken.cleartext', '');

if ($cleartext !== '') {
    $app->setUserState('com_cwmconnect.feedtoken.cleartext', null);
    $feedUrl = Uri::root() . 'index.php?option=com_cwmconnect&view=members&format=kml&token=' . urlencode($cleartext);
}
?>

<?php if (!empty($cleartext)) : ?>
    <div class="alert alert-success">
        <h4 class="alert-heading"><?php echo Text::_('COM_CWMCONNECT_FEEDTOKEN_CREATED_HEADING'); ?></h4>
        <p><?php echo Text::_('COM_CWMCONNECT_FEEDTOKEN_CREATED_CLEARTEXT'); ?></p>
        <div class="input-group mb-2">
            <input type="text" class="form-control font-monospace" value="<?php echo $this->escape($feedUrl); ?>" readonly onclick="this.select();">
        </div>
        <p class="mb-0 small text-muted"><?php echo Text::_('COM_CWMCONNECT_FEEDTOKEN_CREATED_WARNING'); ?></p>
    </div>
<?php endif; ?>

<form action="<?php echo Route::_('index.php?option=com_cwmconnect&view=feedtokens'); ?>"
      method="post" name="adminForm" id="adminForm">

    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <?php if ($this->items === []) : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_CWMCONNECT_FEEDTOKENS_EMPTY'); ?>
        </div>
    <?php else : ?>
        <table class="table table-striped" id="feedtokenList">
            <caption class="visually-hidden"><?php echo Text::_('COM_CWMCONNECT_MANAGER_FEEDTOKENS'); ?></caption>
            <thead>
                <tr>
                    <td class="w-1 text-center">
                        <?php echo HTMLHelper::_('grid.checkall'); ?>
                    </td>
                    <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_FEEDTOKEN_HEADING_LABEL', 'a.label', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
                    <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_FEEDTOKEN_HEADING_USER', 'u.name', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
                    <th scope="col" class="d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'COM_CWMCONNECT_FEEDTOKEN_HEADING_CREATED', 'a.created_at', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?></th>
                    <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_CWMCONNECT_FEEDTOKEN_HEADING_LAST_USED'); ?></th>
                    <th scope="col"><?php echo Text::_('COM_CWMCONNECT_FEEDTOKEN_HEADING_STATUS'); ?></th>
                    <th scope="col" class="w-1"><?php echo Text::_('JGLOBAL_FIELD_ID_LABEL'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td class="text-center">
                        <?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
                    </td>
                    <td>
                        <?php echo $this->escape($item->label); ?>
                    </td>
                    <td>
                        <?php echo $this->escape($item->user_name ?? ''); ?>
                    </td>
                    <td class="d-none d-md-table-cell small">
                        <?php echo $this->escape($item->created_at ?? ''); ?>
                    </td>
                    <td class="d-none d-md-table-cell small">
                        <?php echo $item->last_used_at ? $this->escape($item->last_used_at) : '<span class="text-muted">' . Text::_('JNEVER') . '</span>'; ?>
                    </td>
                    <td>
                        <?php if ($item->revoked_at) : ?>
                            <span class="badge bg-danger"><?php echo Text::_('COM_CWMCONNECT_FEEDTOKEN_STATUS_REVOKED'); ?></span>
                        <?php else : ?>
                            <span class="badge bg-success"><?php echo Text::_('COM_CWMCONNECT_FEEDTOKEN_STATUS_ACTIVE'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php echo $this->pagination->getListFooter(); ?>
    <?php endif; ?>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
