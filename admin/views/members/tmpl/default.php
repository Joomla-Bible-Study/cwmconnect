<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
if (CHURCHDIRECTORY_CHECKREL):
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
$archived  = $this->state->get('filter.published') == 2 ? true : false;
$trashed   = $this->state->get('filter.published') == -2 ? true : false;
$canOrder  = $user->authorise('core.edit.state', 'com_churchdirectory.category');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder && CHURCHDIRECTORY_CHECKREL)
{
	$saveOrderingUrl = 'index.php?option=com_churchdirectroy&task=memberss.saveOrderAjax&tmpl=component';
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
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=members'); ?>" method="post"
      name="adminForm" id="adminForm">
<?php if (!empty($this->sidebar)): ?>
<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
        <div id="j-main-container" class="span10">
        <?php else : ?>
            <div id="j-main-container">
            <?php endif; ?>
<?php if (CHURCHDIRECTORY_CHECKREL): ?>
                <div id="filter-bar" class="btn-toolbar">
                    <div class="filter-search btn-group pull-left">
                        <label for="filter_search"
                               class="element-invisible"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
                        <input type="text" name="filter_search" id="filter_search"
                               value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
                               title="<?php echo JText::_('COM_CHURCHDIRECTORY_SEARCH_IN_NAME'); ?>"/>
                    </div>
    <div class="btn-group pull-left">
        <button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <button type="button"
                onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <label for="limit"
               class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
		<?php echo $this->pagination->getLimitBox(); ?>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></label>
        <select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
            <option value=""><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></option>
            <option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING'); ?></option>
            <option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING'); ?></option>
        </select>
    </div>
    <div class="btn-group pull-right">
        <label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY'); ?></label>
        <select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
            <option value=""><?php echo JText::_('JGLOBAL_SORT_BY'); ?></option>
			<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder); ?>
        </select>
    </div>
	<?php endif; ?>
<?php if (!CHURCHDIRECTORY_CHECKREL): ?>
    <div class="filter-search fltlft">
        <label class="filter-search-lbl" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
        <input type="text" name="filter_search" id="filter_search"
               value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
               title="<?php echo JText::_('COM_CHURCHDIRECTORY_SEARCH_IN_NAME'); ?>"/>
        <button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <button type="button"
                onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
    </div>
    <div class="filter-select fltrt">
        <select name="filter_published" class="inputbox" onchange="this.form.submit()">
            <option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED'); ?></option>
			<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true); ?>
        </select>

        <select name="filter_category_id" class="inputbox" onchange="this.form.submit()">
            <option value=""><?php echo JText::_('JOPTION_SELECT_CATEGORY'); ?></option>
			<?php echo JHtml::_('select.options', JHtml::_('category.options', 'com_churchdirectory'), 'value', 'text', $this->state->get('filter.category_id')); ?>
        </select>
        <select name="filter_access" class="inputbox" onchange="this.form.submit()">
            <option value=""><?php echo JText::_('JOPTION_SELECT_ACCESS'); ?></option>
			<?php echo JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access')); ?>
        </select>

        <select name="filter_language" class="inputbox" onchange="this.form.submit()">
            <option value=""><?php echo JText::_('JOPTION_SELECT_LANGUAGE'); ?></option>
			<?php echo JHtml::_('select.options', JHtml::_('contentlanguage.existing', true, true), 'value', 'text', $this->state->get('filter.language')); ?>
        </select>

    </div>
	<?php endif; ?>

