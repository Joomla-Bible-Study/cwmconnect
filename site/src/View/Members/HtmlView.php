<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Members;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Model\MembersModel;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;

/**
 * Phase G: members-only directory list view.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /** @var list<object> */
    public array $items = [];

    public ?Pagination $pagination = null;

    public mixed $state = null;

    public string $layoutMode = 'grid';

    #[\Override]
    public function display($tpl = null): void
    {
        /** @var MembersModel $model */
        $model = $this->getModel();

        $this->items      = $model->getItems() ?: [];
        $this->pagination = $model->getPagination();
        $this->state      = $model->getState();
        $this->layoutMode = (string) $this->state->get('list.layout', 'grid');

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        parent::display($tpl);
    }
}
