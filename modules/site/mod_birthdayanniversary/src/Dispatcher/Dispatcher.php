<?php

/**
 * @package    Mod_Birthdayanniversary
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Module\Birthdayanniversary\Site\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Dispatcher for mod_birthdayanniversary — runs the birthday + anniversary
 * lookups via the module helper, then renders the layout.
 *
 * @since  2.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Resolve the data the layout needs (birthdays + anniversaries for the
     * configured month) and ensure the component's stylesheet trio is loaded.
     *
     * @return  array<string, mixed>
     *
     * @since   2.0.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        // Load the component's shared stylesheets — the legacy module did the
        // same via addCSS(). Each call is no-op-safe if already enqueued.
        HTMLHelper::_('stylesheet', 'media/com_churchdirectory/css/general.css');
        HTMLHelper::_('stylesheet', 'media/com_churchdirectory/css/model.css');
        HTMLHelper::_('stylesheet', 'media/com_churchdirectory/css/icons.css');

        $helper = $this->getHelperFactory()->getHelper('BirthdayanniversaryHelper');

        $data['birthdays']   = $helper->getBirthdays($data['params']);
        $data['anniversary'] = $helper->getAnniversary($data['params']);

        return $data;
    }
}
