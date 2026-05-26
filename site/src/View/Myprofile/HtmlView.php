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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

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
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    protected bool $hasActiveToken = false;

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

        $this->form            = $model->getForm() ?: null;
        $this->lockedFields    = PcLockedFields::forItem($this->item);
        $this->isPcLinked      = (int) ($this->item->pc_person_id ?? 0) > 0;
        $this->hasActiveToken  = $this->checkActiveToken();

        parent::display($tpl);
    }

    /**
     * Check whether the current user has an active (non-revoked) feed token.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function checkActiveToken(): bool
    {
        $userId = (int) (Factory::getApplication()->getIdentity()?->id ?? 0);

        if ($userId <= 0) {
            return false;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->where($db->quoteName('revoked_at') . ' IS NULL')
            ->bind(':uid', $userId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}
