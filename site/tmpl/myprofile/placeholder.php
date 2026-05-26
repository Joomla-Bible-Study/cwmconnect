<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Cwmconnect\Site\View\Myprofile\HtmlView $this */

$adminLink = $this->adminEmail !== ''
    ? '<a href="mailto:' . htmlspecialchars($this->adminEmail, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($this->adminEmail, ENT_QUOTES, 'UTF-8') . '</a>'
    : Text::_('COM_CWMCONNECT_MYPROFILE_PLACEHOLDER_ADMIN_FALLBACK');
?>
<div class="com-cwmconnect-myprofile com-cwmconnect-myprofile--unpaired">
	<h1><?php echo Text::_('COM_CWMCONNECT_MYPROFILE_HEADING'); ?></h1>

	<div class="alert alert-info" role="status">
		<?php echo Text::sprintf('COM_CWMCONNECT_MYPROFILE_PLACEHOLDER_BODY', $adminLink); ?>
	</div>
</div>
