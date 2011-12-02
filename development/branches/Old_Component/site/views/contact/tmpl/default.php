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
if(JRequest::getMethod() == 'POST' && !$this->error && $this->params->get('after_submit',0) == 1) {
	echo $this->loadTemplate('confirm');
} else {
?>
<?php if ($this->params->get('show_page_title') && $this->params->get('page_title') != $this->contact->name) { ?>
<h1 class="componentheading<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php echo $this->escape($this->params->get('page_title')); ?>
</h1>
<?php } ?>
<div id="qcontacts<?php echo $this->params->get('pageclass_sfx'); ?>">

<?php if ($this->params->get('show_contact_list') && count($this->contacts) > 1) { ?>
	<?php  
	$menus	=& JSite::getMenu();
	$menu	= $menus->getActive();
	?>
	<form method="get" action="index.php" name="selectForm" id="selectForm">
		<?php echo JText::_('Select Contact'); ?>
		<br />
		<input type="hidden" name="option" value="com_qcontacts" />
		<input type="hidden" name="view" value="contact" />
		<?php echo JHTML::_('select.genericlist', $this->contacts, 'id', 'class="inputbox" onchange="this.form.submit()"', 'id', 'name', $this->contact->id); ?>
		<input type="hidden" name="Itemid" value="<?php echo $menu->id ?>" />
	</form>
<?php } ?>

<?php if($this->contact->name && $this->params->get('show_name', 1)) { ?>
	<p id="contact-name">
		<?php echo $this->contact->name; ?>
	</p>
<?php } ?>
<?php if($this->contact->lname && $this->params->get('show_lname', 1)) { ?>
	<p id="contact-name">
		<?php echo $this->contact->lname; ?>
	</p>
<?php } ?>

<?php if($this->contact->con_position && $this->params->get('show_position', 1)) { ?>
	<p id="contact-position">
		<?php echo $this->contact->con_position; ?>
	</p>
<?php } ?>
<?php if ($this->contact->image && $this->params->get('show_image')) { ?>
	<div id="contact-image" class="<?php echo ($this->params->get('cimage_align') ? 'aright' : 'aleft'); ?>">
		<?php echo JHTML::_('image', trim($this->params->get('image_path','images/members'),'/').'/'.$this->contact->image, JText::_( 'Contact' )); ?>
	</div>
<?php } ?>

<?php echo $this->loadTemplate('address'); ?>
			
<?php if($this->params->get('allow_vcard')) { ?>
	<p>
		<?php echo JText::_('Download information as a'); ?>
		<a href="index.php?option=com_qcontacts&amp;controller=vcard&amp;contact_id=<?php echo $this->contact->id; ?>&amp;format=raw">
		<?php echo JText::_('VCard'); ?></a>
	</p>
<?php }
if ( $this->params->get('show_email_form') && ($this->contact->email_to || $this->contact->user_id)) {
	echo $this->loadTemplate('form');
}
?>
</div>
<?php } ?>