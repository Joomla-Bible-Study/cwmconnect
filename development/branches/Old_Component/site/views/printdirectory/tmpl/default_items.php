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
<?php
$printed_items = 0;
$items_per_page = 5
?>
<?php foreach($this->items as $group => $group_members) { ?>
<div class="group_label"><?php echo $group; ?></div>
<?php foreach($group_members as $contact) { ?>
<?php
if($printed_items == $items_per_page) {
    echo '<div style="page-break-after:always"></div>';
    $printed_items = 0;
}
?>
<div class="contact<?php echo $contact->odd+1; ?>">
	<?php foreach($this->columns as $col) {
		$c = $col['column'];
	?>
	<?php if ($c->field == 'image') {
		if ($contact->{$c->field} == '') { echo '<img src="' . $this->baseurl . '/images/members/1st_church_8x12.jpg" hspace="6" alt="No Image Avalible" width="100px" />'; }
		else { echo '<img src="' . $this->baseurl . '/images/members/' . $contact->{$c->field} . '" hspace="6" alt="image" width="100px" />'; }
		echo ('<span>');
                echo '<b>Name:</b> ' . JHTML::link($contact->link, $contact->name, array('class'=>'directory'.$contact->params->get('pageclass_sfx')));
		echo '<br />';
		if ($contact->con_position == '') {} else {
		echo '<b>Position:</b> ' . $contact->con_p;osition . '<br />';}
		if ($contact->address == '') {} else {
		echo $contact->address . '<br />';}
		if ($contact->suburb == '') {} else {
		echo $contact->suburb . ', '. $contact->state . ' ' . $contact->postcode;}
		if ($contact->postcodeaddon == '') {echo '<br /><br />';} else { echo '-'.$contact->postcodeaddon . '<br /><br />';}
		if ($contact->telephone == '') {} else {
		echo '<b>Ph:</b> ' . $contact->telephone .'<br />';}
		if ($contact->mobile == '') {} else {
		echo '<b>Mobile:</b> '.$contact->mobile.'<br />';}
		if ($contact->fax == '') {} else {
		echo '<b>Fax:</b> '.$contact->fax.'<br /></span>';}
	}
	} ?>
</div>
<?php 
$printed_items++;
} ?>
<?php } ?>
