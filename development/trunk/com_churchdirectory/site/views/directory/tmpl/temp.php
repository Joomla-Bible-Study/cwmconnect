<?php

/**
 * @package		ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
if (($this->params->get('address_check') > 0) && ($item->address || $item->suburb || $item->state || $item->country || $item->postcode)) :
    ?>
    <div class="churchdirectory-address">
        <?php if ($this->params->get('address_check') > 0) : ?>
            <span class="<?php echo $this->params->get('marker_class'); ?>" >
                <?php echo $this->params->get('marker_address'); ?>
            </span>
            <address>
                <?php
            endif;
            if ($item->address && $this->params->get('dr_show_street_address')) :
                ?>
                <span class="churchdirectory-street">
                    <?php echo $item->address . '<br />'; ?>
                </span>
            <?php endif; ?>
            <?php if ($item->suburb && $this->params->get('show_suburb')) : ?>

                <?php echo $item->suburb; ?>
                <?php
                if ($this->params->get('dr_show_state') || $this->params->get('dr_show_postcode')) :
                    echo ', ';
                    if ($item->state && $this->params->get('show_state')) :
                        ?>
                        <?php echo $item->state . ' '; ?>
                    <?php endif; ?>
                    <?php if ($item->postcode && $this->params->get('dr_show_postcode')) { ?>
                        <span class="churchdirectory-postcode">
                            <?php echo $item->postcode; ?>
                        </span>
                        <?php
                        if ($item->postcodeaddon == NULL) {
                            echo '<br /><br />';
                        } else {
                            echo '-' . $item->postcodeaddon . '(Please rap into postcode now)<br /><br />';
                        }
                    } else {
                        ?>
                        <br />
                    <?php } ?>
                    <?php
                endif;
            endif;
            ?>
    </div>
<?php endif; ?>

<?php if ($this->params->get('address_check') > 0) : ?>
    </address>
    </div>
    <?php
endif;
?>
