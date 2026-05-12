<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Category;

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
 * KML feed view for a single directory category — streams Google-Earth
 * compatible placemarks for the members under the category.
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
        $parent   = $model->getParent();

        if ($category === null || $category === false || $parent === false) {
            throw new \Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'), 404);
        }

        $user   = Factory::getApplication()->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [1];

        if (!\in_array($category->access, $groups, false)) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        if (empty($items)) {
            throw new \Exception('No Data', 404);
        }

        foreach ($items as $item) {
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : (string) $item->id;

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
