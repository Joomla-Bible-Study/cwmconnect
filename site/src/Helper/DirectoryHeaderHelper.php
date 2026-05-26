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

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Renders the configured header/footer blocks for the rendered directory PDF/HTML.
 *
 * Stateful — call {@see setPages()} once and read the populated arrays.
 *
 * @since  2.0.0
 */
class DirectoryHeaderHelper
{
    /** @var array<int, \stdClass> Header HTML blocks keyed by row id. */
    public array $header = [];

    /** @var array<int, \stdClass> Footer HTML blocks keyed by row id. */
    public array $footer = [];

    /**
     * Load every published dirheader row and split it into the header / footer
     * bucket based on the `section` column.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function setPages(): void
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select('a.*')
            ->from($db->quoteName('#__cwmconnect_dirheader', 'a'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('a.ordering') . ' ASC');

        $result = $db->setQuery($query)->loadObjectList();

        foreach ($result as $b) {
            $entry       = new \stdClass();
            $entry->html = '<div class="headerpage"><h2>' . $b->name . '</h2>' . $b->description . '</div>';
            $entry->name = $b->name;

            if ((string) $b->section === '1') {
                $this->footer[(int) $b->id] = $entry;
            } else {
                $this->header[(int) $b->id] = $entry;
            }
        }
    }
}
