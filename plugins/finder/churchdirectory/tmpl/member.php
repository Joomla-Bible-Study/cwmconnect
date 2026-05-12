<?php

/**
 * @package    Plg_Finder_Churchdirectory
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$mime = !empty($this->result->mime) ? 'mime-' . $this->result->mime : null;
$base = Uri::getInstance()->toString(['scheme', 'host', 'port']);

if (
    !empty($this->query->highlight)
    && empty($this->result->mime)
    && $this->params->get('highlight_terms', 1)
    && PluginHelper::isEnabled('system', 'highlight')
) {
    $route = $this->result->route . '&highlight=' . base64_encode(serialize($this->query->highlight));
} else {
    $route = $this->result->route;
}
?>
<dt class="result-title <?php echo $mime; ?>">
    <a href="<?php echo Route::_($route); ?>">
        <?php echo $this->escape($this->result->title); ?>
    </a>
</dt>

<?php if ($this->params->get('show_description', 1)) : ?>
    <dd class="result-text<?php echo $this->pageclass_sfx; ?>">
        <?php echo HTMLHelper::_('string.truncate', $this->result->description, $this->params->get('description_length', 255)); ?>
    </dd>
<?php endif; ?>

<?php if ($this->params->get('show_url', 1)) : ?>
    <dd class="result-url<?php echo $this->pageclass_sfx; ?>">
        <?php echo $base . Route::_($this->result->route); ?>
    </dd>
<?php endif; ?>
