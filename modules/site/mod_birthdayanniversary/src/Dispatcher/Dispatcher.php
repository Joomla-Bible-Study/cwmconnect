<?php

/**
 * @package    Mod_Birthdayanniversary
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Module\Birthdayanniversary\Site\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

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
     * configured month) and enqueue the component's shared stylesheets via
     * the WebAssetManager (resolved through media/com_churchdirectory/
     * joomla.asset.json).
     *
     * @return  array<string, mixed>
     *
     * @since   2.0.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $this->getApplication()
            ->getDocument()
            ->getWebAssetManager()
            ->useStyle('com_churchdirectory.general')
            ->useStyle('com_churchdirectory.model');

        $helper = $this->getHelperFactory()->getHelper('BirthdayanniversaryHelper');

        $data['birthdays']   = $helper->getBirthdays($data['params']);
        $data['anniversary'] = $helper->getAnniversary($data['params']);

        return $data;
    }
}
