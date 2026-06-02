<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Service;

use Joomla\CMS\Categories\Categories;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Category tree for com_cwmconnect.
 *
 * The core `CategoryFactory` resolves `{namespace}\Site\Service\Category`, so
 * this class must exist for `Categories::getInstance('Cwmconnect')` (used by
 * the categories views) to return a tree rather than null. Maps the category
 * counts to the member table.
 *
 * @since  __DEPLOY_VERSION__
 */
class Category extends Categories
{
    /**
     * @param   array  $options  Category tree options.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct($options = [])
    {
        $options['table']     = '#__cwmconnect_details';
        $options['extension'] = 'com_cwmconnect';

        parent::__construct($options);
    }
}
