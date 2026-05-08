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
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Member table class for #__churchdirectory_details.
 *
 * @since  2.0.0
 */
class MemberTable extends Table
{
    /** @since 2.0.0 */
    public ?int $id = 0;
    /** @since 2.0.0 */
    public ?string $name = '';
    /** @since 2.0.0 */
    public ?string $lname = '';
    /** @since 2.0.0 */
    public ?string $alias = '';
    /** @since 2.0.0 */
    public ?string $con_position = null;
    /** @since 2.0.0 */
    public ?int $contact_id = null;
    /** @since 2.0.0 */
    public ?string $address = null;
    /** @since 2.0.0 */
    public ?string $suburb = null;
    /** @since 2.0.0 */
    public ?string $state = null;
    /** @since 2.0.0 */
    public ?string $country = null;
    /** @since 2.0.0 */
    public ?string $postcode = null;
    /** @since 2.0.0 */
    public ?string $postcodeaddon = null;
    /** @since 2.0.0 */
    public ?string $telephone = null;
    /** @since 2.0.0 */
    public ?string $fax = null;
    /** @since 2.0.0 */
    public ?string $misc = null;
    /** @since 2.0.0 */
    public ?string $spouse = null;
    /** @since 2.0.0 */
    public ?string $children = null;
    /** @since 2.0.0 */
    public ?string $image = null;
    /** @since 2.0.0 */
    public ?string $imagepos = null;
    /** @since 2.0.0 */
    public ?string $email_to = null;
    /** @since 2.0.0 */
    public ?int $default_con = 0;
    /** @since 2.0.0 */
    public ?int $published = 0;
    /** @since 2.0.0 */
    public ?int $checked_out = null;
    /** @since 2.0.0 */
    public ?string $checked_out_time = null;
    /** @since 2.0.0 */
    public ?int $ordering = 0;
    /** @since 2.0.0 */
    public ?string $params = null;
    /** @since 2.0.0 */
    public ?int $user_id = 0;
    /** @since 2.0.0 */
    public ?int $catid = 0;
    /** @since 2.0.0 */
    public ?int $kmlid = null;
    /** @since 2.0.0 */
    public ?int $funitid = null;
    /** @since 2.0.0 */
    public ?int $access = 1;
    /** @since 2.0.0 */
    public ?string $mobile = null;
    /** @since 2.0.0 */
    public ?string $webpage = null;
    /** @since 2.0.0 */
    public ?string $sortname1 = null;
    /** @since 2.0.0 */
    public ?string $sortname2 = null;
    /** @since 2.0.0 */
    public ?string $sortname3 = null;
    /** @since 2.0.0 */
    public ?string $language = '*';
    /** @since 2.0.0 */
    public ?string $created = null;
    /** @since 2.0.0 */
    public ?int $created_by = 0;
    /** @since 2.0.0 */
    public ?string $created_by_alias = null;
    /** @since 2.0.0 */
    public ?string $modified = null;
    /** @since 2.0.0 */
    public ?int $modified_by = 0;
    /** @since 2.0.0 */
    public ?string $metakey = null;
    /** @since 2.0.0 */
    public ?string $metadesc = null;
    /** @since 2.0.0 */
    public ?string $metadata = null;
    /** @since 2.0.0 */
    public ?int $featured = 0;
    /** @since 2.0.0 */
    public ?string $xreference = null;
    /** @since 2.0.0 */
    public ?string $publish_up = null;
    /** @since 2.0.0 */
    public ?string $publish_down = null;
    /** @since 2.0.0 */
    public ?string $skype = null;
    /** @since 2.0.0 */
    public ?string $yahoo_msg = null;
    /** @since 2.0.0 */
    public ?string $lat = null;
    /** @since 2.0.0 */
    public ?string $lng = null;
    /** @since 2.0.0 */
    public ?string $birthdate = null;
    /** @since 2.0.0 */
    public ?string $anniversary = null;
    /** @since 2.0.0 */
    public ?string $attribs = null;
    /** @since 2.0.0 */
    public ?int $version = 0;
    /** @since 2.0.0 */
    public ?int $hits = 0;
    /** @since 2.0.0 */
    public ?string $surname = null;
    /** @since 2.0.0 */
    public ?int $mstatus = 0;

