<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\View\Directory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Stub XML export view.
 *
 * The legacy site/views/directory/view.xml.php was a 439-line hand-rolled
 * address-book exporter (vCard-flavored XML). It hasn't been ported to PSR-4
 * yet — both because the output format is fragile and because there's no
 * evidence in this codebase that it's currently consumed by anything.
 *
 * Returns a 503 so the route doesn't silently render an empty document. When
 * a real use case surfaces, port the legacy class into this stub.
 *
 * @since  2.0.0
 */
class XmlView extends BaseHtmlView
{
    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        throw new \Exception(Text::_('COM_CWMCONNECT_XML_EXPORT_NOT_IMPLEMENTED'), 503);
    }
}
