<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Administrator\View\Feedtoken\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_cwmconnect&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
      method="post" name="adminForm" id="feedtoken-form" class="form-validate">

    <div class="main-card">
        <div class="row">
            <div class="col-lg-6">
                <?php echo $this->form->renderField('label'); ?>
                <?php echo $this->form->renderField('user_id'); ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
