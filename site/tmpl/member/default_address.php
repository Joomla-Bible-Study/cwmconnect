<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\String\PunycodeHelper;

/** @var \CWM\Component\Cwmconnect\Site\View\Member\HtmlView $this */

$member = $this->member;
$params = $this->params;

// Prefer the admin-entered mailing address when "show full" is on and one
// exists, else the physical/synced address.
$useMailing = $params->get('dr_show_address_full') === '1'
    && ($member->attribs->get('mailingaddress') || $member->attribs->get('mailingsuburb') || $member->attribs->get('mailingstate'));

$street   = $useMailing ? $member->attribs->get('mailingaddress') : $member->address;
$suburb   = $useMailing ? $member->attribs->get('mailingsuburb') : $member->suburb;
$state    = $useMailing ? $member->attribs->get('mailingstate') : $member->state;
$postcode = $useMailing ? $member->attribs->get('mailingpostcode') : $member->postcode;
$country  = $useMailing ? $member->attribs->get('mailingcountry') : $member->country;

$addressParts = [];

if ($street && $params->get('show_street_address')) {
    $addressParts[] = '<span itemprop="streetAddress">' . nl2br($this->escape($street)) . '</span>';
}

$cityLine = [];

if ($suburb && $params->get('show_suburb')) {
    $cityLine[] = '<span itemprop="addressLocality">' . $this->escape($suburb) . '</span>';
}

if ($state && $params->get('show_state')) {
    $cityLine[] = '<span itemprop="addressRegion">' . $this->escape($state) . '</span>';
}

if ($postcode && $params->get('show_postcode')) {
    $cityLine[] = '<span itemprop="postalCode">' . $this->escape($postcode) . '</span>';
}

if ($cityLine !== []) {
    $addressParts[] = implode(' ', $cityLine);
}

if ($country && $params->get('show_country')) {
    $addressParts[] = '<span itemprop="addressCountry">' . $this->escape($country) . '</span>';
}

$webHref = '';

if ($member->webpage && $params->get('show_webpage')) {
    $webHref = preg_match('~^https?://~i', $member->webpage) ? $member->webpage : 'http://' . $member->webpage;
}
?>
<ul class="list-unstyled mb-0 cwm-contact" itemscope itemtype="https://schema.org/PostalAddress">
    <?php if ($addressParts !== []) : ?>
        <li class="d-flex gap-2 mb-2">
            <span class="icon-location text-primary mt-1" aria-hidden="true"></span>
            <span><?php echo implode('<br>', $addressParts); ?></span>
        </li>
    <?php endif; ?>

    <?php if ($member->email_to && $params->get('show_email')) : ?>
        <li class="d-flex gap-2 mb-2">
            <span class="icon-envelope text-primary mt-1" aria-hidden="true"></span>
            <span class="cwmconnect-emailto"><?php echo $member->email_to; // pre-cloaked HTML?></span>
        </li>
    <?php endif; ?>

    <?php if ($member->telephone && $params->get('show_telephone')) : ?>
        <li class="d-flex gap-2 mb-2">
            <span class="icon-phone text-primary mt-1" aria-hidden="true"></span>
            <span itemprop="telephone"><?php echo nl2br($this->escape($member->telephone)); ?></span>
        </li>
    <?php endif; ?>

    <?php if ($member->mobile && $params->get('show_mobile')) : ?>
        <li class="d-flex gap-2 mb-2">
            <span class="icon-mobile text-primary mt-1" aria-hidden="true"></span>
            <span itemprop="telephone"><?php echo nl2br($this->escape($member->mobile)); ?></span>
        </li>
    <?php endif; ?>

    <?php if ($member->fax && $params->get('show_fax')) : ?>
        <li class="d-flex gap-2 mb-2">
            <span class="icon-print text-primary mt-1" aria-hidden="true"></span>
            <span itemprop="faxNumber"><?php echo nl2br($this->escape($member->fax)); ?></span>
        </li>
    <?php endif; ?>

    <?php if ($webHref !== '') : ?>
        <li class="d-flex gap-2">
            <span class="icon-out-2 text-primary mt-1" aria-hidden="true"></span>
            <a href="<?php echo $this->escape($webHref); ?>" target="_blank" rel="noopener">
                <?php echo $this->escape(PunycodeHelper::urlToUTF8($member->webpage)); ?>
            </a>
        </li>
    <?php endif; ?>
</ul>
