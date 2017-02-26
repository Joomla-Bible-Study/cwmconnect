<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');
JHTML::_('behavior.modal');

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$archived  = $this->state->get('filter.published') == 2 ? true : false;
$trashed   = $this->state->get('filter.published') == -2 ? true : false;
$canOrder  = $user->authorise('core.edit.state', 'com_churchdirectory.category');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_churchdirectroy&task=geostatus.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$sortFields = $this->getSortFields();
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
			<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
			<div class="clr"></div>
			<table class="adminlist table table-striped" id="articleList">
				<thead>
				<tr>
					<th width="1%" class="order nowrap center hidden-phone">
						<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
					</th>
					<th width="1%" class="title">
						<input type="checkbox" name="checkall-toggle" value=""
						       title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>"
						       onclick="Joomla.checkAll(this)"/>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
					</th>
					<th class="norap center">
						<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_ADDRESS', 'a.address', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap hidden-phone">
						<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_STATE', 'a.state', $listDirn, $listOrder); ?>
					</th>
					<th width="5%" class="nowrap hidden-phone">
						<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_SUBURB', 'a.suburb', $listDirn, $listOrder); ?>
					</th>
					<th width="5%" class="nowrap hidden-phone">
						<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_ZIP', 'a.postcode', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap">
						<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_STATUS', 'u.status', $listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$n              = count($this->items);
				foreach ($this->items as $i => $item) :
					$ordering = $listOrder == 'a.ordering';
					$canCreate  = $user->authorise('core.create', 'com_churchdirectory.category.' . $item->catid);
					$canEdit    = $user->authorise('core.edit', 'com_churchdirectory.category.' . $item->catid);
					$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
					$canEditOwn = $user->authorise('core.edit.own', 'com_churchdirectory.category.' . $item->catid) && $item->created_by == $userId;
					$canChange  = $user->authorise('core.edit.state', 'com_churchdirectory.category.' . $item->catid) && $canCheckin;

					$item->cat_link = JRoute::_('index.php?option=com_categories&extension=com_churchdirectory&task=edit&type=other&id=' . $item->catid);
					?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid ?>">
						<td class="order nowrap center hidden-phone">
							<?php if ($canChange) :
								$disableClassName = '';
								$disabledLabel = '';

								if (!$saveOrder) :
									$disabledLabel    = JText::_('JORDERINGDISABLED');
									$disableClassName = 'inactive tip-top';
								endif; ?>
								<span class="sortable-handler hasTooltip <?php echo $disableClassName; ?>"
								      title="<?php echo $disabledLabel; ?>">
				                <i class="icon-menu"></i>
				            </span>
								<input type="text" style="display:none" name="order[]" size="5"
								       value="<?php echo $item->ordering; ?>"
								       class="width-10 text-area-order "/>
							<?php else : ?>
								<span class="sortable-handler inactive">
					            <i class="icon-menu"></i>
				            </span>
							<?php endif; ?>
						</td>
						<td class="center hidden-phone">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="nowrap has-context">
							<div class="pull-left">
								<?php if ($canEdit || $canEditOwn) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&task=member.edit&id=' . (int) $item->id); ?>">
										<?php echo $this->escape($item->name); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
								<span class="small">
								<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
							</span>

								<div class="small">
									<?php echo $item->category_title; ?>
								</div>
							</div>
							<div class="pull-left">
								<?php
								// Create dropdown items
								JHtml::_('dropdown.edit', $item->id, 'member.');
								JHtml::_('dropdown.divider');
								JHtml::_('geoupdate.update', $item->id);

								// Render dropdown list
								echo JHtml::_('dropdown.render');
								?>
							</div>
						</td>
						<td class="center hidden-phone">
							<?php if (!empty($item->address))
							{
								echo $item->address;
							}
							else
							{
								echo JText::_('COM_CHURCHDIRECTORY_LBL_EMPTY');
							} ?>
						</td>
						<td class="small hidden-phone">
							<?php if (!empty($item->state))
							{
								echo $item->state;
							}
							else
							{
								echo JText::_('COM_CHURCHDIRECTORY_LBL_EMPTY');
							} ?>
						</td>
						<td class="center hidden-phone">
							<?php if (!empty($item->suburb))
							{
								echo $item->suburb;
							}
							else
							{
								echo JText::_('COM_CHURCHDIRECTORY_LBL_EMPTY');
							} ?>
						</td>
						<td class="small hidden-phone">
							<?php if (!empty($item->postcode))
							{
								echo $item->postcode;
							}
							else
							{
								echo JText::_('COM_CHURCHDIRECTORY_LBL_EMPTY');
							} ?>
						</td>
						<td class="small hidden-phone">
							<?php if (!empty($item->status))
							{
								echo $item->status;
							}
							else
							{
								echo JText::_('COM_CHURCHDIRECTORY_LBL_EMPTY');
							} ?>
						</td>
						<td class="center hidden-phone">
							<?php echo $item->id; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				<tfoot>
				<tr>
					<td colspan="11">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
			</table>
			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>
