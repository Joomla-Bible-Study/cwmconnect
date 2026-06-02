<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

use CWM\Component\Cwmconnect\Administrator\Service\Image\ImageVariants;
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

        $real = realpath($candidate);

        if ($real === false || !is_file($real)) {
            return null;
        }

        // Containment: the resolved real path must stay inside the document
        // root OR the component's media dir, so a stray DB value can never
        // escape to the wider filesystem. The media dir is checked separately
        // because `composer link` (and some production setups) symlink media/
        // out of the install root — realpath() resolves that symlink, so a
        // legitimate photo's real path legitimately lives outside JPATH_ROOT.
        $allowedBases = array_filter([
            realpath(JPATH_ROOT),
            realpath(JPATH_ROOT . '/media/com_cwmconnect'),
        ]);

        foreach ($allowedBases as $base) {
            if (str_starts_with($real, $base . \DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }

        return null;
    }

    /**
     * Resolve a browser-optimized web variant of a member photo, or null when
     * none exists (caller falls back to {@see resolvePath()} on the original).
     * Prefers WebP when the client accepts it, then the JPEG fallback.
     *
     * The stem comes from the stored `image` value via pathinfo, so only the
     * id/hash survives — no path or extension reaches the filesystem lookup.
     *
     * @param   string  $image         The member `image` column value.
     * @param   string  $size          Variant size (e.g. 'thumb', 'medium').
     * @param   bool    $acceptsWebp    Whether the client sent image/webp.
     *
     * @return  string|null  Absolute path to the variant, or null.
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function resolveVariant(string $image, string $size, bool $acceptsWebp): ?string
    {
        $stem = pathinfo(trim($image), \PATHINFO_FILENAME);

        if ($stem === '' || !isset(ImageVariants::SIZES[$size])) {
            return null;
        }

        $webDir = realpath(JPATH_ROOT . '/media/com_cwmconnect/photos/web');

        if ($webDir === false) {
            return null;
        }

        foreach ($acceptsWebp ? ['webp', 'jpg'] : ['jpg'] as $format) {
            $real = realpath($webDir . '/' . ImageVariants::variantFilename($stem, $size, $format));

            if ($real !== false && is_file($real) && str_starts_with($real, $webDir . \DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }

        return null;
    }

    /**
     * Load a family-unit row for household-photo serving (id, image,
     * published), or null.
     *
     * @param   DatabaseInterface  $db        Database driver.
     * @param   int                $funitId   Family-unit id.
     *
     * @return  object|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function loadHousehold(DatabaseInterface $db, int $funitId): ?object
    {
        if ($funitId <= 0) {
            return null;
        }

        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'image', 'published']))
            ->from($db->quoteName('#__cwmconnect_familyunit'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $funitId, ParameterType::INTEGER);

        return $db->setQuery($query, 0, 1)->loadObject() ?: null;
    }

    /**
     * Resolve a household family photo to an absolute path — an optimized web
     * variant first (WebP when accepted), then the original — under
     * `photos/households/`, or null. Stem comes from pathinfo, so no path or
     * extension from the stored value reaches the filesystem lookup.
     *
     * @param   string  $image        The family-unit `image` value.
     * @param   string  $size         Variant size, or '' for the original.
     * @param   bool    $acceptsWebp  Whether the client sent image/webp.
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function resolveHouseholdImage(string $image, string $size, bool $acceptsWebp): ?string
    {
        $image = trim($image);
        $base  = JPATH_ROOT . '/media/com_cwmconnect/photos/households';
        $stem  = pathinfo($image, \PATHINFO_FILENAME);

        if ($stem !== '' && isset(ImageVariants::SIZES[$size]) && ($webDir = realpath($base . '/web')) !== false) {
            foreach ($acceptsWebp ? ['webp', 'jpg'] : ['jpg'] as $format) {
                $real = realpath($webDir . '/' . ImageVariants::variantFilename($stem, $size, $format));

                if ($real !== false && is_file($real) && str_starts_with($real, $webDir . \DIRECTORY_SEPARATOR)) {
                    return $real;
                }
            }
        }

        if ($image === '' || str_contains($image, '..')
            || !\in_array(strtolower(pathinfo($image, \PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        $real     = realpath($base . '/' . $image);
        $baseReal = realpath($base);

        return ($real !== false && is_file($real) && $baseReal !== false && str_starts_with($real, $baseReal . \DIRECTORY_SEPARATOR))
            ? $real
            : null;
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
        // Photos are access-gated, so cache privately (browser only). A
        // day-long cache spares the proxy on busy directory pages while
        // keeping a re-synced photo (same filename) fresh within a day. Vary
        // on Accept so a WebP response is never reused for a JPEG-only client.
        $app->setHeader('Cache-Control', 'private, max-age=86400', true);
        $app->setHeader('Vary', 'Accept', true);
        $app->sendHeaders();

        readfile($path);

        $app->close();
    }
}
