<?php

/**
 * @version		$Id: default_address.php 21097 2011-04-07 15:38:03Z dextercowley $
 * @package		Joomla.Site
 * @subpackage	com_churchdirectory
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/* marker_class: Class based on the selection of text, none, or icons
 * jicon-text, jicon-none, jicon-icon
 */
?>
<?php if (($this->params->get('address_check') > 0) &&  ($this->churchdirectory->address || $this->churchdirectory->suburb  || $this->churchdirectory->state || $this->churchdirectory->country || $this->churchdirectory->postcode)) : ?>
	<div class="churchdirectory-address">
	<?php if ($this->params->get('address_check') > 0) : ?>
		<span class="<?php echo $this->params->get('marker_class'); ?>" >
			<?php echo $this->params->get('marker_address'); ?>
		</span>
		<address>
	<?php endif; ?>
	<?php if ($this->churchdirectory->address && $this->params->get('show_street_address')) : ?>
		<span class="churchdirectory-street">
			<?php echo nl2br($this->churchdirectory->address); ?>
		</span>
	<?php endif; ?>
	<?php if ($this->churchdirectory->suburb && $this->params->get('show_suburb')) : ?>
		<span class="churchdirectory-suburb">
			<?php echo $this->churchdirectory->suburb; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->churchdirectory->state && $this->params->get('show_state')) : ?>
		<span class="churchdirectory-state">
			<?php echo $this->churchdirectory->state; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->churchdirectory->postcode && $this->params->get('show_postcode')) : ?>
		<span class="churchdirectory-postcode">
			<?php echo $this->churchdirectory->postcode; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->churchdirectory->country && $this->params->get('show_country')) : ?>
		<span class="churchdirectory-country">
			<?php echo $this->churchdirectory->country; ?>
		</span>
	<?php endif; ?>
<?php endif; ?>

<?php if ($this->params->get('address_check') > 0) : ?>
	</address>
	</div>
<?php endif; ?>

<?php if($this->params->get('show_email') || $this->params->get('show_telephone')||$this->params->get('show_fax')||$this->params->get('show_mobile')|| $this->params->get('show_webpage') ) : ?>
	<div class="churchdirectory-churchdirectoryinfo">
<?php endif; ?>
<?php if ($this->churchdirectory->email_to && $this->params->get('show_email')) : ?>
	<p>
		<span class="<?php echo $this->params->get('marker_class'); ?>" >
			<?php echo $this->params->get('marker_email'); ?>
		</span>
		<span class="churchdirectory-emailto">
			<?php echo $this->churchdirectory->email_to; ?>
		</span>
	</p>
<?php endif; ?>

<?php if ($this->churchdirectory->telephone && $this->params->get('show_telephone')) : ?>
	<p>
		<span class="<?php echo $this->params->get('marker_class'); ?>" >
			<?php echo $this->params->get('marker_telephone'); ?>
		</span>
		<span class="churchdirectory-telephone">
			<?php echo nl2br($this->churchdirectory->telephone); ?>
		</span>
	</p>
<?php endif; ?>
<?php if ($this->churchdirectory->fax && $this->params->get('show_fax')) : ?>
	<p>
		<span class="<?php echo $this->params->get('marker_class'); ?>" >
			<?php echo $this->params->get('marker_fax'); ?>
		</span>
		<span class="churchdirectory-fax">
		<?php echo nl2br($this->churchdirectory->fax); ?>
		</span>
	</p>
<?php endif; ?>
<?php if ($this->churchdirectory->mobile && $this->params->get('show_mobile')) :?>
	<p>
		<span class="<?php echo $this->params->get('marker_class'); ?>" >
			<?php echo $this->params->get('marker_mobile'); ?>
		</span>
		<span class="churchdirectory-mobile">
			<?php echo nl2br($this->churchdirectory->mobile); ?>
		</span>
	</p>
<?php endif; ?>
<?php if ($this->churchdirectory->webpage && $this->params->get('show_webpage')) : ?>
	<p>
		<span class="<?php echo $this->params->get('marker_class'); ?>" >
		</span>
		<span class="churchdirectory-webpage">
			<a href="<?php echo $this->churchdirectory->webpage; ?>" target="_blank">
			<?php echo $this->churchdirectory->webpage; ?></a>
		</span>
	</p>
<?php endif; ?>
<?php if($this->params->get('show_email') || $this->params->get('show_telephone')||$this->params->get('show_fax')||$this->params->get('show_mobile')|| $this->params->get('show_webpage') ) : ?>
	</div>
<?php endif; ?>
