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
<?php 
if ($this->params->get('address_check') > 0) { 
?>
<div id="contact-address">
	<address>
	<?php if ( $this->params->get('address_check') > 0) : ?>
	<span class="marker"><?php echo $this->params->get('marker_address'); ?></span>
	<?php endif; ?>

	<?php if ($this->contact->address && $this->params->get('show_street_address',1)) : ?>
	<p id="contact-street"><?php echo nl2br($this->contact->address); ?></p>
	<?php endif; ?>

	<?php if ($this->contact->suburb && $this->params->get('show_suburb',1)) : ?>
	<p id="contact-suburb"><?php echo $this->contact->suburb; ?></p>
	<?php endif; ?>

	<?php if ($this->contact->state && $this->params->get('show_state',1)) : ?>
	<p id="contact-state"><?php echo $this->contact->state; ?></p>
	<?php endif; ?>

	<?php if ($this->contact->country && $this->params->get('show_country',1)) : ?>
	<p id="contact-country"><?php echo $this->contact->country; ?></p>
	<?php endif; ?>

	<?php if ($this->contact->postcode && $this->params->get('show_postcode',1)) : ?>
	<p id="contact-postcode"><?php echo $this->contact->postcode; ?></p>
	<?php endif; ?>
	</address>
</div>

<?php } ?>
<br />

<?php 
$mc = '<div class="marker'.$this->params->get('other_class') . '">';
if ( ($this->contact->email_to && $this->params->get('show_email')) || 
($this->contact->telephone  && $this->params->get('show_telephone',1)) || 
($this->contact->fax && $this->params->get('show_fax',1)) || 
($this->contact->mobile && $this->params->get('show_mobile',1)) || 
($this->contact->webpage && $this->params->get('show_webpage')) || 
($this->contact->skype && $this->params->get('show_skype')) || 
($this->contact->yahoo_msg  && $this->params->get('show_yahoo'))) : ?>

<?php 
if ( $this->contact->email_to && $this->params->get('show_email')) : ?>
<div id="contact-email" class="contact-other"><?php echo $mc . $this->params->get('marker_email'); ?></div>
	<?php echo $this->contact->email_to; ?></div>
<?php endif; ?>
<?php if($this->contact->telephone && $this->params->get('show_telephone',1)) : ?>
<div id="contact-telephone" class="contact-other"><?php echo $mc . $this->params->get('marker_telephone'); ?></div>
	<?php echo nl2br($this->contact->telephone); ?></div>
<?php endif; ?>
<?php if($this->contact->fax && $this->params->get('show_fax',1)) : ?>
<div id="contact-fax" class="contact-other"><?php echo $mc . $this->params->get('marker_fax'); ?></div>
	<?php echo nl2br($this->contact->fax); ?></div>
<?php endif; ?>
<?php if($this->contact->mobile && $this->params->get('show_mobile',1)) :?>
<div id="contact-mobile" class="contact-other"><?php echo $mc . $this->params->get( 'marker_mobile' ); ?></div>
	<?php echo nl2br($this->contact->mobile); ?></div>
<?php endif; ?>
<?php if ( $this->contact->skype && $this->params->get('show_skype')) { ?>
<div id="contact-skype" class="contact-other"><?php echo $mc . $this->params->get( 'marker_skype' ); ?></div>
	<?php if($this->params->get('show_skype') == 1) { 
		echo $this->contact->skype;
	} else {?>
		<a href="skype:<?php echo $this->contact->skype; ?>?call"><?php echo $this->contact->skype; ?></a>
	<?php } ?>
</div>
<?php } ?>

<?php if ( $this->contact->yahoo_msg && $this->params->get( 'show_yahoo' )) : ?>
<div id="contact-yahoo" class="contact-other"><?php echo $mc . $this->params->get( 'marker_yahoo' ); ?></div>
	<?php if($this->params->get( 'show_yahoo' ) == 1) { 
		echo $this->contact->yahoo_msg;
	} else { ?>
		<a href="http://messenger.yahoo.com/edit/send/?.target=<?php echo $this->contact->yahoo_msg; ?>"><?php echo $this->contact->yahoo_msg; ?></a>
	<?php } ?>
</div>
<?php endif; ?>

<?php if ( $this->contact->webpage && $this->params->get('show_webpage')) { ?>
<div id="contact-website" class="contact-other"><?php echo $mc . $this->params->get( 'marker_web' ); ?></div>
<a href="<?php echo $this->contact->webpage; ?>" target="_blank">
	<?php echo $this->contact->webpage; ?></a></div>

<?php } ?>
<?php endif; ?>

<?php if ( $this->contact->misc && $this->params->get('show_misc') ) : ?>
<div id="contact-misc" class="contact-other"><?php echo $mc . $this->params->get('marker_misc'); ?></div>
<?php echo $this->contact->misc; ?></div>
<?php endif; ?>

<div id="contact-team" class="contact-other"><?php echo $mc . $this->params->get('marker_misc'); ?></div>
<a href="https://www.nfsda.org/list-of-teams/<?php echo $this->contact->catid; ?>-members-team-<?php echo $this->contact->team; ?>">Team Group <?php echo $this->contact->team; ?></a></div>
