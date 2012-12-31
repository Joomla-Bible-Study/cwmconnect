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
?>
<script type="text/javascript">
<!--
	function validateForm( frm ) {
		regex=/^[a-zA-Z0-9._-]+@([a-zA-Z0-9.-]+\.)+[a-zA-Z0-9.-]{2,4}$/;
		if(typeof(frm.email) != 'undefined' && frm.email.value != '' && !regex.test(frm.email.value)) {
			alert( "<?php echo JText::_('Please enter a valid e-mail address.', true );?>");
			return false;
		} else if (false<?php foreach($this->fields as $f) {$fld=$f['field'];if($fld->required){echo ' || frm.'. $fld->name . '.value ==\'\'';} } ?>) {
			alert( "<?php echo JText::_('CONTACT_FORM_NC', true); ?>" );
			return false;
		}
		return true;
	}
// -->
</script>
<?php if($this->error) { ?>
<div id="qcontacts-error">
<?php echo $this->error; ?>
</div>
<?php } ?>
<form action="<?php echo JRoute::_( 'index.php?option=com_qcontacts&view=contact&id='.$this->contact->slug.'&catid='.$this->contact->catslug );?>" method="post" name="emailForm" id="emailForm" class="form-validate" onsubmit="return validateForm(this);">
	<div class="qcontacts_email<?php echo $this->params->get('pageclass_sfx'); ?>">
		<?php
		$i=0;
		foreach($this->fields as $f) {
		$fld = $f['field'];
		$i++;
		$rm = $fld->required ? $this->params->get('required_marker'):'';
		$rc = $fld->required ? ' class="required"':'';
		if($fld->type != 'checkbox') {
		?>
		<label for="<?php echo $fld->id;?>"<?php echo $rc; ?>>
		<?php echo $rm. JText::_($fld->label);?>
		</label>
		<?php
		}
		$n = $this->escape($fld->name);
		
		switch($fld->type) {
			case 'text':
			?>
			<input type="text" name="<?php echo $n;?>" id="<?php echo $fld->id;?>" size="<?php echo $fld->size;?>" value="<?php echo $this->escape($this->data->$n); ?>" class="inputbox" />
			<?php
			break;
			case 'textarea':
			?>
			<textarea cols="<?php echo $fld->cols;?>" rows="<?php echo $fld->rows;?>" name="<?php echo $n;?>" id="<?php echo $fld->id;?>" class="inputbox"><?php echo $this->data->$n ?></textarea>
			<?php
			break;
			case 'radio':
				?><div class="fld-wrap"><?php
				foreach($fld->value as $r) {
					?>
					<input type="radio" name="<?php echo $n;?>" class="radio" id="<?php echo $fld->id;?>" value="<?php echo $this->escape($r); ?>"<?php echo ($this->data->$n == $r ? ' checked="checked"':''); ?> /><?php echo $r; ?> 
					<?php
				}
				?></div><?php
				break;
			case 'checkbox':
				?>
				<div class="fld-wrap"><input type="checkbox" name="<?php echo $n;?>" class="chkbox" id="<?php echo $fld->id;?>" value="<?php echo $this->escape($fld->value); ?>"<?php echo ($this->data->$n == $fld->value ? ' checked="checked"':''); ?> />
				<label for="<?php echo $fld->id;?>" class="chkbox<?php echo ($fld->required ? ' required':''); ?>">
				<?php echo $rm. JText::_($fld->label);?>
				</label></div>
				<?php
				break;
			case 'dropdown':
				?>
				<select name="<?php echo $n;?>" id="<?php echo $fld->id;?>">
				<?php
				foreach($fld->value as $r) {
					?>
					<option value="<?php echo $this->escape($r);?>"<?php echo($this->data->$n == $r ? ' selected="selected"':''); ?>><?php echo $r;?></option>
					<?php
				}
				?></select><?php
				break;
		}
		?>
		<?php
		}
		?>
		<div class="separator"></div>
		<?php if ($this->params->get( 'show_email_copy' )) { ?>
			<input type="checkbox" class="chkbox" name="email_copy" id="contact_email_copy" value="1"  />
			<label class="chkbox" for="contact_email_copy">
				<?php echo JText::_('EMAIL_A_COPY'); ?>
			</label>
		<?php } ?>
		<div class="separator"></div>
		<?php if ($this->params->get('captcha')) { ?>
			<?php echo $this->params->get('captcha'); ?>
			<label for="captcha_code">
				<?php echo JText::_('Enter code displayed above'); ?>
			</label>
			<input type="text" name="captcha" id="captcha_code" size="5" class="inputbox" value="" />

		<?php } ?>
		
		<input type="submit" name="submit" class="contact-button" value="<?php echo JText::_('Send');?>" />
	</div>

<input type="hidden" name="option" value="com_qcontacts" />
<input type="hidden" name="view" value="contact" />
<input type="hidden" name="id" value="<?php echo $this->contact->slug; ?>" />

<input type="hidden" name="task" value="submit" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
	
	