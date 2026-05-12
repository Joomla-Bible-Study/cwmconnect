<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\HTML\Colorpicker;
use CWM\Component\Cwmconnect\Administrator\Service\HTML\Member;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;

/**
 * Component class for com_cwmconnect.
 *
 * @since  2.0.0
 */
class CwmconnectComponent extends MVCComponent implements
    BootableExtensionInterface,
    CategoryServiceInterface,
    RouterServiceInterface
{
    use CategoryServiceTrait;
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;

    public const int MIN_PHP_VERSION_ID = 80400;
    public const string MIN_PHP_VERSION = '8.4.0';
    public const string MIN_JOOMLA_VERSION = '5.0.0';

    /**
     * Boot the extension. Registers the component's HTMLHelper services so
     * `HTMLHelper::_('cwmconnect.foo.bar', ...)` resolves to the
     * matching method on the registered service object.
     *
     * @param   ContainerInterface  $container  The DI container.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function boot(ContainerInterface $container): void
    {
        $registry = $this->getRegistry();

        $registry->register('cwmconnect.colorpicker', new Colorpicker());
        $registry->register('cwmconnect.member',      new Member());
    }
}
