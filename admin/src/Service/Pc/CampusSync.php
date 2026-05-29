<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * K.6: sync Planning Center campuses into the dirheader table so the printed
 * directory cover can show the church name + address pulled from PC.
 *
 * Auxiliary to the people sync — small, idempotent, and safe to run on every
 * sync pass. The cover then resolves each field as "manual override, else PC
 * value, else site name" (see {@see \CWM\Component\Cwmconnect\Site\View\Members\PdfView}).
 *
 * @since  __DEPLOY_VERSION__
 */
final class CampusSync
{
    /**
     * @param   Client                     $client      PC API client.
     * @param   CampusMapper               $mapper      Campus → dirheader transform.
     * @param   CampusRepositoryInterface  $repository  Persistence.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly Client $client,
        private readonly CampusMapper $mapper,
        private readonly CampusRepositoryInterface $repository,
    ) {}

    /**
     * Fetch every PC campus and upsert it. Returns the number persisted.
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    public function run(): int
    {
        $count = 0;

        foreach ($this->client->listCampuses() as $campus) {
            $fields = $this->mapper->map($campus);

            if ($fields === null) {
                continue;
            }

            $this->repository->upsertByPcCampusId($fields);
            ++$count;
        }

        return $count;
    }
}
