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

use CWM\Component\Cwmconnect\Site\Helper\Layout;
use CWM\Component\Cwmconnect\Site\Helper\RenderHelper;
use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Member\HtmlView $this */

$member       = $this->member;
$params       = $this->params;
$render       = new RenderHelper();
$hasPhoto     = !empty($member->image) && $params->get('show_image');
$position     = $member->con_position && $params->get('show_position')
    ? $render->getPosition($member->con_position) : '';
$familyPos    = $member->attribs->get('familypostion');
$spouse       = $familyPos != '2' ? $render->getSpouse((int) $member->fu_id, (int) $familyPos) : '';
$children     = ($params->get('dr_show_children') && $familyPos != '2')
    ? $render->getChildren((int) $member->fu_id, false, $member->children) : '';
$categoryUrl  = (int) $member->catid > 0 ? RouteHelper::getCategoryRoute($member->catid) : '';
$hasEmailForm = $params->get('show_email_form') && !empty($member->email_to);
?>
<div class="cwmconnect-member container-fluid px-0">

    <?php // Hero header?>
    <div class="card shadow-sm mb-4 cwm-member-hero">
        <div class="card-body">
            <a class="small text-decoration-none d-inline-block mb-3" href="<?php echo Route::_('index.php?option=com_cwmconnect&view=members'); ?>">
                <span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_HOME_BROWSE_DIRECTORY'); ?>
            </a>

            <div class="row g-4 align-items-center">
                <?php if ($hasPhoto) : ?>
                    <div class="col-12 col-md-auto text-center">
                        <?php echo Layout::render('photo', [
                            'id'       => (int) $member->id,
                            'size'     => 'medium',
                            'hasPhoto' => true,
                            'alt'      => $member->name,
                            'class'    => 'rounded shadow-sm',
                            'sizes'    => '200px',
                            'width'    => 200,
                            'height'   => 267,
                            'linkFull' => true,
                        ]); ?>
                    </div>
                <?php endif; ?>

                <div class="col">
                    <?php if ($params->get('show_name')) : ?>
                        <h1 class="h2 mb-1">
                            <?php echo $this->escape($member->name); ?>
                            <?php if ((int) $this->item->published === 0) : ?>
                                <span class="badge bg-warning text-dark align-middle"><?php echo Text::_('JUNPUBLISHED'); ?></span>
                            <?php endif; ?>
                        </h1>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php if ($position !== '') : ?>
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"><?php echo $this->escape($position); ?></span>
                        <?php endif; ?>
                        <?php if ($member->category_title && $params->get('show_contact_category') && (int) $member->catid > 0) : ?>
                            <a class="badge rounded-pill bg-body-secondary text-body-secondary text-decoration-none" href="<?php echo $categoryUrl; ?>">
                                <?php echo $this->escape($member->category_title); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($member->fu_name)) : ?>
                            <span class="badge rounded-pill bg-body-secondary text-body-secondary">
                                <span class="icon-users" aria-hidden="true"></span> <?php echo $this->escape($member->fu_name); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($spouse) : ?>
                        <div class="text-body-secondary"><strong><?php echo Text::_('COM_CWMCONNECT_SPOUSE'); ?>:</strong> <?php echo $spouse; ?></div>
                    <?php endif; ?>
                    <?php if ($children) : ?>
                        <div class="text-body-secondary"><?php echo $children; ?></div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php if ($hasEmailForm) : ?>
                            <a class="btn btn-primary btn-sm" href="#cwm-email-form">
                                <span class="icon-envelope" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_EMAIL_FORM'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($params->get('allow_vcard')) : ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo Route::_('index.php?option=com_cwmconnect&view=member&id=' . (int) $member->id . '&format=vcf'); ?>">
                                <span class="icon-vcard" aria-hidden="true"></span> <?php echo Text::_('COM_CWMCONNECT_VCARD'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php // Section cards?>
    <div class="row g-3">
        <?php
        $contact = trim($this->loadTemplate('address'));
if ($contact !== '') :
    ?>
            <div class="col-12 col-lg-6">
                <?php echo Layout::render('sectioncard', [
                    'title' => Text::_('COM_CWMCONNECT_DETAILS'),
                    'icon'  => 'icon-address',
                    'body'  => $contact,
                ]); ?>
            </div>
        <?php endif; ?>

        <?php
        $household = trim($this->loadTemplate('household'));
if ($household !== '') :
    ?>
            <div class="col-12 col-lg-6">
                <?php echo Layout::render('sectioncard', [
                    'title' => Text::_('COM_CWMCONNECT_MEMBER_HOUSEHOLD_HEADING'),
                    'icon'  => 'icon-users',
                    'body'  => $household,
                ]); ?>
            </div>
        <?php endif; ?>

        <?php if ($member->misc && $params->get('show_misc')) : ?>
            <div class="col-12 col-lg-6">
                <?php echo Layout::render('sectioncard', [
                    'title' => Text::_('COM_CWMCONNECT_OTHER_INFORMATION'),
                    'icon'  => 'icon-info-circle',
                    'body'  => '<div class="contact-misc">' . $member->misc . '</div>',
                ]); ?>
            </div>
        <?php endif; ?>

        <?php if ($params->get('show_links') && $member->params->get('linka') != null) : ?>
            <div class="col-12 col-lg-6">
                <?php echo Layout::render('sectioncard', [
                    'title' => Text::_('COM_CWMCONNECT_LINKS'),
                    'icon'  => 'icon-link',
                    'body'  => trim($this->loadTemplate('links')),
                ]); ?>
            </div>
        <?php endif; ?>

        <?php if ($params->get('show_articles') && $member->user_id && $member->articles) : ?>
            <div class="col-12 col-lg-6">
                <?php echo Layout::render('sectioncard', [
                    'title' => Text::_('JGLOBAL_ARTICLES'),
                    'icon'  => 'icon-file-alt',
                    'body'  => trim($this->loadTemplate('articles')),
                ]); ?>
            </div>
        <?php endif; ?>

        <?php if ($hasEmailForm) : ?>
            <div class="col-12" id="cwm-email-form">
                <?php echo Layout::render('sectioncard', [
                    'title' => Text::_('COM_CWMCONNECT_EMAIL_FORM'),
                    'icon'  => 'icon-envelope',
                    'body'  => trim($this->loadTemplate('form')),
                ]); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php echo $this->item->event->afterDisplayContent; ?>
</div>
