<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Categories\Categories;

/**
 * Categories tree helper for the directory component.
 *
 * @since  2.0.0
 */
class CategoriesHelper extends Categories
{
    /**
     * Wire the directory's table + extension into the Categories engine.
     *
     * @param   array<string, mixed>  $options  Override options forwarded to {@see Categories}.
     *
     * @since   2.0.0
     */
    public function __construct(array $options = [])
    {
        $options['table']      = '#__cwmconnect_details';
        $options['extension']  = 'com_cwmconnect';
        $options['statefield'] = 'published';

        parent::__construct($options);
    }
}
