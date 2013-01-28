<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

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
$archived  = $this->state->get('filter.published') == 2 ? true : false;
$trashed   = $this->state->get('filter.published') == -2 ? true : false;
$canOrder  = $user->authorise('core.edit.state', 'com_churchdirectory.category');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder && $version)
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
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=dirheaders'); ?>" method="post"
      name="adminForm" id="adminForm">
<?php if (!empty($this->sidebar)): ?>
<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
        <div id="j-main-container" class="span10">
        <?php else : ?>
            <div id="j-main-container">
            <?php endif; ?>
<?php if ($version): ?>
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
	<?php else: ?>
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
        <th width="10" class="title">
            <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->items); ?>);"/>
        </th>
        <th>
			<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
        </th>
        <th width="5%" style="min-width:55px" class="nowrap center">
			<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
        </th>
        <th width="10%" class="nowrap hidden-phone">
			<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
        </th>
        <th width="5%" class="nowrap hidden-phone">
			<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
        </th>
        <th width="1%">
			<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
        </th>
    </tr>
    </thead>
    <tbody>
	<?php
	$n = count($this->items);
	foreach ($this->items as $i => $item) :
		$ordering   = $listOrder == 'a.ordering';
		$canCreate  = $user->authorise('core.create');
		$canEdit    = $user->authorise('core.edit');
		$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
		$canEditOwn = $user->authorise('core.edit.own') && $item->created_by == $userId;
		$canChange  = $user->authorise('core.edit.state') && $canCheckin;
		?>
    <tr class="row<?php echo $i % 2; ?>">
        <td class="order nowrap center hidden-phone">
			<?php echo ChurchDirectoryHelper::ordering($canChange, $saveOrder, $item, $this->items, $ordering, $i, $n, 'dirheaders', $this->pagination, $listDirn); ?>
        </td>
        <td class="center">
			<?php echo JHtml::_('grid.id', $i, $item->id); ?>
        </td>
        <td class="nowrap has-context">
            <div class="pull-left">
				<?php if ($item->checked_out) : ?>
				<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'dirheaders.', $canCheckin); ?>
				<?php endif; ?>
				<?php if ($canEdit || $canEditOwn) : ?>
                <a href="<?php echo JRoute::_('index.php?option=com_churchdirectory&task=dirheader.edit&id=' . (int) $item->id); ?>">
					<?php echo $this->escape($item->name); ?></a>
				<?php else : ?>
				<?php echo $this->escape($item->name); ?>
				<?php endif; ?>
                <span class="small">
					<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
	                </span>
            </div>
            <div class="pull-left">
				<?php
				if ($version)
				{
					// Create dropdown items
					JHtml::_('dropdown.edit', $item->id, 'dirheader.');
					JHtml::_('dropdown.divider');
					if ($item->published) :
						JHtml::_('dropdown.unpublish', 'cb' . $i, 'dirheaders.');
					else :
						JHtml::_('dropdown.publish', 'cb' . $i, 'dirheaders.');
					endif;

					JHtml::_('dropdown.divider');

					if ($archived) :
						JHtml::_('dropdown.unarchive', 'cb' . $i, 'dirheaders.');
					else :
						JHtml::_('dropdown.archive', 'cb' . $i, 'dirheaders.');
					endif;

					if ($item->checked_out) :
						JHtml::_('dropdown.checkin', 'cb' . $i, 'dirheaders.');
					endif;

					if ($trashed) :
						JHtml::_('dropdown.untrash', 'cb' . $i, 'dirheaders.');
					else :
						JHtml::_('dropdown.trash', 'cb' . $i, 'dirheaders.');
					endif;

					// Render dropdown list
					echo JHtml::_('dropdown.render');

				}
				?>
            </div>
        </td>
        <td align="center">
			<?php echo JHtml::_('jgrid.published', $item->published, $i, 'dirheaders.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
        </td>
        <td class="small hidden-phone">
			<?php echo $item->access_level; ?>
        </td>
        <td class="small hidden-phone">
			<?php if ($item->language == '*'): ?>
			<?php echo JText::alt('JALL', 'language'); ?>
			<?php else: ?>
			<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
			<?php endif; ?>
        </td>
        <td class="center hidden-phone">
			<?php echo $item->id; ?>
        </td>
    </tr>
		<?php endforeach; ?>
    </tbody>
	<?php if ($version): ?>
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
