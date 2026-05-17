<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Administrator\View\Member\HtmlView $this */

$input   = Factory::getApplication()->getInput();
$isModal = $input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = ($isModal || $input->get('tmpl', '', 'cmd') === 'component') ? '&tmpl=component' : '';

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');

// Hide the family-position attribute when the member has no family unit selected.
$this->getDocument()->addScriptDeclaration(<<<JS
    document.addEventListener('DOMContentLoaded', function () {
        var fu = document.getElementById('jform_funitid');
        var lbl = document.getElementById('jform_attribs_familypostion-lbl');
        var ctrl = document.getElementById('jform_attribs_familypostion');

        if (!fu) {
            return;
        }

        var sync = function () {
            var hidden = fu.value === '-1';
            if (lbl)  { lbl.style.display  = hidden ? 'none' : ''; }
            if (ctrl) { ctrl.style.display = hidden ? 'none' : ''; }
        };

        fu.addEventListener('change', sync);
        sync();
    });
    JS);

// Fieldsets that are rendered manually below.
$this->ignore_fieldsets = ['details', 'item_associations', 'jmetadata'];

// Phase F: "Synced from PC" banner data. Locks themselves are applied in MemberModel.
$pcPersonId   = (int) ($this->item->pc_person_id ?? 0);
$pcLastSynced = (string) ($this->item->pc_last_synced_at ?? '');
$pcProfileUrl = $pcPersonId > 0
    ? 'https://people.planningcenteronline.com/people/' . $pcPersonId
    : '';
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="member-form" class="form-validate">

    <?php if ($pcPersonId > 0) : ?>
        <div class="alert alert-info d-flex align-items-center" role="status">
            <span class="icon-link me-2" aria-hidden="true"></span>
            <div class="flex-grow-1">
                <strong><?php echo Text::_('COM_CWMCONNECT_PC_LOCK_BANNER_TITLE'); ?></strong>
                <?php echo Text::sprintf(
                    'COM_CWMCONNECT_PC_LOCK_BANNER_BODY',
                    $pcPersonId,
                    $this->escape($pcLastSynced !== '' ? $pcLastSynced : Text::_('JNEVER')),
                ); ?>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $this->escape($pcProfileUrl); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo Text::_('COM_CWMCONNECT_PC_LOCK_BANNER_VIEW_IN_PC'); ?>
                <span class="icon-out-2" aria-hidden="true"></span>
            </a>
        </div>
    <?php endif; ?>

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'memberTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'memberTab', 'details', empty($this->item->id) ? Text::_('COM_CWMCONNECT_NEW_MEMBER') : Text::sprintf('COM_CWMCONNECT_EDIT_MEMBER', $this->item->id)); ?>
        <div class="row">
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-md-6">
                        <?php echo $this->form->renderField('lname'); ?>
                        <?php echo $this->form->renderField('lat'); ?>
                        <?php echo $this->form->renderField('funitid'); ?>
                        <?php echo $this->form->renderField('familypostion', 'attribs'); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo $this->form->renderField('surname'); ?>
                        <?php echo $this->form->renderField('lng'); ?>
                        <?php echo $this->form->renderField('sex', 'attribs'); ?>
                        <?php echo $this->form->renderField('user_id'); ?>
                    </div>
                </div>

                <?php if ($this->access) : ?>
                    <h2><?php echo Text::_('COM_CWMCONNECT_PROTECTED_CONTENT'); ?></h2>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo $this->form->renderField('mstatus'); ?>
                        </div>
                        <div class="col-md-6">
                            <?php echo $this->form->renderField('bpc_date', 'attribs'); ?>
                            <?php echo $this->form->renderField('memberotherinfo', 'attribs'); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-3">
                <?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'memberTab', 'misc', Text::_('COM_CWMCONNECT_FIELD_PARAMS_MISC_INFO_LABEL')); ?>
        <?php echo $this->form->renderField('misc'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'memberTab', 'basic', Text::_('COM_CWMCONNECT_MEMBER_DETAILS')); ?>
        <p><?php echo empty($this->item->id) ? Text::_('COM_CWMCONNECT_DETAILS') : Text::sprintf('COM_CWMCONNECT_EDIT_DETAILS', $this->item->id); ?></p>

        <div class="row">
            <div class="col-md-6">
                <?php echo $this->form->renderField('image'); ?>
                <?php echo $this->form->renderField('con_position'); ?>
                <?php echo $this->form->renderField('email_to'); ?>
                <?php echo $this->form->renderField('address'); ?>
                <?php echo $this->form->renderField('suburb'); ?>
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->renderField('postcode'); ?>
                <?php echo $this->form->renderField('postcodeaddon'); ?>
                <?php echo $this->form->renderField('country'); ?>
                <?php echo $this->form->renderField('telephone'); ?>
                <?php echo $this->form->renderField('mobile'); ?>
                <?php echo $this->form->renderField('fax'); ?>
                <?php echo $this->form->renderField('webpage'); ?>
            </div>
            <div class="col-md-6">
                <?php
                $familypos = (int) $this->form->getValue('familypostion', 'attribs', 0);
if ($familypos !== 2) :
    echo $this->form->renderField('spouse');
    echo $this->form->renderField('children_listed');
endif;
?>
                <?php echo $this->form->renderField('children'); ?>
                <?php echo $this->form->renderField('sortname1'); ?>
                <?php echo $this->form->renderField('sortname2'); ?>
                <?php echo $this->form->renderField('sortname3'); ?>
                <?php echo $this->form->renderField('birthdate'); ?>
                <?php echo $this->form->renderField('anniversary'); ?>

                <?php if ((int) $this->age !== 0) : ?>
                    <div class="control-group">
                        <div class="control-label">
                            <label for="jform_age" id="jform_age-lbl">
                                <?php echo Text::_('COM_CWMCONNECT_AGE_LABEL'); ?>
                            </label>
                        </div>
                        <div class="controls">
                            <input type="text" name="jform[age]" id="jform_age"
                                   value="<?php echo (int) $this->age . ' ' . Text::_('COM_CWMCONNECT_YEARS_OLD'); ?>"
                                   class="readonly" size="10" readonly>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo LayoutHelper::render('joomla.edit.params', $this); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'memberTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <div class="row">
            <div class="col-md-6">
                <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
            </div>
            <div class="col-md-6">
                <?php echo LayoutHelper::render('joomla.edit.metadata', $this); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="view" value="member">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
