<?php
/**
 * @version		$Id: default_address.php 71 $
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/* marker_class: Class based on the selection of text, none, or icons
 * jicon-text, jicon-none, jicon-icon
 */
dump($items, 'item');
?>
<?php if (($this->params->get('address_check') > 0)) : ?>
    <div class="churchdirectory-address">
        <?php if ($this->params->get('address_check') > 0) : ?>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_address'); ?>
            </span>
            <address>
            <?php endif; ?>
            <?php dump ($item->address, 'address'); if ($item->address && $this->params->get('dr_show_street_address')) : ?>
                <span class="churchdirectory-street">
                    <?php echo nl2br($item->address); ?>
                </span>
            <?php endif; ?>
            <?php if ($item->suburb && $this->params->get('dr_show_suburb')) : ?>
                <span class="churchdirectory-suburb">
                    <?php echo $item->suburb; ?>
                </span>
            <?php endif; ?>
            <?php if ($item->state && $this->params->get('dr_show_state')) : ?>
                <span class="churchdirectory-state">
                    <?php echo $item->state; ?>
                </span>
            <?php endif; ?>
            <?php if ($item->postcode && $this->params->get('dr_show_postcode')) : ?>
                <span class="churchdirectory-postcode">
                    <?php echo $item->postcode; ?>
                </span>
            <?php endif; ?>
            <?php if ($item->country && $this->params->get('dr_show_country')) : ?>
                <span class="churchdirectory-country">
                    <?php echo $item->country; ?>
                </span>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($this->params->get('address_check') > 0) : ?>
        </address>
    </div>
<?php endif; ?>

<?php if ($this->params->get('show_email') || $this->params->get('show_telephone') || $this->params->get('show_fax') || $this->params->get('show_mobile') || $this->params->get('show_webpage')) : ?>
    <div class="churchdirectory-churchdirectoryinfo">
    <?php endif; ?>
    <?php if ($item->email_to && $this->params->get('show_email')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_email'); ?>
            </span>
            <span class="churchdirectory-emailto">
                <?php echo $item->email_to; ?>
            </span>
        </p>
    <?php endif; ?>

    <?php if ($item->telephone && $this->params->get('show_telephone')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_telephone'); ?>
            </span>
            <span class="churchdirectory-telephone">
                <?php echo nl2br($item->telephone); ?>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($item->fax && $this->params->get('show_fax')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_fax'); ?>
            </span>
            <span class="churchdirectory-fax">
                <?php echo nl2br($item->fax); ?>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($item->mobile && $this->params->get('show_mobile')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_mobile'); ?>
            </span>
            <span class="churchdirectory-mobile">
                <?php echo nl2br($item->mobile); ?>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($item->webpage && $this->params->get('show_webpage')) : ?>
        <p>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
            </span>
            <span class="churchdirectory-webpage">
                <a href="<?php echo $item->webpage; ?>" target="_blank">
                    <?php echo $item->webpage; ?></a>
            </span>
        </p>
    <?php endif; ?>
    <?php if ($this->params->get('show_email') || $this->params->get('show_telephone') || $this->params->get('show_fax') || $this->params->get('show_mobile') || $this->params->get('show_webpage')) : ?>
    </div>
<?php endif; ?>