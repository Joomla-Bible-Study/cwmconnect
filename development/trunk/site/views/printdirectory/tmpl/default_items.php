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
	<?php if ($c->field == 'image') {
		echo '<table><tr><td VALIGN=TOP>';
		if ($item->{$c->field} == '') { echo '<img src="' . $this->baseurl . '/images/members/1st_church_8x12.jpg" hspace="6" alt="No Image Avalible" width="100px" />'; }
		else { echo '<img src="' . $this->baseurl . '/images/members/' . $item->{$c->field} . '" hspace="6" alt="image" width="100px" />'; }
		echo '</td><td>';
		echo '<b>Name:</b> ' . JHTML::link($item->link, $item->name, array('class'=>'directory'.$this->params->get('pageclass_sfx')));
		echo '<br />';
		if ($item->con_position == '') {} else {
		echo '<b>Position:</b> ' . $item->con_position . '<br />';}
		if ($item->address == '') {} else {
		echo $item->address . '<br />';}
		if ($item->suburb == '') {} else {
		echo $item->suburb . ', '. $item->state . ' ' . $item->postcode;}
		if ($item->postcodeaddon == '') {echo '<br /><br />';} else { echo '-'.$item->postcodeaddon . '<br /><br />';}
		if ($item->telephone == '') {} else {
		echo '<b>Ph:</b> ' . $item->telephone .'<br />';}
		if ($item->mobile == '') {} else {
		echo '<b>Mobile:</b> '.$item->mobile.'<br />';}
		if ($item->fax == '') {} else {
		echo '<b>Fax:</b> '.$item->fax.'<br /></td></tr></table>';}
		

	}
	?>
	<?php echo '</td>';
	} ?>
</tr>
<?php } ?>
