<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Service\HTML;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * HTML helper that builds the inline "Geo-update this row" link rendered
 * inside the geostatus list view's per-row dropdown.
 *
 * Registered as `churchdirectory.geoupdate.update`.
 *
 * @since  2.0.0
 */
class Geoupdate
{
    /**
     * Render an anchor that runs the geocode worker for a single row in a
     * Bootstrap modal popup.
     *
     * @param   int     $id          Member id.
     * @param   string  $customLink  Custom link prefix (rarely used).
     *
     * @return  string  The rendered anchor markup.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function update(int $id, string $customLink = ''): string
    {
        $base = $customLink !== '' ? $customLink : 'index.php?option=com_churchdirectory';
        $href = Route::_($base . '&task=geoupdate.browse&id=' . (int) $id . '&tmpl=component', false);

        return sprintf(
            '<a class="dropdown-item" href="%s" data-bs-toggle="modal" data-bs-target="#geoupdateModal">%s</a>',
            htmlspecialchars($href, ENT_QUOTES),
            Text::_('COM_CHURCHDIRECTORY_GEOUPDATE')
        );
    }
}
