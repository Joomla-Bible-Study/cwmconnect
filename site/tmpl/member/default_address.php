<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Site\Helper\RenderHelper;
use CWM\Component\Connect\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

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

					<span class="cwmconnect-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->attribs->get('mailingaddress')) . ', <br />'; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
					<span class="cwmconnect-suburb" itemprop="addressLocality">
                        <?php echo $this->member->attribs->get('mailingsuburb') . ', '; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->state && $this->params->get('show_state')) : ?>
					<span class="cwmconnect-state" itemprop="addressRegion">
                        <?php echo $this->member->attribs->get('mailingstate'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
					<span class="cwmconnect-postcode" itemprop="postalCode">
                        <?php echo $this->member->attribs->get('mailingpostcode'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->country && $this->params->get('show_country')) : ?>
					<span class="cwmconnect-country" itemprop="addressCountry">
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

					<span class="cwmconnect-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->address) . ', <br />'; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
					<span class="cwmconnect-suburb" itemprop="addressLocality">
                        <?php echo $this->member->suburb . ', '; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->state && $this->params->get('show_state')) : ?>
					<span class="cwmconnect-state" itemprop="addressRegion">
                        <?php echo $this->member->state; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
					<span class="cwmconnect-postcode" itemprop="postalCode">
                        <?php echo $this->member->postcode; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->country && $this->params->get('show_country')) : ?>
					<span class="cwmconnect-country" itemprop="addressCountry">
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

					<span class="cwmconnect-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->attribs->get('mailingaddress')) . ', <br />'; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
					<span class="cwmconnect-suburb" itemprop="addressLocality">
                        <?php echo $this->member->attribs->get('mailingsuburb') . ', '; ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->state && $this->params->get('show_state')) : ?>
					<span class="cwmconnect-state" itemprop="addressRegion">
                        <?php echo $this->member->attribs->get('mailingstate'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
					<span class="cwmconnect-postcode" itemprop="postalCode">
                        <?php echo $this->member->attribs->get('mailingpostcode'); ?>
                    </span>
				<?php endif; ?>
				<?php if ($this->member->country && $this->params->get('show_country')) : ?>
					<span class="cwmconnect-country" itemprop="addressCountry">
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
						<span class="cwmconnect-street" itemprop="streetAddress">
                        <?php echo nl2br($this->member->address) . ', <br />'; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
						<span class="cwmconnect-suburb" itemprop="addressLocality">
                        <?php echo $this->member->suburb . ', '; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->state && $this->params->get('show_state')) : ?>
						<span class="cwmconnect-state" itemprop="addressRegion">
                        <?php echo $this->member->state; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
						<span class="cwmconnect-postcode" itemprop="postalCode">
                        <?php echo $this->member->postcode; ?>
                    </span>
					<?php endif; ?>
					<?php if ($this->member->country && $this->params->get('show_country')) : ?>
						<span class="cwmconnect-country" itemprop="addressCountry">
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
            <span class="cwmconnect-emailto">
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
            <span class="cwmconnect-telephone" itemprop="telephone">
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
            <span class="cwmconnect-fax" itemprop="faxNumber">
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
            <span class="cwmconnect-mobile" itemprop="telephone">
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
            <span class="cwmconnect-webpage">
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
