<?php
/**
 * ChurchDirectory Contact manager component for Joomla! 1.5 and 1.6
 *
 * @version 1.6.0
 * @package churchdirectory
 * @author NFSDA
 * @copyright Copyright (C) 2011 NFSDA. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of ChurchDirectory.
ChurchDirectory is free software: you can redistribute it and/or modify
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
	<?php if ($c->field == 'image') {
		if ($item->{$c->field} == '') { echo '<img src="' . $this->baseurl . '/images/members/1st_church_8x12.jpg" align="center" hspace="6" alt="image" width="100px" />'; }
		else { echo '<img src="' . $this->baseurl . '/images/members/' . $item->{$c->field} . '" align="center" hspace="6" alt="image" width="100px" />'; }
	}
	  elseif ($c->field == 'name') {
		echo JHTML::link($item->link, $item->name, array('class'=>'directory'.$this->params->get('pageclass_sfx')));
	} elseif ($c->field == 'catid'){
	
	}
	  else{
		echo $item->{$c->field};
	}
	?>
	</td>
	<?php } ?>
</tr>
<?php } ?>
