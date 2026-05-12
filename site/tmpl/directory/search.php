<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Cwmconnect\Site\View\Directory\HtmlView $this */
?>
<?php echo $this->renderHelper->getSearchField($this->params); ?>

<div class="directory container">
    <h1><?php echo Text::_('COM_CWMCONNECT_SEARCH_RESULTS'); ?></h1>

    <?php if (!empty($this->items)) : ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo Text::_('COM_CWMCONNECT_MEMBER_EMAIL_NAME_LABEL'); ?></th>
                    <th><?php echo Text::_('COM_CWMCONNECT_PROFILE_LINK'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <tr>
                        <th scope="row"><?php echo $i + 1; ?></th>
                        <td><?php echo $this->escape($item->name); ?></td>
                        <td>
                            <a href="<?php echo Route::_(RouteHelper::getMemberRoute($item->slug, $item->catid)); ?>"
                               class="btn btn-info">
                                <?php echo Text::_('COM_CWMCONNECT_VIEW'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php echo Text::_('COM_CWMCONNECT_NO_SEARCH_RESULTS'); ?></p>
    <?php endif; ?>
</div>
