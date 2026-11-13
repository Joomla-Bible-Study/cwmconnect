<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Search;

use Joomla\CMS\Event\Finder\AfterDeleteEvent;
use Joomla\CMS\Event\Finder\AfterSaveEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * {@see SearchIndexInterface} through Smart Search's own events, which the
 * component's finder plugin answers. Does nothing while that plugin is off.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class FinderSearchIndex implements SearchIndexInterface
{
    /**
     * @param   DatabaseInterface  $db  Database driver.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private DatabaseInterface $db) {}

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function memberChanged(int $memberId): void
    {
        $dispatcher = $this->dispatcher();

        if ($dispatcher === null || $memberId <= 0) {
            return;
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName(['id', 'access']))
            ->from($this->db->quoteName('#__cwmconnect_details'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $memberId, ParameterType::INTEGER);

        $member = $this->db->setQuery($query)->loadObject();

        if ($member === null) {
            return;
        }

        $dispatcher->dispatch('onFinderAfterSave', new AfterSaveEvent('onFinderAfterSave', [
            'context' => 'com_cwmconnect.member',
            'subject' => $member,
            'isNew'   => false,
        ]));
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function membersRemoved(array $memberIds): void
    {
        $dispatcher = $this->dispatcher();

        if ($dispatcher === null) {
            return;
        }

        foreach ($memberIds as $memberId) {
            $dispatcher->dispatch('onFinderAfterDelete', new AfterDeleteEvent('onFinderAfterDelete', [
                'context' => 'com_cwmconnect.member',
                'subject' => (object) ['id' => (int) $memberId],
            ]));
        }
    }

    /**
     * The application's dispatcher with the finder plugins loaded, or null
     * when the member search plugin is off and there is nothing to tell.
     *
     * @return  DispatcherInterface|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function dispatcher(): ?DispatcherInterface
    {
        if (!PluginHelper::isEnabled('finder', 'cwmconnect')) {
            return null;
        }

        $dispatcher = Factory::getApplication()->getDispatcher();
        PluginHelper::importPlugin('finder', null, true, $dispatcher);

        return $dispatcher;
    }
}
