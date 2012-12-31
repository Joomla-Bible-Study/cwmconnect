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
defined('_JEXEC') or die('Restricted access');

$user =& JFactory::getUser();
$ordering = ($this->lists['order'] == 'cd.ordering');
JHTML::_('behavior.tooltip');

JToolBarHelper::title( JText::_( 'KMLOptions' ) .': <small><small>[ '. JText::_( 'Contact Manager' )  .' ]</small></small>', 'generic.png' );
JToolBarHelper::publishList();
JToolBarHelper::unpublishList();
JToolBarHelper::deleteList();
JToolBarHelper::editListX();
JToolBarHelper::addNewX();
?>
<div><a href="http://www.nfsda.org/custom/members.kml">KML</a> | <a href="https://www.nfsda.org/custom/phpsqlgeocode_update2.php">Geocode Update</a> | <a href="index.php?option=com_qcontacts&format=pdf">PDF</a>
<form action="index.php?option=com_qcontacts&view=kmloptions" method="post" name="adminForm">
	<table>
	<tr>
		<td align="left" width="100%">
			<?php echo JText::_( 'Filter' ); ?>:
			<input type="text" name="search" id="search" value="<?php echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
			<button onclick="this.form.submit();"><?php echo JText::_( 'Go' ); ?></button>
			<button onclick="document.getElementById('search').value='';this.form.getElementById('filter_catid').value='0';this.form.getElementById('filter_state').value='';this.form.submit();"><?php echo JText::_( 'Reset' ); ?></button>
		</td>
		<td nowrap="nowrap">
			<?php
			echo $this->lists['catid'];
			echo $this->lists['state'];
			?>
		</td>
	</tr>
	</table>

	<table class="adminlist">
	<thead>
		<tr>
			<th width="10">
				<?php echo JText::_( 'Num' ); ?>
			</th>
			<th width="10" class="title">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->items); ?>);" />
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',   'Name', 'cd.name', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th width="5%" class="title" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',   'Published', 'cd.published', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th nowrap="nowrap" width="8%">
				<?php echo JHTML::_('grid.sort',   'Order by', 'cd.ordering', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				<?php echo JHTML::_('grid.order',  $this->items ); ?>
			</th>
			<th width="8%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',   'Access', 'cd.access', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th width="10%" class="title">
				<?php echo JHTML::_('grid.sort',   'Category', 'category', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th class="title" nowrap="nowrap" width="10%">
				<?php echo JHTML::_('grid.sort',   'Linked to User', 'user', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
			<th width="1%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',   'ID', 'cd.id', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="11">
				
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$k = 0;
	for ($i=0, $n=count($this->items); $i < $n; $i++) {
		$row = $this->items[$i];

		$link 		= JRoute::_( 'index.php?option=com_qcontacts&controller=kmloptions&task=edit&cid[]='. $row->id );

		$checked 	= JHTML::_('grid.checkedout',   $row, $i );
		$access 	= JHTML::_('grid.access',   $row, $i );
		$published 	= JHTML::_('grid.published', $row, $i );

		$row->cat_link 	= JRoute::_( 'index.php?option=com_categories&section=com_qcontacts_kmloptions&task=edit&type=other&cid[]='. $row->catid );
		$row->user_link	= JRoute::_( 'index.php?option=com_users&task=editA&cid[]='. $row->user_id );
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo $this->pagination->getRowOffset( $i ); ?>
			</td>
			<td>
				<?php echo $checked; ?>
			</td>
			<td>
			<?php
			if (JTable::isCheckedOut($user->get ('id'), $row->checked_out )) :
				echo $row->name;
			else :
				?>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'Edit Contact' );?>::<?php echo $row->name; ?>">
				<a href="<?php echo $link; ?>">
					<?php echo $row->name; ?></a> </span>
				<?php
			endif;
			?>
			</td>
			<td align="center">
				<?php echo $published;?>
			</td>
			<td class="order">
				<span><?php echo $this->pagination->orderUpIcon( $i, ( $row->catid == @$this->items[$i-1]->catid ), 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $n, ( $row->catid == @$this->items[$i+1]->catid ), 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $row->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<td align="center">
				<?php echo $access;?>
			</td>
			<td>
				<a href="<?php echo $row->cat_link; ?>" title="<?php echo JText::_( 'Edit Category' ); ?>">
					<?php echo $row->category; ?></a>
			</td>
			<td>
				<a href="<?php echo $row->user_link; ?>" title="<?php echo JText::_( 'Edit User' ); ?>">
					<?php echo $row->user; ?></a>
			</td>
			<td align="center">
				<?php echo $row->id; ?>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</tbody>
	</table>

	<input type="hidden" name="option" value="<?php echo $option; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<input type="hidden" name="controller" value="contact" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
