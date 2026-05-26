<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Table class for `#__cwmconnect_feed_tokens`.
 *
 * @since  __DEPLOY_VERSION__
 */
class FeedTokenTable extends Table
{
    /** @since __DEPLOY_VERSION__ */
    public ?int $id = null;

    /** @since __DEPLOY_VERSION__ */
    public ?int $user_id = null;

    /** @since __DEPLOY_VERSION__ */
    public ?string $token_hash = null;

    /** @since __DEPLOY_VERSION__ */
    public ?string $label = null;

    /** @since __DEPLOY_VERSION__ */
    public ?string $created_at = null;

    /** @since __DEPLOY_VERSION__ */
    public ?string $last_used_at = null;

    /** @since __DEPLOY_VERSION__ */
    public ?string $revoked_at = null;

    /**
     * @param   DatabaseInterface  $db  Database connector.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__cwmconnect_feed_tokens', 'id', $db);
    }

    /**
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function check(): bool
    {
        if (empty($this->user_id)) {
            $this->setError(Text::_('COM_CWMCONNECT_FEEDTOKEN_ERR_NO_USER'));

            return false;
        }

        if (trim((string) $this->label) === '') {
            $this->setError(Text::_('COM_CWMCONNECT_FEEDTOKEN_ERR_NO_LABEL'));

            return false;
        }

        if (empty($this->token_hash)) {
            $this->setError(Text::_('COM_CWMCONNECT_FEEDTOKEN_ERR_NO_HASH'));

            return false;
        }

        if (empty($this->created_at)) {
            $this->created_at = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }

        return true;
    }
}
