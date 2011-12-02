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

$text = ( $this->isnew ? JText::_( 'New' ) : JText::_( 'Edit' ) );
JToolBarHelper::title( JText::_( 'KMLOptions' ) .': <small><small>[ '. $text .' ]</small></small>', 'generic.png' );

JToolBarHelper::save();
JToolBarHelper::apply();
if($this->isnew) {
	JToolBarHelper::cancel();
} else {
	JToolBarHelper::cancel( 'cancel', 'Close' );
}

if ($this->contact->image == '') {
	$this->contact->image = 'blank.png';
}

JHTML::_('behavior.tooltip');
jimport('joomla.html.pane');
$pane =& JPane::getInstance('sliders');

JFilterOutput::objectHTMLSafe( $this->contact, ENT_QUOTES, 'misc' );
$cparams = JComponentHelper::getParams ('com_media');
?>
<script language="javascript" type="text/javascript">
<!--
function submitbutton(pressbutton) {
	var form = document.adminForm;
	if (pressbutton == 'cancel') {
		submitform( pressbutton );
		return;
	}

	// do field validation
	if ( form.name.value == "" ) {
		alert( "<?php echo JText::_( 'You must provide a name.', true ); ?>" );
	} else if ( form.catid.value == 0 ) {
		alert( "<?php echo JText::_( 'Please select a Category.', true ); ?>" );
	} else {
		submitform( pressbutton );
	}
}
//-->
</script>

<form action="index.php" method="post" name="adminForm">

