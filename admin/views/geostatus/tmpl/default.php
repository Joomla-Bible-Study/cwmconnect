<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

$version = version_compare(JVERSION, '3.0', 'ge');
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
if ($version):
	JHtml::_('bootstrap.tooltip');
	JHtml::_('behavior.multiselect');
	JHtml::_('dropdown.init');
	JHtml::_('formbehavior.chosen', 'select');
else :
	JHtml::_('behavior.multiselect');
	JHtml::_('behavior.tooltip');
endif;

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<script type="text/javascript">
    Joomla.orderTable = function () {
        table = document.getElementById("sortTable");
        direction = document.getElementById("directionTable");
        order = table.options[table.selectedIndex].value;
        if (order != '<?php echo $listOrder; ?>') {
            dirn = 'asc';
        } else {
            dirn = direction.options[direction.selectedIndex].value;
        }
        Joomla.tableOrdering(order, dirn, '');
    }
</script>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=geostatus'); ?>" method="post"
      name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
    </div>
        <div id="j-main-container" class="span10">
        <?php else : ?>
            <div id="j-main-container">
            <?php endif; ?>
<table class="adminlist table table-striped" id="articleList">
    <thead>
    <tr>
        <th width="1%" class="title">
            <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>"
                   onclick="Joomla.checkAll(this)"/>
        </th>
        <th>
            <span>Name</span>
        </th>
        <th>
            Address
        </th>
        <th width="5%">
            State
        </th>
        <th width="10%">
            Zip
        </th>
        <th width="15%" class="nowrap center">
            <span>Status</span>
        </th>
        <th width="1%" class="nowrap center hidden-phone">
			<?php echo JText::_('JGRID_HEADING_ID'); ?>
        </th>
    </tr>
    </thead>
<tbody>
	<?php if (!empty($this->info)):
	$n = count($this->info);
	foreach ($this->info AS $i => $data):
		$ordering   = $listOrder == 'm.ordering';
		$canCreate  = $user->authorise('core.create');
		$canEdit    = $user->authorise('core.edit');
		$canCheckin = $user->authorise('core.manage', 'com_checkin') || $data->checked_out == $userId || $data->checked_out == 0;
		$canEditOwn = $user->authorise('core.edit.own') && $data->created_by == $userId;
		$canChange  = $user->authorise('core.edit.state') && $canCheckin;?>
    <tr class="row<?php echo $i % 2; ?>" sortable-group-id="1">
        <td class="center hidden-phone">
			<?php echo JHtml::_('grid.id', $i, $data->id); ?>
        </td>
        <td class="nowrap has-context"
            style="<?php if ($data->status != 'No Geo Location Set'): ?> background-color: #F8B9B7<?php endif;?>">
            <div class="pull-left">
                <a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&task=member.edit&id=' . (int) $data->id); ?>">
					<?php echo $this->escape($data->name); ?></a>
            </div>
            <div class="pull-left">
				<?php
				if ($version)
				{
					// Create dropdown items
					JHtml::_('dropdown.edit', $data->id, 'member.');

					//JHtml::_('member.geoupdate', $data->id, 'members');

				}
				?>
            </div>
        </td>
        <td class="small">
			<?php echo $data->address; ?>
        </td>
        <td class="small">
			<?php echo $data->state; ?>
        </td>
        <td class="small">
			<?php echo $data->postcode; ?>
        </td>
        <td class="small center">
			<?php echo $data->status; ?>
        </td>
        <td class="center hidden-phone">
			<?php echo $data->id; ?>
        </td>
    </tr>
            </tbody>
        </table>
		<?php endforeach;
else: ?>
    <div>
        <p>No errors</p>
    </div>
	<?php endif; ?>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
	<?php echo JHtml::_('form.token'); ?>
</div>
</form>
