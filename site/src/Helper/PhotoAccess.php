<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Gatekeeper for member-photo delivery.
 *
 * Member photos are cached under `media/com_cwmconnect/photos/`, which is
 * blocked from direct web access (.htaccess / web.config). They are served
 * only through the site/admin `photo.serve` proxy, which uses this helper to
 * decide who may see a photo and to stream it from disk. mpdf is unaffected —
 * it reads the files directly off the filesystem.
 *
 * @since  __DEPLOY_VERSION__
 */
final class PhotoAccess
{
    /**
     * Extensions the proxy is allowed to serve. Anything else (config, source,
     * logs, …) is refused regardless of the stored value.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * May the viewer see this member's photo? Managers (core.manage) see all;
     * everyone else is held to the same visibility gates as the directory
     * listing (published + display_in_directory + directory_scope, with
     * household scope limited to the same household).
     *
     * @param   bool         $isManager          Viewer has core.manage.
     * @param   object|null  $member             Member row, or null when not found.
     * @param   int|null     $viewerHouseholdId  Viewer's funitid, or null.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function canView(bool $isManager, ?object $member, ?int $viewerHouseholdId): bool
    {
        if ($member === null) {
            return false;
        }

        if ($isManager) {
            return true;
        }

        if ((int) ($member->published ?? 0) !== 1 || (int) ($member->display_in_directory ?? 0) !== 1) {
            return false;
        }

        return match ((string) ($member->directory_scope ?? 'public')) {
            'public'    => true,
            'household' => $viewerHouseholdId !== null && $viewerHouseholdId === (int) ($member->funitid ?? 0),
            default     => false,
        };
    }

    /**
     * Resolve a member `image` value to an absolute filesystem path, or null
     * when blank / remote / traversing / missing. Mirrors the dual shape used
     * elsewhere (bare PC-avatar filename vs root-relative legacy path).
     *
     * @param   string  $image
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function resolvePath(string $image): ?string
    {
        $image = trim($image);

        if ($image === '' || str_contains($image, '..') || preg_match('~^https?://~i', $image) === 1) {
            return null;
        }

        // Only ever serve real image files — never config, source, or logs,
        // whatever the stored value happens to be.
        if (!\in_array(strtolower(pathinfo($image, \PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        $candidate = str_contains($image, '/')
            ? JPATH_ROOT . '/' . ltrim($image, '/')
            : JPATH_ROOT . '/media/com_cwmconnect/photos/' . $image;

        // Containment: the resolved real path must stay inside the document
        // root, so a stray DB value can never escape to the wider filesystem.
        $real = realpath($candidate);
        $root = realpath(JPATH_ROOT);

        if ($real === false || $root === false || !str_starts_with($real, $root . \DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($real) ? $real : null;
    }

    /**
     * Absolute path to the "no photo available" placeholder, or null.
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function placeholderPath(): ?string
    {
        $path = JPATH_ROOT . '/media/com_cwmconnect/images/200-photo_not_available.jpg';

        return is_file($path) ? $path : null;
    }

    /**
     * MIME type for a served image, by extension.
     *
     * @param   string  $path
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function contentType(string $path): string
    {
        return match (strtolower(pathinfo($path, \PATHINFO_EXTENSION))) {
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Load the visibility-relevant columns of a member row by id.
     *
     * @param   DatabaseInterface  $db
     * @param   int                $id
     *
     * @return  object|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function loadMember(DatabaseInterface $db, int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'image', 'published', 'display_in_directory', 'directory_scope', 'funitid']))
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObject() ?: null;
    }

    /**
     * The household (funitid) of the member row paired to a Joomla user, or
     * null when the user has no paired record.
     *
     * @param   DatabaseInterface  $db
     * @param   int                $userId
     *
     * @return  int|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function householdId(DatabaseInterface $db, int $userId): ?int
    {
        if ($userId <= 0) {
            return null;
        }

        $query = $db->createQuery()
            ->select($db->quoteName('funitid'))
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->bind(':uid', $userId, ParameterType::INTEGER);

        $funitid = $db->setQuery($query, 0, 1)->loadResult();

        return $funitid !== null ? (int) $funitid : null;
    }

    /**
     * Stream an image file to the browser with private caching, then close
     * the application.
     *
     * @param   CMSApplicationInterface  $app
     * @param   string                   $path
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function stream(CMSApplicationInterface $app, string $path): void
    {
        $app->setHeader('Content-Type', self::contentType($path), true);
        $app->setHeader('Content-Length', (string) (filesize($path) ?: 0), true);
        $app->setHeader('Cache-Control', 'private, max-age=300, must-revalidate', true);
        $app->sendHeaders();

        readfile($path);

        $app->close();
    }
}
