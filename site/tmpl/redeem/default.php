<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Cwmconnect\Site\View\Redeem\HtmlView $this */
?>
<div class="cwm-redeem">
	<h1><?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_HEADING'); ?></h1>

	<?php if ($this->alreadyIn) : ?>
		<div class="alert alert-success" role="status">
			<?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_ALREADY'); ?>
		</div>
		<a class="btn btn-primary"
		   href="<?php echo Route::_('index.php?option=com_cwmconnect&view=members'); ?>">
			<?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_GO_TO_DIRECTORY'); ?>
		</a>

	<?php elseif (!$this->loggedIn) : ?>
		<div class="alert alert-info" role="status">
			<?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_SIGN_IN_FIRST'); ?>
		</div>

	<?php elseif (!$this->enabled) : ?>
		<div class="alert alert-warning" role="status">
			<?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_UNAVAILABLE'); ?>
		</div>

	<?php else : ?>
		<p><?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_INTRO'); ?></p>

		<form action="<?php echo Route::_('index.php?option=com_cwmconnect&task=redeem.submit'); ?>"
		      method="post" class="form-validate">
			<div class="mb-3">
				<label class="form-label" for="access_code">
					<?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_LABEL_FIELD'); ?>
				</label>
				<input type="text" name="access_code" id="access_code"
				       class="form-control" required autocomplete="off"
				       autocapitalize="characters" spellcheck="false"
				       placeholder="<?php echo $this->escape(Text::_('COM_CWMCONNECT_ACCESS_CODE_PLACEHOLDER')); ?>">
				<div class="form-text"><?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_HINT'); ?></div>
			</div>

			<button type="submit" class="btn btn-primary">
				<?php echo Text::_('COM_CWMCONNECT_ACCESS_CODE_SUBMIT'); ?>
			</button>

			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>
</div>
