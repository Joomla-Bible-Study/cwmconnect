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
 * Dirheader table class for #__churchdirectory_dirheader.
 *
 * @since  2.0.0
 */
class DirheaderTable extends Table
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
    public ?string $section = '0';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $webpage = '';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $image = '';

    /**
     * @var int|null
     * @since 2.0.0
     */
    public ?int $catid = 0;

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
     * @var int|null
     * @since 2.0.0
     */
    public ?int $featured = 0;

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
    public ?string $created_by_alias = '';

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
     * @var string|null
     * @since 2.0.0
     */
    public ?string $params = null;

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $metakey = '';

    /**
     * @var string|null
     * @since 2.0.0
     */
    public ?string $metadesc = '';

    /**
     * @param   DatabaseInterface  $db  Database connector object
     *
     * @since   2.0.0
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->_jsonEncode = ['params'];

        parent::__construct('#__churchdirectory_dirheader', 'id', $db);
    }

    /**
     * Stores a Dirheader record.
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

        // Verify that the alias is unique within the same category.
        $table = clone $this;
        $table->reset();

        if (
            $table->load(['alias' => $this->alias, 'catid' => $this->catid])
            && ((int) $table->id !== (int) $this->id || (int) $this->id === 0)
        ) {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_ERROR_UNIQUE_ALIAS'));

            return false;
        }

        return parent::store($updateNulls);
    }

    /**
     * Validates a Dirheader record before saving.
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
            ->from($db->quoteName('#__churchdirectory_dirheader'))
            ->where($db->quoteName('name') . ' = ' . $db->quote($this->name));
        $db->setQuery($query);
        $xid = (int) $db->loadResult();

        if ($xid && $xid !== (int) $this->id) {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_WARNING_SAME_NAME'));

            return false;
        }

        if (empty($this->alias)) {
            $this->alias = (string) $this->name;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if ((int) $this->publish_down > 0 && $this->publish_down < $this->publish_up) {
            $temp               = $this->publish_up;
            $this->publish_up   = $this->publish_down;
            $this->publish_down = $temp;
        }

        return true;
    }
}
