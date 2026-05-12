<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Service\HTML;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;

/**
 * Bootstrap-style helper to register the bundled jscolor library on demand.
 *
 * Registered as `churchdirectory.colorpicker.framework`.
 *
 * @since  2.0.0
 */
class Colorpicker
{
    /**
     * Loaded-script flag, indexed by method name.
     *
     * @var array<string, bool>
     * @since 2.0.0
     */
    protected static array $loaded = [];

    /**
     * Load the jscolor framework into the current document head exactly once.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function framework(): void
    {
        if (!empty(self::$loaded[__METHOD__])) {
            return;
        }

        HTMLHelper::_('script', 'media/com_churchdirectory/js/jscolor.min.js', ['version' => 'auto']);
        self::$loaded[__METHOD__] = true;
    }
}
