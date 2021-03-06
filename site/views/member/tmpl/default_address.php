<?php
/**
 * Sub view member for address
 *
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/* marker_class: Class based on the selection of text, none, or icons
 * jicon-text, jicon-none, jicon-icon
 */
?>
<dl class="contact-address dl-horizontal" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
	<?php if ($this->params->get('dr_show_address_full') === '1') : ?>
		<?php if ($this->member->attribs->get('mailingaddress')
			|| $this->member->attribs->get('mailingsuburb')
			|| $this->member->attribs->get('mailingstate')
		) :
			?>
			<?php if ($this->params->get('address_check') > 0) : ?>
			<dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
                </span>
			</dt>
		<?php endif; ?>
			<dd>
				<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>

					<span class="churchdirectory-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->attribs->get('mailingaddress')) . ', <br />'; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
					<span class="churchdirectory-suburb" itemprop="addressLocality">
                        <?php echo $this->member->attribs->get('mailingsuburb') . ', '; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->state && $this->params->get('show_state')) : ?>
					<span class="churchdirectory-state" itemprop="addressRegion">
                        <?php echo $this->member->attribs->get('mailingstate'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
					<span class="churchdirectory-postcode" itemprop="postalCode">
                        <?php echo $this->member->attribs->get('mailingpostcode'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->country && $this->params->get('show_country')) : ?>
					<span class="churchdirectory-country" itemprop="addressCountry">
                        <?php echo $this->member->attribs->get('mailingcountry'); ?>
                    </span>
				<?php endif; ?>
			</dd>
		<?php endif; ?>
		<?php if (($this->params->get('address_check') > 0) && ($this->member->address || $this->member->suburb || $this->member->state || $this->member->country || $this->member->postcode)) : ?>
			<?php if ($this->params->get('address_check') > 0) : ?>
				<dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
                </span>
				</dt>
			<?php endif; ?>
			<dd>
				<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>

					<span class="churchdirectory-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->address) . ', <br />'; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
					<span class="churchdirectory-suburb" itemprop="addressLocality">
                        <?php echo $this->member->suburb . ', '; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->state && $this->params->get('show_state')) : ?>
					<span class="churchdirectory-state" itemprop="addressRegion">
                        <?php echo $this->member->state; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
					<span class="churchdirectory-postcode" itemprop="postalCode">
                        <?php echo $this->member->postcode; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->country && $this->params->get('show_country')) : ?>
					<span class="churchdirectory-country" itemprop="addressCountry">
                        <?php echo $this->member->country; ?>
                    </span>
				<?php endif; ?>
			</dd>
		<?php endif; ?>
	<?php elseif ($this->params->get('show_address_full') != '1') : ?>
		<?php if ($this->member->attribs->get('mailingaddress') || $this->member->attribs->get('mailingsuburb') || $this->member->attribs->get('mailingstate')): ?>
			<?php if ($this->params->get('address_check') > 0) : ?>
				<dt>
            <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
                </span>
				</dt>
			<?php endif; ?>
			<dd>
				<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>

					<span class="churchdirectory-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->attribs->get('mailingaddress')) . ', <br />'; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
					<span class="churchdirectory-suburb" itemprop="addressLocality">
                        <?php echo $this->member->attribs->get('mailingsuburb') . ', '; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->state && $this->params->get('show_state')) : ?>
					<span class="churchdirectory-state" itemprop="addressRegion">
                        <?php echo $this->member->attribs->get('mailingstate'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
					<span class="churchdirectory-postcode" itemprop="postalCode">
                        <?php echo $this->member->attribs->get('mailingpostcode'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->country && $this->params->get('show_country')) : ?>
					<span class="churchdirectory-country" itemprop="addressCountry">
                        <?php echo $this->member->attribs->get('mailingcountry'); ?>
                    </span>
				<?php endif; ?>
			</dd>
		<?php else: ?>
			<?php if (($this->params->get('address_check') > 0) && ($this->member->address || $this->member->suburb || $this->member->state || $this->member->country || $this->member->postcode)) : ?>
				<?php if ($this->params->get('address_check') > 0) : ?>
					<dt>
                <span class="<?php echo $this->params->get('marker_class'); ?>">
                    <?php echo $this->params->get('marker_address'); ?>
                </span>
					</dt>
				<?php endif; ?>
				<dd>
					<?php if ($this->member->address && $this->params->get('show_street_address')) : ?>
						<span class="churchdirectory-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->address) . ', <br />'; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
						<span class="churchdirectory-suburb" itemprop="addressLocality">
                        <?php echo $this->member->suburb . ', '; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->state && $this->params->get('show_state')) : ?>
						<span class="churchdirectory-state" itemprop="addressRegion">
                        <?php echo $this->member->state; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
						<span class="churchdirectory-postcode" itemprop="postalCode">
                        <?php echo $this->member->postcode; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->country && $this->params->get('show_country')) : ?>
						<span class="churchdirectory-country" itemprop="addressCountry">
                        <?php echo $this->member->country; ?>
                    </span>
					<?php endif; ?>
				</dd>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($this->params->get('show_email') || $this->params->get('show_telephone') || $this->params->get('show_fax') || $this->params->get('show_mobile') || $this->params->get('show_webpage')) : ?>
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
            <span class="churchdirectory-telephone" itemprop="telephone">
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
            <span class="churchdirectory-fax" itemprop="faxNumber">
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
            <span class="churchdirectory-mobile" itemprop="telephone">
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
                <?php if (substr_count($this->member->webpage, 'http://', 0))
                {
	                $a = '';
                }
                else
                {
	                $a = 'http://';
                } ?>
	            <a href="<?php echo $a . $this->member->webpage; ?>" target="_blank">
		            <?php echo JStringPunycode::urlToUTF8($this->member->webpage); ?></a>
            </span>
		</dd>
	<?php endif; ?>
</dl>
