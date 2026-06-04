<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Profile;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\HouseholdVisibility;
use CWM\Component\Cwmconnect\Site\Model\ProfileModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Phase 0: single-member public profile (the v2 replacement for the legacy
 * `member` view). Renders one directory member with their household and
 * PC-synced contact + social details.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The target member.
     *
     * @var    object
     * @since  __DEPLOY_VERSION__
     */
    public object $item;

    /**
     * Visibility scope of the current viewer relative to the member's
     * household (drives whether children's names render).
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    public string $householdScope = HouseholdVisibility::OTHER_HOUSEHOLD;

    /**
     * Other members of the target's household.
     *
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $householdMembers = [];

    /**
     * Count of household members the viewer can't see by name.
     *
     * @var    integer
     * @since  __DEPLOY_VERSION__
     */
    public int $hiddenHouseholdCount = 0;

    /**
     * Render the profile.
     *
     * @param   string|null  $tpl  The template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var ProfileModel $model */
        $model = $this->getModel();
        $item  = $model->getItem();

        if ($item === false) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PROFILE_NOT_FOUND'), 404);
        }

        $this->item = $item;

        $viewerUserId      = (int) ($this->getCurrentUser()->id ?? 0);
        $viewerHouseholdId = $model->viewerHouseholdId($viewerUserId);
        $targetHouseholdId = (int) ($item->funitid ?? 0);

        $this->householdScope = HouseholdVisibility::scope($viewerHouseholdId, $targetHouseholdId ?: null);

        if ($targetHouseholdId > 0) {
            $sameHousehold          = $this->householdScope === HouseholdVisibility::SAME_HOUSEHOLD;
            $this->householdMembers = $model->getHouseholdMembers($targetHouseholdId, (int) $item->id, $sameHousehold);

            if (!$sameHousehold) {
                $this->hiddenHouseholdCount = $model->getHiddenHouseholdCount($targetHouseholdId, (int) $item->id);
            }
        }

        $this->setDocumentTitle((string) $item->name);

        parent::display($tpl);
    }
}
