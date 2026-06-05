<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Myprofile;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Helper\PcLockedFields;
use CWM\Component\Cwmconnect\Site\Model\MyprofileModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Phase H: self-service portal HTML view.
 *
 * Two distinct render paths:
 *   - Paired user → render the portal edit form (template `edit.php`).
 *   - Unpaired user → render the §8.1 placeholder (template `placeholder.php`).
 *
 * The dispatcher already enforces the login wall; an anonymous viewer never
 * reaches this view.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var object|false Persistent member row, or false when unpaired. */
    protected object|false $item = false;

    /** @var Form|null Portal form with locked-field attributes applied. */
    protected ?Form $form = null;

    /** @var list<string> PC-locked column names — drives the template hints. */
    protected array $lockedFields = [];

    /** @var bool True when `$item` originated from Planning Center. */
    protected bool $isPcLinked = false;

    /** @var string mailto: admin contact for the unpaired placeholder. */
    protected string $adminEmail = '';

    /**
     * Current user's live map feeds, each tagged with a computed status.
     *
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    protected array $feeds = [];

    /**
     * Maximum live feeds the member may hold at once.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    protected int $maxFeeds = 5;

    /**
     * True when the member has reached the feed cap.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    protected bool $atFeedCap = false;

    /**
     * One-time cleartext token surfaced once after create/regenerate, so the
     * template can render the download link + raw URL.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected string $newFeedCleartext = '';

    /**
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var MyprofileModel $model */
        $model      = $this->getModel();
        $this->item = $model->getItemForCurrentUser();

        if ($this->item === false) {
            $this->adminEmail = (string) ComponentHelper::getParams('com_cwmconnect')->get(
                'admin_contact_email',
                (string) Factory::getApplication()->get('mailfrom', ''),
            );

            $tpl ??= 'placeholder';
            parent::display($tpl);

            return;
        }

        $this->form         = $model->getForm() ?: null;
        $this->lockedFields = PcLockedFields::forItem($this->item);
        $this->isPcLinked   = (int) ($this->item->pc_person_id ?? 0) > 0;

        $this->feeds     = $model->getFeeds();
        $this->maxFeeds  = $model->maxFeedsPerMember();
        $this->atFeedCap = $model->activeFeedCount() >= $this->maxFeeds;

        $app                    = Factory::getApplication();
        $this->newFeedCleartext = (string) $app->getUserState('com_cwmconnect.myprofile.feed_cleartext', '');
        $app->setUserState('com_cwmconnect.myprofile.feed_cleartext', null);

        parent::display($tpl);
    }
}
