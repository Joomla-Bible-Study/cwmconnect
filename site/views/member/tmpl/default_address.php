<?php
/**
 * Sub view member for address
 *
 * @package             ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/* marker_class: Class based on the selection of text, none, or icons
 * jicon-text, jicon-none, jicon-icon
 */
?>
<dl class="contact-address dl-horizontal">
<?php if ($this->params->get('dr_show_address_full') === '1') : ?>
	<?php if ($this->member->attribs->get('mailingaddress') || $this->member->attribs->get('mailingsuburb') || $this->member->attribs->get('mailingstate')) : ?>
        <div class="churchdirectory-address">
            <?php if ($this->params->get('address_check') > 0) : ?>
            <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
	            <?php echo JText::_('COM_CHURCHDIRECTORY_MAILING_ADDRESS'); ?>
                </span>
            </dt>
			<?php endif; ?>
		<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>
			<dd>
            <span class="churchdirectory-street">
                        <?php echo nl2br($this->member->attribs->get('mailingaddress')); ?>
                    </span>
			<?php endif; ?>
			</dd>
		<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
			<dd>
            <span class="churchdirectory-suburb">
                        <?php echo $this->member->attribs->get('mailingsuburb'); ?>
                    </span>
			<?php endif; ?>
		<?php if ($this->member->state && $this->params->get('show_state')) : ?>
			<dd>
            <span class="churchdirectory-state">
                        <?php echo $this->member->attribs->get('mailingstate'); ?>
                    </span>
			<?php endif; ?>
			</dd>
		<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
			<dd>
            <span class="churchdirectory-postcode">
                        <?php echo $this->member->attribs->get('mailingpostcode'); ?>
                    </span>
			<?php endif; ?>
			</dd>
		<?php if ($this->member->country && $this->params->get('show_country')) : ?>
			<dd>
            <span class="churchdirectory-country">
                        <?php echo $this->member->attribs->get('mailingcountry'); ?>
                    </span>
			<?php endif; ?>
			</dd>

		<?php if ($this->params->get('address_check') > 0) : ?>
            </div>
        <?php endif; ?>
		<?php endif; ?>
	<?php if (($this->params->get('address_check') > 0) && ($this->member->address || $this->member->suburb || $this->member->state || $this->member->country || $this->member->postcode)) : ?>
        <div class="churchdirectory-address">
            <?php if ($this->params->get('address_check') > 0) : ?>
            <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
	            <?php echo JText::_('COM_CHURCHDIRECTORY_PHYSICAL_ADDRESS'); ?>
                </span>
            </dt>
			<?php endif; ?>
		<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>
            <dd>
            <span class="churchdirectory-street">
                        <?php echo nl2br($this->member->address); ?>
                    </span>
            </dd>
			<?php endif; ?>
		<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
			<dd>
            <span class="churchdirectory-suburb">
                        <?php echo $this->member->suburb; ?>
                    </span>
			<?php endif; ?>
			</dd>
		<?php if ($this->member->state && $this->params->get('show_state')) : ?>
            <dd>
            <span class="churchdirectory-state">
                        <?php echo $this->member->state; ?>
                    </span>
            </dd>
			<?php endif; ?>
		<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
            <dd>
            <span class="churchdirectory-postcode">
                        <?php echo $this->member->postcode; ?>
                    </span>
            </dd>
			<?php endif; ?>
		<?php if ($this->member->country && $this->params->get('show_country')) : ?>
            <dd>
            <span class="churchdirectory-country">
                        <?php echo $this->member->country; ?>
                    </span>
            </dd>
			<?php endif; ?>

		<?php if ($this->params->get('address_check') > 0) : ?>
            </div>
        <?php endif; ?>
		<?php endif; ?>
	<?php elseif ($this->params->get('show_address_full') != '1') : ?>
	<?php if ($this->member->attribs->get('mailingaddress') || $this->member->attribs->get('mailingsuburb') || $this->member->attribs->get('mailingstate')): ?>
        <div class="churchdirectory-address">
		<?php if ($this->params->get('address_check') > 0) : ?>
            <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
	            <?php echo JText::_('COM_CHURCHDIRECTORY_MAILING_ADDRESS'); ?>
                </span>
            </dt>
			<?php endif; ?>
		<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>
            <dd>
            <span class="churchdirectory-street">
                        <?php echo nl2br($this->member->attribs->get('mailingaddress')); ?>
                    </span>
            </dd>
			<?php endif; ?>
		<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
            <dd>
            <span class="churchdirectory-suburb">
                        <?php echo $this->member->attribs->get('mailingsuburb'); ?>
                    </span>
            </dd>
			<?php endif; ?>
		<?php if ($this->member->state && $this->params->get('show_state')) : ?>
            <dd>
            <span class="churchdirectory-state">
                        <?php echo $this->member->attribs->get('mailingstate'); ?>
                    </span>
            </dd>
			<?php endif; ?>
		<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
            <dd>
            <span class="churchdirectory-postcode">
                        <?php echo $this->member->attribs->get('mailingpostcode'); ?>
                    </span>
            </dd>
			<?php endif; ?>
		<?php if ($this->member->country && $this->params->get('show_country')) : ?>
            <dd>
            <span class="churchdirectory-country">
                        <?php echo $this->member->attribs->get('mailingcountry'); ?>
                    </span>
            </dd>
			<?php endif; ?>

		<?php if ($this->params->get('address_check') > 0) : ?>
            </div>
        <?php endif; ?>
		<?php else: ?>
		<?php if (($this->params->get('address_check') > 0) && ($this->member->address || $this->member->suburb || $this->member->state || $this->member->country || $this->member->postcode)) : ?>
        <div class="churchdirectory-address">
            <?php if ($this->params->get('address_check') > 0) : ?>
                <dt>
                <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
	                <?php echo JText::_('COM_CHURCHDIRECTORY_PHYSICAL_ADDRESS'); ?>
                </span>
                </dt>
				<?php endif; ?>

			<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>
                <dd>
                <span class="churchdirectory-street">
                        <?php echo nl2br($this->member->address); ?>
                    </span>
                </dd>
				<?php endif; ?>

			<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
                <dd>
                <span class="churchdirectory-suburb">
                        <?php echo $this->member->suburb; ?>
                    </span>
                </dd>
				<?php endif; ?>

			<?php if ($this->member->state && $this->params->get('show_state')) : ?>
                <dd>
                <span class="churchdirectory-state">
                        <?php echo $this->member->state; ?>
                    </span>
                </dd>
				<?php endif; ?>

			<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
                <dd>
                <span class="churchdirectory-postcode">
                        <?php echo $this->member->postcode; ?>
                    </span>
                </dd>
				<?php endif; ?>

			<?php if ($this->member->country && $this->params->get('show_country')) : ?>
                <dd>
                <span class="churchdirectory-country">
                        <?php echo $this->member->country; ?>
                    </span>
                </dd>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>

