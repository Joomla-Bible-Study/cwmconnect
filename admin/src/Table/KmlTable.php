<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Kml table class for #__churchdirectory_kml.
 *
 * @since  2.0.0
 */
class KmlTable extends Table
{
    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $id = 0;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $name = '';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $alias = '';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $description = '';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $webpage = '';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $style = '';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $lat = null;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $lng = null;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $language = '*';

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $access = 1;

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $published = 0;

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $checked_out = null;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $checked_out_time = null;

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $ordering = 0;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $created = null;

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $created_by = 0;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $modified = null;

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $modified_by = 0;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $publish_up = null;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $publish_down = null;

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $user_id = 0;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $params = null;

    /**
     * @param   DatabaseInterface  $db  Database connector object
     *
     * @since   2.0.0
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->_jsonEncode = ['params'];

        parent::__construct('#__churchdirectory_kml', 'id', $db);
    }

    /**
     * Stores a Kml record.
     *
     * @param   bool  $updateNulls  True to update fields even if they are null.
     *
     * @return  bool  True on success.
     *
     * @since   2.0.0
     */
    #[\Override]
    public function store($updateNulls = false): bool
    {
        if (\is_array($this->params)) {
            $registry     = new Registry($this->params);
            $this->params = (string) $registry;
        }

        $date   = Factory::getDate()->toSql();
        $userId = (int) (Factory::getApplication()->getIdentity()?->id ?? 0);

        $this->modified = $date;

        if ($this->id) {
            $this->modified_by = $userId;
        } else {
            if (!(int) $this->created) {
                $this->created = $date;
            }

            if (empty($this->created_by)) {
                $this->created_by = $userId;
            }
        }

        return parent::store($updateNulls);
    }

    /**
     * Validates a Kml record before saving.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    #[\Override]
    public function check(): bool
    {
        if (InputFilter::checkAttribute(['href', $this->webpage ?? ''])) {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_URL'));

            return false;
        }

        if (
            $this->webpage !== null
            && $this->webpage !== ''
            && stripos($this->webpage, 'http://') === false
            && stripos($this->webpage, 'https://') === false
            && stripos($this->webpage, 'ftp://') === false
        ) {
            $this->webpage = 'http://' . $this->webpage;
        }

        if (trim((string) $this->name) === '') {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_NAME'));

            return false;
        }

        $db    = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__churchdirectory_kml'))
            ->where($db->quoteName('name') . ' = ' . $db->quote($this->name));
        $db->setQuery($query);
        $xid = (int) $db->loadResult();

        if ($xid && $xid !== (int) $this->id) {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_WARNING_SAME_NAME'));

            return false;
        }

        $this->generateAlias();

        if ((int) $this->publish_down > 0 && $this->publish_down < $this->publish_up) {
            $this->setError(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

            return false;
        }

        return true;
    }

    /**
     * Generate a valid alias from the name (or the current date as a fallback).
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function generateAlias(): string
    {
        if (empty($this->alias)) {
            $this->alias = (string) $this->name;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, (string) $this->language);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        return $this->alias;
    }
}
