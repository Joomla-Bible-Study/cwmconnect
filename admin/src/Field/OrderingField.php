<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Form\Field\OrderingField as BaseOrderingField;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * Ordering field for Churchdirectory rows.
 *
 * Overrides the base ordering field's UCM-driven query (which assumes a
 * `#__content_types` registration) with a direct query against the
 * member table — keeping the J5+ form-select behaviour but plugging in a
 * component-local data source.
 *
 * @since  2.0.0
 */
class OrderingField extends BaseOrderingField
{
    /**
     * The form field type.
     *
     * @var string
     * @since 2.0.0
     */
    protected $type = 'Ordering';

    /**
     * Build the dropdown options for the configured category.
     *
     * @return  QueryInterface
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function getQuery(): QueryInterface
    {
        $categoryId = (int) $this->form->getValue('catid');
        $db         = $this->getDatabase();

        $query = $db->createQuery()
            ->select([
                $db->quoteName('ordering', 'value'),
                $db->quoteName('name', 'text'),
            ])
            ->from($db->quoteName('#__churchdirectory_details'))
            ->where($db->quoteName('catid') . ' = :categoryId')
            ->order($db->quoteName('ordering'))
            ->bind(':categoryId', $categoryId, ParameterType::INTEGER);

        return $query;
    }
}
