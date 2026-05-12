<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Administrator\Service\HTML\Colorpicker;
use CWM\Component\Churchdirectory\Administrator\Service\HTML\Geoupdate;
use CWM\Component\Churchdirectory\Administrator\Service\HTML\Member;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Psr\Container\ContainerInterface;

/**
 * Component class for com_churchdirectory.
 *
 * @since  2.0.0
 */
class ChurchdirectoryComponent extends MVCComponent implements
    BootableExtensionInterface,
    CategoryServiceInterface,
    RouterServiceInterface
{
    use CategoryServiceTrait;
    use RouterServiceTrait;

    public const int MIN_PHP_VERSION_ID = 80300;
    public const string MIN_PHP_VERSION = '8.3.0';
    public const string MIN_JOOMLA_VERSION = '5.0.0';

    /**
     * Boot the extension. Registers the component's HTMLHelper services so
     * `HTMLHelper::_('churchdirectory.foo.bar', ...)` resolves to the
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

        $registry->register('churchdirectory.colorpicker', new Colorpicker());
        $registry->register('churchdirectory.geoupdate',   new Geoupdate());
        $registry->register('churchdirectory.member',      new Member());
    }
}