    /**
     * @param   DatabaseInterface  $db  Database connector object
     *
     * @since   2.0.0
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->_jsonEncode = ['params', 'attribs', 'metadata'];

        parent::__construct('#__churchdirectory_details', 'id', $db);
    }

    /**
     * Override bind: collapse a multi-select con_position array to a CSV string.
     *
     * @param   mixed  $array   Data to bind.
     * @param   mixed  $ignore  Properties to ignore.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    #[\Override]
    public function bind($array, $ignore = ''): bool
    {
        if (
            \is_array($array)
            && \array_key_exists('con_position', $array)
            && \is_array($array['con_position'])
        ) {
            $array['con_position'] = implode(',', $array['con_position']);
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Stores a Member record.
     *
     * @param   bool  $updateNulls  True to update fields even if null.
     *
     * @return  bool
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

        if (\is_array($this->attribs)) {
            $registry     = new Registry($this->attribs);
            $this->attribs = (string) $registry;
        }

        // If marked as a child (funitid = -1), force familypostion to 0.
        if ((int) $this->funitid === -1) {
            $registry = new Registry($this->attribs);
            $registry->set('familypostion', '0');
            $this->attribs = (string) $registry;
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

        $nullDate = $this->_db->getNullDate();

        if (!$this->publish_up) {
            $this->publish_up = $nullDate;
        }

        if (!$this->publish_down) {
            $this->publish_down = $nullDate;
        }

        if (!$this->xreference) {
            $this->xreference = '';
        }

        // IDN/punycode normalisation for email and webpage.
        if (!empty($this->email_to)) {
            $this->email_to = PunycodeHelper::emailToPunycode($this->email_to);
        }

        if (!empty($this->webpage)) {
            $this->webpage = PunycodeHelper::urlToPunycode($this->webpage);
        }

        // Verify the alias is unique within the same category.
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
     * Validates a Member record before saving.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    #[\Override]
    public function check(): bool
    {
        $this->default_con = (int) $this->default_con;

        if (InputFilter::checkAttribute(['href', $this->webpage ?? ''])) {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_URL'));

            return false;
        }

        if (trim((string) $this->name) === '') {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_NAME'));

            return false;
        }

        $this->generateAlias();

        if (trim((string) $this->catid) === '') {
            $this->setError(Text::_('COM_CHURCHDIRECTORY_WARNING_CATEGORY'));

            return false;
        }

        if (!$this->user_id) {
            $this->user_id = 0;
        }

        if ((int) $this->publish_down > 0 && $this->publish_down < $this->publish_up) {
            $this->setError(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

            return false;
        }

        if (!empty($this->metakey)) {
            $badCharacters  = ["\n", "\r", '"', '<', '>'];
            $afterClean     = StringHelper::str_ireplace($badCharacters, '', $this->metakey);
            $keys           = explode(',', $afterClean);
            $cleanKeys      = [];

            foreach ($keys as $key) {
                if (trim($key) !== '') {
                    $cleanKeys[] = trim($key);
                }
            }

            $this->metakey = implode(', ', $cleanKeys);
        }

        if (!empty($this->metadesc)) {
            $badCharacters  = ['"', '<', '>'];
            $this->metadesc = StringHelper::str_ireplace($badCharacters, '', $this->metadesc);
        }

        return true;
    }

    /**
     * Returns the title used for the asset table.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    protected function _getAssetTitle(): string
    {
        return (string) $this->name;
    }

    /**
     * Get the parent asset id for the record.
     *
     * @param   Table|null  $table  Table object.
     * @param   int|null    $id     Asset id.
     *
     * @return  int
     *
     * @since   2.0.0
     */
    protected function _getAssetParentId(?Table $table = null, $id = null): int
    {
        $asset = Table::getInstance('Asset');
        $asset->loadByName('com_churchdirectory');

        return (int) $asset->id;
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
