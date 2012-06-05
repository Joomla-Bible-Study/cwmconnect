<?php
/**
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/* marker_class: Class based on the selection of text, none, or icons
 * jicon-text, jicon-none, jicon-icon
 */
?>
<?php if ($this->member->attribs->get('mailingaddress') || $this->member->attribs->get('mailingsuburb') || $this->member->attribs->get('mailingstate')): ?>
    <div class="churchdirectory-address">
        <?php if ($this->params->get('address_check') > 0) : ?>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_address'); ?>
            </span>
        <?php endif; ?>
        <?php if ($this->member->address && $this->params->get('show_street_address')) : ?>
            <span class="churchdirectory-street">
                <?php echo nl2br($this->member->attribs->get('mailingaddress')); ?>
            </span>
        <?php endif; ?>
        <?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
            <span class="churchdirectory-suburb">
                <?php echo $this->member->attribs->get('mailingsuburb'); ?>
            </span>
        <?php endif; ?>
        <?php if ($this->member->state && $this->params->get('show_state')) : ?>
            <span class="churchdirectory-state">
                <?php echo $this->member->attribs->get('mailingstate'); ?>
            </span>
        <?php endif; ?>
        <?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
            <span class="churchdirectory-postcode">
                <?php echo $this->member->attribs->get('mailingpostcode'); ?>
            </span>
        <?php endif; ?>
        <?php if ($this->member->country && $this->params->get('show_country')) : ?>
            <span class="churchdirectory-country">
                <?php echo $this->member->attribs->get('mailingcountry'); ?>
            </span>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php if (($this->params->get('address_check') > 0) && ($this->member->address || $this->member->suburb || $this->member->state || $this->member->country || $this->member->postcode)) : ?>
        <div class="churchdirectory-address">
            <?php if ($this->params->get('address_check') > 0) : ?>
                <span class="<?php echo $this->params->get('marker_class'); ?>" >
                    <?php echo $this->params->get('marker_address'); ?>
                </span>
                <address>
                <?php endif; ?>
                <?php if ($this->member->address && $this->params->get('show_street_address')) : ?>
                    <span class="churchdirectory-street">
                        <?php echo nl2br($this->member->address); ?>
                    </span>
                <?php endif; ?>
                <?php if ($this->member->suburb && $this->params->get('show_suburb')) : ?>
                    <span class="churchdirectory-suburb">
                        <?php echo $this->member->suburb; ?>
                    </span>
                <?php endif; ?>
                <?php if ($this->member->state && $this->params->get('show_state')) : ?>
                    <span class="churchdirectory-state">
                        <?php echo $this->member->state; ?>
                    </span>
                <?php endif; ?>
                <?php if ($this->member->postcode && $this->params->get('show_postcode')) : ?>
                    <span class="churchdirectory-postcode">
                        <?php echo $this->member->postcode; ?>
                    </span>
                <?php endif; ?>
                <?php if ($this->member->country && $this->params->get('show_country')) : ?>
                    <span class="churchdirectory-country">
                        <?php echo $this->member->country; ?>
                    </span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($this->params->get('address_check') > 0) : ?>
            </address>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($this->params->get('show_email') || $this->params->get('show_telephone') || $this->params->get('show_fax') || $this->params->get('show_mobile') || $this->params->get('show_webpage')) : ?>
    <div class="churchdirectory-churchdirectoryinfo">
    <?php endif; ?>
    <?php if ($this->member->email_to && $this->params->get('show_email')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_email'); ?>
            </span>
            <span class="churchdirectory-emailto">
                <?php echo $this->member->email_to; ?>
            </span>
        </p>
    <?php endif; ?>

    <?php if ($this->member->telephone && $this->params->get('show_telephone')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_telephone'); ?>
            </span>
            <span class="churchdirectory-telephone">
                <?php echo nl2br($this->member->telephone); ?>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($this->member->fax && $this->params->get('show_fax')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_fax'); ?>
            </span>
            <span class="churchdirectory-fax">
                <?php echo nl2br($this->member->fax); ?>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($this->member->mobile && $this->params->get('show_mobile')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_mobile'); ?>
            </span>
            <span class="churchdirectory-mobile">
                <?php echo nl2br($this->member->mobile); ?>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($this->member->webpage && $this->params->get('show_webpage')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
            </span>
            <span class="churchdirectory-webpage">
                <a href="<?php echo $this->member->webpage; ?>" target="_blank">
                    <?php echo $this->member->webpage; ?></a>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($this->params->get('show_email') || $this->params->get('show_telephone') || $this->params->get('show_fax') || $this->params->get('show_mobile') || $this->params->get('show_webpage')) : ?>
    </div>
<?php endif; ?>
