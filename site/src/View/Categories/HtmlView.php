<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Categories;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\View\CategoriesView;

/**
 * Site categories view — renders the directory's category tree.
 *
 * @since  2.0.0
 */
class HtmlView extends CategoriesView
{
    /** @var string Default page-heading language key. */
    protected $pageHeading = 'COM_CWMCONNECT_DEFAULT_PAGE_TITLE';

    /** @var string Extension name used by CategoriesView to resolve options. */
    protected $extension = 'com_cwmconnect';
}