<div class="col width-60">
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'Details' ); ?></legend>

		<table class="admintable">
		<tr>
			<td class="key">
				<label for="name">
					<?php echo JText::_( 'Name' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="name" id="name" size="60" maxlength="255" value="<?php echo $this->contact->name; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="lname">
					<?php echo JText::_( 'Last Name' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="lname" id="lname" size="60" maxlength="255" value="<?php echo $this->contact->lname; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="name">
					<?php echo JText::_( 'Alias' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="alias" id="alias" size="60" maxlength="255" value="<?php echo $this->contact->alias; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<?php echo JText::_( 'Published' ); ?>:
			</td>
			<td>
				<?php echo $this->lists['published']; ?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="catid">
					<?php echo JText::_( 'Category' ); ?>:
				</label>
			</td>
			<td>
				<?php echo $this->lists['catid'];?>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="user_id">
					<?php echo JText::_( 'Linked to User' ); ?>:
				</label>
			</td>
			<td >
				<?php echo $this->lists['user_id'];?>
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<label for="ordering">
					<?php echo JText::_( 'Ordering' ); ?>:
				</label>
			</td>
			<td>
				<?php echo $this->lists['ordering']; ?>
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<label for="access">
					<?php echo JText::_( 'Access' ); ?>:
				</label>
			</td>
			<td>
				<?php echo $this->lists['access']; ?>
			</td>
		</tr>
		<?php
		if ($this->contact->id) {
			?>
			<tr>
				<td class="key">
					<label>
						<?php echo JText::_( 'ID' ); ?>:
					</label>
				</td>
				<td>
					<strong><?php echo $this->contact->id;?></strong>
				</td>
			</tr>
			<?php
		}
		?>
		</table>
	</fieldset>

	<fieldset class="adminform">
		<legend><?php echo JText::_( 'Information' ); ?></legend>

		<table class="admintable">
		<tr>
			<td class="key">
			<label for="con_position">
				<?php echo JText::_( 'Contact\'s Position' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="con_position" id="con_position" size="60" maxlength="255" value="<?php echo $this->contact->con_position; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="email_to">
					<?php echo JText::_( 'E-mail' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="email_to" id="email_to" size="60" maxlength="255" value="<?php echo $this->contact->email_to; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key" valign="top">
				<label for="address">
					<?php echo JText::_( 'Street Address' ); ?>:
					</label>
				</td>
				<td>
					<textarea name="address" id="address" rows="3" cols="45" class="inputbox"><?php echo $this->contact->address; ?></textarea>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="suburb">
					<?php echo JText::_( 'Town/Suburb' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="suburb" id="suburb" size="60" maxlength="100" value="<?php echo $this->contact->suburb;?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="state">
					<?php echo JText::_( 'State/County' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="state" id="state" size="60" maxlength="100" value="<?php echo $this->contact->state;?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="postcode">
					<?php echo JText::_( 'Postal Code/ZIP XXXXX-' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="postcode" id="postcode" size="60" maxlength="100" value="<?php echo $this->contact->postcode; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="postcodeaddon">
					<?php echo JText::_( 'Postal Code/ZIP -XXXX' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="postcodeaddon" id="postcodeaddon" size="60" maxlength="100" value="<?php echo $this->contact->postcodeaddon; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="country">
					<?php echo JText::_( 'Country' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="country" id="country" size="60" maxlength="100" value="<?php echo $this->contact->country;?>" />
			</td>
		</tr>
		<tr>
			<td class="key" valign="top">
			<label for="telephone">
			<?php echo JText::_( 'Telephone' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="telephone" id="telephone" size="60" maxlength="255" value="<?php echo $this->contact->telephone; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key" valign="top">
				<label for="mobile">
					<?php echo JText::_( 'Mobile' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="mobile" id="mobile" size="60" maxlength="255" value="<?php echo $this->contact->mobile; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key" valign="top">
				<label for="fax">
					<?php echo JText::_( 'Fax' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="fax" id="fax" size="60" maxlength="255" value="<?php echo $this->contact->fax; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="webpage">
					<?php echo JText::_( 'Webpage' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="webpage" id="webpage" size="60" maxlength="255" value="<?php echo $this->contact->webpage; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="skype">
					<?php echo JText::_( 'Skype' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="skype" id="skype" size="60" maxlength="255" value="<?php echo $this->contact->skype; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="yahoo_msg">
					<?php echo JText::_( 'Yahoo Messenger' ); ?>:
				</label>
			</td>
			<td>
				<input class="inputbox" type="text" name="yahoo_msg" id="yahoo_msg" size="60" maxlength="255" value="<?php echo $this->contact->yahoo_msg; ?>" />
			</td>
		</tr>
		<tr>
			<td  class="key" valign="top">
				<label for="misc">
					<?php echo JText::_( 'Miscellaneous Info' ); ?>:
				</label>
			</td>
			<td>
				<textarea name="misc" id="misc" rows="5" cols="45" class="inputbox"><?php echo $this->contact->misc; ?></textarea>
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="spouse">
					<?php echo JText::_( 'Spouse' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="spouse" id="spouse" size="60" maxlength="255" value="<?php echo $this->contact->spouse; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="children">
					<?php echo JText::_( 'Children' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="children" id="children" size="60" maxlength="255" value="<?php echo $this->contact->children; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="lat">
					<?php echo JText::_( 'Latitude' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="lat" id="lat" size="10" maxlength="11" value="<?php echo $this->contact->lat; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="lng">
					<?php echo JText::_( 'Longitude' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="lng" id="lng" size="10" maxlength="11" value="<?php echo $this->contact->lng; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="team">
					<?php echo JText::_( 'Members Team' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="team" id="team" size="2" maxlength="2" value="<?php echo $this->contact->team; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="teamicon">
					<?php echo JText::_( 'Members Team Icon' ); ?>:
				</label>
			</td>
			<td >
				<input class="inputbox" type="text" name="teamicon" id="teamicon" size="60" maxlength="255" value="<?php echo $this->contact->teamicon; ?>" />
			</td>
		</tr>
		<tr>
			<td class="key">
				<label for="image">
					<?php echo JText::_( 'Image' ); ?>:
				</label>
			</td>
			<td >
				<?php echo $this->lists['image']; ?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<script language="javascript" type="text/javascript">
				if (document.forms.adminForm.image.options.value!=''){
					jsimg='../<?php echo $this->image_path; ?>/' + getSelectedValue( 'adminForm', 'image' );
				} else {
					jsimg='../images/M_images/blank.png';
				}
				document.write('<img src=' + jsimg + ' name="imagelib" width="100" height="100" border="2" alt="<?php echo JText::_( 'Preview' ); ?>" />');
				</script>
			</td>
		</tr>
		</table>
	</fieldset>
</div>

<div class="col width-40">
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'Parameters' ); ?></legend>

		</fieldset>
</div>
<div class="clr"></div>

<input type="hidden" name="option" value="<?php echo $option; ?>" />
<input type="hidden" name="id" value="<?php echo $this->contact->id; ?>" />
<input type="hidden" name="cid[]" value="<?php echo $this->contact->id; ?>" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="contact" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
