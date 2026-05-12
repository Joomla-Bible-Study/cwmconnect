<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Directory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Helper\ReportbuildHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Registry\Registry;

/**
 * KML feed view for the full directory — streams Google-Earth placemarks for
 * every visible member with mappable coordinates.
 *
 * @since  2.0.0
 */
class KmlView extends BaseHtmlView
{
    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $model    = $this->getModel();
        $state    = $model->getState();
        $params   = $state->get('params');
        $items    = $model->getItems();
        $category = $model->getCategory();

        $user   = Factory::getApplication()->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [1];

        if ($category && !\in_array($category->access, $groups, false)) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }

        if (empty($items)) {
            echo Text::_('COM_CWMCONNECT_ERROR_DIRECTORY_NOT_FOUND');
            return;
        }

        foreach ($items as $item) {
            $item->slug  = $item->alias ? ($item->id . ':' . $item->alias) : (string) $item->id;
            $item->event = new \stdClass();

            $temp = new Registry();
            $temp->loadString((string) $item->params);
            $item->params = clone $params;
            $item->params->merge($temp);

            $catParams = new Registry();
            $catParams->loadString((string) $item->category_params);
            $item->category_params = $catParams;

            if ((int) $item->params->get('dr_show_email', 0) === 1) {
                $item->email_to = trim((string) ($item->email_to ?? ''));

                if ($item->email_to !== '' && !MailHelper::isEmailAddress($item->email_to)) {
                    $item->email_to = null;
                }
            }
        }

        (new ReportbuildHelper())->getKML($items);
    }
}
