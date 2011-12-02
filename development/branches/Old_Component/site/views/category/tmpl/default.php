<?php
/**
 * QContacts Contact manager component for Joomla! 1.5
 *
 * @version 1.0.6
 * @package qcontacts
 * @author Massimo Giagnoni
 * @copyright Copyright (C) 2008 Massimo Giagnoni. All rights reserved.
 * @copyright Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of QContacts.
QContacts is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
defined( '_JEXEC' ) or die( 'Restricted access' );
function gridSort($title, $order, $direction = 'asc', $selected = 0, $task=NULL) {
	$direction	= strtolower( $direction );
	$images		= array( 'sort_asc.png', 'sort_desc.png' );
	$index		= intval( $direction == 'desc' );
	$direction	= ($direction == 'desc') ? 'asc' : 'desc';
	
	if ($order != $selected ) {
		$html = '<a href="javascript:tableOrdering(\''.$order.'\',\'\',\''.$task.'\');" title="">';
		$html .= JText::_( $title );
		$html .= '</a>';
	} else {
		$html = JText::_( $title );
		$html .= '&nbsp;<a href="javascript:tableOrdering(\''.$order.'\',\''.$direction.'\',\''.$task.'\');" title="'.JText::_( 'Click to sort this column' ).'">';
		$html .= JHTML::_('image.administrator',  $images[$index], '/images/', NULL, NULL);
		$html .= '</a>';
	}
		
	return $html;
}
$cparams =& JComponentHelper::getParams('com_media');
?>

<?php if ( $this->params->get('show_page_title',1) ) { ?>
<div class="componentheading<?php echo $this->params->get( 'pageclass_sfx' ); ?>">
<?php echo $this->escape($this->params->get('page_title')); ?>
</div>
<?php } ?>
<div class="contentpane<?php echo $this->params->get( 'pageclass_sfx' ); ?>">
<?php if ($this->category->image || $this->category->description) { ?>
	<div class="contentdescription<?php echo $this->params->get( 'pageclass_sfx' ); ?>">
	<?php if ($this->params->get('image') != -1 && $this->params->get('image') != '') { ?>
		<img src="<?php echo $this->baseurl .'/'. $cparams->get('image_path') . '/'. $this->params->get('image'); ?>" align="<?php echo $this->params->get('image_align'); ?>" hspace="6" alt="<?php echo JText::_( 'Contacts' ); ?>" />
	<?php } elseif ($this->category->image) { ?>
		<img src="<?php echo $this->baseurl .'/'. $cparams->get('image_path') . '/'. $this->category->image; ?>" align="<?php echo $this->category->image_position; ?>" hspace="6" alt="<?php echo JText::_( 'Contacts' ); ?>" />
	<?php } ?>
	<?php echo $this->category->description; ?>
	</div>
<?php } ?>
<script language="javascript" type="text/javascript">
	function tableOrdering( order, dir, task ) {
	var form = document.adminForm;

	form.filter_order.value 	= order;
	form.filter_order_Dir.value	= dir;
	document.adminForm.submit( task );
}
</script>
<form action="<?php echo $this->action; ?>" method="post" name="adminForm">
<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
	<thead>
		<tr>
			<td align="right" colspan="6">
			<?php if ($this->params->get('show_limit')) {
				echo JText::_('Display Num') .'&nbsp;';
				echo $this->pagination->getLimitBox();
			} ?>
			</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td align="center" colspan="6" class="sectiontablefooter<?php echo $this->params->get( 'pageclass_sfx' ); ?>">
				<?php echo $this->pagination->getPagesLinks(); ?>
			</td>
		</tr>
		<tr>
			<td colspan="6" align="right">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php if ($this->params->get('show_headings',1)) { ?>
		<tr>
		<?php 
			foreach($this->columns as $col) {
				$c = $col['column'];
				?>
				<td <?php echo ($c->width ? 'width="'.$c->width.'"': ''); ?>height="20" class="sectiontableheader<?php echo $this->params->get('pageclass_sfx'); ?>">
				<?php
				if(isset($c->sortable) && $c->sortable) {
					echo gridSort($c->label, ($c->field == 'category_name' ? '':'cd.').$c->field, $this->lists['order_Dir'], $this->lists['order'] );	
				} else {
					echo JText::_($c->label);
				}
				?>
				</td>
				<?php
			}
		?>
		</tr>
	<?php } ?>
	<?php echo $this->loadTemplate('items'); ?>
</tbody>
</table>

<input type="hidden" name="option" value="com_qcontacts" />
<input type="hidden" name="catid" value="<?php echo $this->category->id;?>" />
<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
<input type="hidden" name="filter_order_Dir" value="" />
</form>
</div>