<?php if ($this->params->get('show_email') || $this->params->get('show_telephone') || $this->params->get('show_fax') || $this->params->get('show_mobile') || $this->params->get('show_webpage')) : ?>
    <div class="churchdirectory-churchdirectoryinfo">
    <?php endif; ?>
<?php if ($this->member->email_to && $this->params->get('show_email')) : ?>
    <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                <?php echo $this->params->get('marker_email'); ?>
            </span>
    </dt>
    <dd>
            <span class="churchdirectory-emailto">
                <?php echo $this->member->email_to; ?>
            </span>
    </dd>
	<?php endif; ?>

<?php if ($this->member->telephone && $this->params->get('show_telephone')) : ?>
    <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                <?php echo $this->params->get('marker_telephone'); ?>
            </span>
    </dt>
    <dd>
            <span class="churchdirectory-telephone">
                <?php echo nl2br($this->member->telephone); ?>
            </span>
    </dd>
	<?php endif; ?>
<?php if ($this->member->fax && $this->params->get('show_fax')) : ?>
    <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                <?php echo $this->params->get('marker_fax'); ?>
            </span>
    </dt>
    <dd>
            <span class="churchdirectory-fax">
                <?php echo nl2br($this->member->fax); ?>
            </span>
    </dd>
	<?php endif; ?>
<?php if ($this->member->mobile && $this->params->get('show_mobile')) : ?>
    <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                <?php echo $this->params->get('marker_mobile'); ?>
            </span>
    </dt>
    <dd>
            <span class="churchdirectory-mobile">
                <?php echo nl2br($this->member->mobile); ?>
            </span>
    </dd>
	<?php endif; ?>
<?php if ($this->member->webpage && $this->params->get('show_webpage')) : ?>
    <dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
            </span>
    </dt>
    <dd>
            <span class="churchdirectory-webpage">
                <a href="<?php echo $this->member->webpage; ?>" target="_blank">
	                <?php echo $this->member->webpage; ?></a>
            </span>
    </dd>
	<?php endif; ?>
<?php if ($this->params->get('show_email') || $this->params->get('show_telephone') || $this->params->get('show_fax') || $this->params->get('show_mobile') || $this->params->get('show_webpage')) : ?>
    </div>
<?php endif; ?>
</dl>
