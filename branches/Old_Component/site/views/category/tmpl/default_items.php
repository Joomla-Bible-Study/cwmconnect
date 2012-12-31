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
defined( '_JEXEC' ) or die( 'Restricted access' ); ?>
<?php foreach($this->items as $item) { ?>
<tr class="sectiontableentry<?php echo $item->odd+1; ?>">
	<?php foreach($this->columns as $col) {
		$c = $col['column'];
	?>
	<td <?php if (!$this->params->get('show_headings',1)) {echo ($c->width ? 'width="'.$c->width.'"': '');} ?>>
	<?php if($c->field == 'name') {
		echo JHTML::link($item->link, $item->name, array('class'=>'category'.$this->params->get('pageclass_sfx')));
	} else {
		echo $item->{$c->field};
	}
	?>
	</td>
	<?php } ?>
</tr>
<?php } ?>
