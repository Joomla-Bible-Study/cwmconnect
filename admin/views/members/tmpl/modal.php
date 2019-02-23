<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');

$input = JFactory::getApplication()->input;
$function = $input->getCmd('function', 'jSelectMember');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_churchdirectory&view=members&layout=modal&tmpl=component&function=' . $function); ?>"
      method="post" name="adminForm" id="adminForm" class="form-inline">
	<fieldset class="filter clearfix">
		<div class="btn-toolbar">
			<div class="btn-group pull-left">
				<label for="filter_search">
					<?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>
				</label>
				<input type="text" name="filter_search" id="filter_search"
				       value="<?php echo $this->escape($this->state->get('filter.search')); ?>" size="30"
				       title="<?php echo JText::_('COM_CHURCHDIRECTORY_FILTER_SEARCH_DESC'); ?>"/>
			</div>
			<div class="btn-group pull-left">
				<button type="submit" class="btn hasTooltip" data-placement="bottom"
				        title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>">
					<i class="icon-search"></i></button>
				<button type="button" class="btn hasTooltip" data-placement="bottom"
				        title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"
				        onclick="document.id('filter_search').value='';this.form.submit();">
					<i class="icon-remove"></i></button>
			</div>
			<input onclick="if (window.parent) window.parent.<?php echo $this->escape($function); ?>('0', '<?php echo $this->escape(addslashes(JText::_('COM_CHURCHDIRECTORY_SELECT_A_MEMBER'))); ?>', null, null);"
			       class="btn" type="button" value="<?php echo JText::_('COM_CHURCHDIRECTORY_FIELD_VALUE_NONE'); ?>"/>

			<div class="clearfix"></div>
		</div>
		<hr class="hr-condensed"/>

		<div class="filters">
			<select name="filter_access" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo JText::_('JOPTION_SELECT_ACCESS'); ?></option>
				<?php echo JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access')); ?>
			</select>

			<select name="filter_published" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED'); ?></option>
				<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true); ?>
			</select>

			<?php if ($this->state->get('filter.forcedLanguage')) : ?>
				<select name="filter_category_id" class="input-medium" onchange="this.form.submit()">
					<option value=""><?php echo JText::_('JOPTION_SELECT_CATEGORY');?></option>
					<?php echo JHtml::_('select.options', JHtml::_('category.options', 'com_churchdirectory', array('filter.language' => array('*', $this->state->get('filter.forcedLanguage')))), 'value', 'text', $this->state->get('filter.category_id'));?>
				</select>
				<input type="hidden" name="forcedLanguage"
				       value="<?php echo $this->escape($this->state->get('filter.forcedLanguage')); ?>"/>
				<input type="hidden" name="filter_language"
				       value="<?php echo $this->escape($this->state->get('filter.language')); ?>"/>
			<?php else : ?>
				<select name="filter_category_id" class="inputbox" onchange="this.form.submit()">
					<option value=""><?php echo JText::_('JOPTION_SELECT_CATEGORY'); ?></option>
					<?php echo JHtml::_('select.options', JHtml::_('category.options', 'com_churchdirectory'), 'value', 'text', $this->state->get('filter.category_id')); ?>
				</select>
				<select name="filter_language" class="inputbox" onchange="this.form.submit()">
					<option value=""><?php echo JText::_('JOPTION_SELECT_LANGUAGE'); ?></option>
					<?php echo JHtml::_('select.options', JHtml::_('contentlanguage.existing', true, true), 'value', 'text', $this->state->get('filter.language')); ?>
				</select>
			<?php endif; ?>
		</div>
	</fieldset>

	<table class="adminlist table table-striped table-condensed">
		<thead>
		<tr>
			<th class="title">
				<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
			</th>
			<th>
				<?php echo JHtml::_('grid.sort', 'COM_CHURCHDIRECTORY_FIELD_LINKED_USER_LABEL', 'ul.name', $listDirn, $listOrder); ?>
			</th>
			<th width="15%">
				<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
			</th>
			<th width="15%">
				<?php echo JHtml::_('grid.sort', 'JCATEGORY', 'a.catid', $listDirn, $listOrder); ?>
			</th>
			<th width="5%">
				<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_LANGUAGE', 'language', $listDirn, $listOrder); ?>
			</th>
			<th width="1%" class="nowrap">
				<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
			</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td colspan="6">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) : ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td>
					<a class="pointer"
					   onclick="if (window.parent) window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->name)); ?>');">
						<?php echo $this->escape($item->name); ?></a>
				</td>
				<td align="center">
					<?php if (!empty($item->linked_user)) : ?>
						<?php echo $item->linked_user; ?>
					<?php endif; ?>
				</td>
				<td class="center">
					<?php echo $this->escape($item->access_level); ?>
				</td>
				<td class="center">
					<?php echo $this->escape($item->category_title); ?>
				</td>
				<td class="center">
					<?php if ($item->language == '*'): ?>
						<?php echo JText::alt('JALL', 'language'); ?>
					<?php else: ?>
						<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
					<?php endif; ?>
				</td>
				<td align="center">
					<?php echo (int) $item->id; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<input type="hidden" name="task" value=""/>
	<?php echo JHtml::_('form.token'); ?>
</form>
