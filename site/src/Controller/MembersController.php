<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Members list controller with KML feed download task.
 *
 * @since  __DEPLOY_VERSION__
 */
class MembersController extends BaseController
{
    /** @since __DEPLOY_VERSION__ */
    protected $default_view = 'members';

    /**
     * Serve a NetworkLink KML file with the user's feed token baked in.
     * Auto-creates a token if the user doesn't have one.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function kmlFeed(): void
    {
        $userId = (int) ($this->app->getIdentity()?->id ?? 0);

        if ($userId <= 0) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $db      = Factory::getContainer()->get(DatabaseInterface::class);
        $service = new FeedTokenService($db);

        $cleartext = $this->getOrCreateToken($db, $service, $userId);

        $dataUrl = Uri::root() . 'index.php?option=com_cwmconnect&view=members&format=kml&token=' . urlencode($cleartext);

        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
        $lines[] = '<Document>';
        $lines[] = '  <name>' . htmlspecialchars(Text::_('COM_CWMCONNECT_KML_DOCUMENT_NAME'), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</name>';
        $lines[] = '  <NetworkLink>';
        $lines[] = '    <name>' . htmlspecialchars(Text::_('COM_CWMCONNECT_KML_NETWORKLINK_NAME'), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</name>';
        $lines[] = '    <refreshVisibility>1</refreshVisibility>';
        $lines[] = '    <Link>';
        $lines[] = '      <href>' . htmlspecialchars($dataUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</href>';
        $lines[] = '      <refreshMode>onInterval</refreshMode>';
        $lines[] = '      <refreshInterval>900</refreshInterval>';
        $lines[] = '    </Link>';
        $lines[] = '  </NetworkLink>';
        $lines[] = '</Document>';
        $lines[] = '</kml>';

        $this->app->setHeader('Content-Type', 'application/vnd.google-earth.kml+xml; charset=UTF-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="church-directory-feed.kml"', true);
        $this->app->sendHeaders();

        echo implode("\n", $lines);
        $this->app->close();
    }

    /**
     * Find an active token for the user, or create one.
     *
     * @param   DatabaseInterface  $db       Database.
     * @param   FeedTokenService   $service  Token service.
     * @param   int                $userId   Joomla user ID.
     *
     * @return  string  Cleartext token.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function getOrCreateToken(DatabaseInterface $db, FeedTokenService $service, int $userId): string
    {
        $query = $db->createQuery()
            ->select($db->quoteName('token_hash'))
            ->from($db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->where($db->quoteName('revoked_at') . ' IS NULL')
            ->bind(':uid', $userId, ParameterType::INTEGER)
            ->setLimit(1);

        $existingHash = $db->setQuery($query)->loadResult();

        if ($existingHash) {
            $pair      = $service->generate();
            $cleartext = $pair['cleartext'];

            $update = $db->createQuery()
                ->update($db->quoteName('#__cwmconnect_feed_tokens'))
                ->set($db->quoteName('token_hash') . ' = ' . $db->quote($pair['hash']))
                ->where($db->quoteName('user_id') . ' = :uid')
                ->where($db->quoteName('token_hash') . ' = ' . $db->quote($existingHash))
                ->bind(':uid', $userId, ParameterType::INTEGER);

            $db->setQuery($update)->execute();

            return $cleartext;
        }

        $pair = $service->generate();
        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $username = $this->app->getIdentity()?->username ?? 'user';

        $row = (object) [
            'user_id'    => $userId,
            'token_hash' => $pair['hash'],
            'label'      => 'Auto — ' . $username,
            'created_at' => $now,
        ];

        $db->insertObject('#__cwmconnect_feed_tokens', $row);

        return $pair['cleartext'];
    }
}