</div>
<div class="clr"></div>
<table class="adminlist table table-striped" id="articleList">
    <thead>
    <tr>
        <th width="1%" class="order nowrap center hidden-phone">
			<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
        </th>
        <th width="1%" class="title">
            <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>"
                   onclick="Joomla.checkAll(this)"/>
        </th>
        <th width="1%" style="min-width:55px" class="nowrap center">
			<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
        </th>
        <th>
			<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
        </th>
        <th>
			<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_LASTNAME', 'a.lname', $listDirn, $listOrder); ?>
        </th>
        <th class="nowrap hidden-phone">
			<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_LINKED_USER_LABEL', 'ul.name', $listDirn, $listOrder); ?>
        </th>
        <th width="5%" class="nowrap hidden-phone">
			<?php echo JHtml::_('grid.sort', 'JFEATURED', 'a.featured', $listDirn, $listOrder, null, 'desc'); ?>
        </th>
        <th width="10%" class="nowrap hidden-phone">
			<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
        </th>
        <th width="5%" class="nowrap hidden-phone">
			<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
        </th>
        <th width="1%" class="nowrap center hidden-phone">
			<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
        </th>
    </tr>
    </thead>
    <tbody>
	<?php
	$n = count($this->items);
	foreach ($this->items as $i => $item) :
		$ordering   = $listOrder == 'a.ordering';
		$canCreate  = $user->authorise('core.create', 'com_churchdirectory.category.' . $item->catid);
		$canEdit    = $user->authorise('core.edit', 'com_churchdirectory.category.' . $item->catid);
		$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
		$canEditOwn = $user->authorise('core.edit.own', 'com_churchdirectory.category.' . $item->catid) && $item->created_by == $userId;
		$canChange  = $user->authorise('core.edit.state', 'com_churchdirectory.category.' . $item->catid) && $canCheckin;

		$item->cat_link = JRoute::_('index.php?option=com_categories&extension=com_churchdirectory&task=edit&type=other&id=' . $item->catid);
		?>
    <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid?>">
        <td class="order nowrap center hidden-phone">

	        <?php if(CHURCHDIRECTORY_CHECKREL){
			 if ($canChange) :
			$disableClassName = '';
			$disabledLabel    = '';
			if (!$saveOrder) :
				$disabledLabel    = JText::_('JORDERINGDISABLED');
				$disableClassName = 'inactive tip-top';
			endif; ?>
            <span class="sortable-handler hasTooltip<?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
								<i class="icon-menu"></i>
							</span>
            <input type="text" style="display:none" name="order[]" size="5"
                   value="<?php echo $item->ordering;?>" class="width-20 text-area-order "/>
			<?php else : ?>
            <span class="sortable-handler inactive">
								<i class="icon-menu"></i>
							</span>
			<?php endif;
	        }
	        else{ ?>
	        <?php if ($canChange) : ?>
	        <?php if ($saveOrder) : ?>
		        <?php if ($listDirn == 'asc') : ?>
                    <span><?php echo $this->pagination->orderUpIcon($i, ($item->catid == @$this->items[$i - 1]->catid), 'members.orderup', 'JLIB_HTML_MOVE_UP', $ordering); ?></span>
                    <span><?php echo $this->pagination->orderDownIcon($i, $n, ($item->catid == @$this->items[$i + 1]->catid), 'members.orderdown', 'JLIB_HTML_MOVE_DOWN', $ordering); ?></span>
			        <?php elseif ($listDirn == 'desc') : ?>
                    <span><?php echo $this->pagination->orderUpIcon($i, ($item->catid == @$this->items[$i - 1]->catid), 'members.orderdown', 'JLIB_HTML_MOVE_UP', $ordering); ?></span>
                    <span><?php echo $this->pagination->orderDownIcon($i, $n, ($item->catid == @$this->items[$i + 1]->catid), 'members.orderup', 'JLIB_HTML_MOVE_DOWN', $ordering); ?></span>
			        <?php endif; ?>
		        <?php endif; ?>
	        <?php $disabled = $saveOrder ? '' : 'disabled="disabled"'; ?>
            <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" <?php echo $disabled ?> class="text-area-order" />
	        <?php else : ?>
	        <?php echo $item->ordering; ?>
	        <?php endif; ?>
	        <?php } ?>
        </td>
        <td class="center hidden-phone">
			<?php echo JHtml::_('grid.id', $i, $item->id); ?>
        </td>
        <td class="center">
			<?php echo JHtml::_('jgrid.published', $item->published, $i, 'members.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
        </td>
        <td class="nowrap has-context">
            <div class="pull-left">
				<?php if ($item->checked_out) : ?>
				<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'members.', $canCheckin); ?>
				<?php endif; ?>
				<?php if ($canEdit || $canEditOwn) : ?>
                <a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&task=member.edit&id=' . (int) $item->id); ?>">
					<?php echo $this->escape($item->name); ?></a>
				<?php else : ?>
				<?php echo $this->escape($item->name); ?>
				<?php endif; ?>
                <span class="small">
								<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias));?>
							</span>

                <div class="small">
					<?php echo $item->category_title; ?>
                </div>
            </div>
            <div class="pull-left">
				<?php
				if (CHURCHDIRECTORY_CHECKREL)
				{
					// Create dropdown items
					JHtml::_('dropdown.edit', $item->id, 'member.');
					JHtml::_('dropdown.divider');
					if ($item->published) :
						JHtml::_('dropdown.unpublish', 'cb' . $i, 'members.');
					else :
						JHtml::_('dropdown.publish', 'cb' . $i, 'members.');
					endif;

					if ($item->featured) :
						JHtml::_('dropdown.unfeatured', 'cb' . $i, 'members.');
					else :
						JHtml::_('dropdown.featured', 'cb' . $i, 'members.');
					endif;

					JHtml::_('dropdown.divider');

					if ($archived) :
						JHtml::_('dropdown.unarchive', 'cb' . $i, 'members.');
					else :
						JHtml::_('dropdown.archive', 'cb' . $i, 'members.');
					endif;

					if ($item->checked_out) :
						JHtml::_('dropdown.checkin', 'cb' . $i, 'members.');
					endif;

					if ($trashed) :
						JHtml::_('dropdown.untrash', 'cb' . $i, 'members.');
					else :
						JHtml::_('dropdown.trash', 'cb' . $i, 'members.');
					endif;

					// Render dropdown list
					echo JHtml::_('dropdown.render');

				}
				?>
            </div>
        </td>
        <td class="center hidden-phone">
			<?php echo $item->lname; ?>
        </td>
        <td align="small hidden-phone">
			<?php if (!empty($item->linked_user)) : ?>
            <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id=' . $item->user_id);?>"><?php echo $item->linked_user;?></a>
			<?php endif; ?>
        </td>
        <td class="center hidden-phone">
			<?php echo JHtml::_('member.featured', $item->featured, $i, $canChange); ?>
        </td>
        <td class="small hidden-phone">
			<?php echo $item->access_level; ?>
        </td>
        <td class="small hidden-phone">
			<?php if ($item->language == '*'): ?>
			<?php echo JText::alt('JALL', 'language'); ?>
			<?php else: ?>
			<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
			<?php endif;?>
        </td>
        <td class="center hidden-phone">
			<?php echo $item->id; ?>
        </td>
    </tr>
		<?php endforeach; ?>
    </tbody>
	<?php if (CHURCHDIRECTORY_CHECKREL): ?>
    <tfoot>
    <tr>
        <td colspan="11">
			<?php echo $this->pagination->getListFooter(); ?>
        </td>
    </tr>
    </tfoot>
	<?php endif; ?>
</table>
<input type="hidden" name="task" value=""/>
<input type="hidden" name="boxchecked" value="0"/>
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
<?php echo JHtml::_('form.token'); ?>
</div>
</form>
