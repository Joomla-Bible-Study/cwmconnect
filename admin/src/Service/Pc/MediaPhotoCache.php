<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Image\ImageVariants;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\PcException;
use Joomla\CMS\Http\Http;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase E: production `PhotoCacheInterface` that stores avatars under
 * `media/com_cwmconnect/photos/`.
 *
 * Hash strategy: SHA-256 of the source URL itself. PC bakes the avatar
 * version into the URL path (e.g. `/uploads/.../abc123.png?v=…`), so two
 * different URLs guarantee two different photos — we don't need to fetch
 * bytes just to diff. Per Decision #13 in the spec.
 *
 * Placeholder skip: PC returns `demographic_avatar_url` for everyone
 * (initials-on-coloured-circle). We treat that as "no photo" so the cache
 * doesn't fill with hundreds of identical-but-named placeholders. The
 * detection is path-based — anything under `/static/` or with
 * `demographic_avatar` in the URL is skipped.
 *
 * @since  __DEPLOY_VERSION__
 */
final class MediaPhotoCache implements PhotoCacheInterface
{
    /**
     * Max accepted Content-Length / body size for an avatar download.
     * PC avatars top out well under 1MB; we cap at 5MB so a
     * configuration mistake or proxy-returned HTML page can't fill the
     * disk. Bytes over the cap are dropped and the call returns null.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Allow-listed extensions derived from URL path. Anything outside
     * this set falls back to `.jpg` (PC's most common avatar format) so
     * we never write an executable or unknown blob to the photos dir.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const SAFE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Constructor.
     *
     * @param   Http    $http     Joomla HTTP client. Same instance as the
     *                            PC API client uses so timeouts / proxies
     *                            stay consistent.
     * @param   string  $cacheRoot  Absolute path to the photos directory.
     *                              Defaults to JPATH_ROOT/media/...; tests
     *                              point at a vfs path.
     * @param   int     $timeoutSeconds  HTTP timeout for the download.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly Http $http,
        private readonly string $cacheRoot,
        private readonly int $timeoutSeconds = 30,
        private readonly ?PhotoThumbnailer $thumbnailer = null,
        private readonly ?ImageVariants $variants = null,
    ) {}

    public function cache(int $pcPersonId, ?string $avatarUrl, ?string $currentHash): ?PhotoCacheResult
    {
        if ($pcPersonId <= 0 || $avatarUrl === null || $avatarUrl === '') {
            return null;
        }

        if ($this->looksLikePlaceholder($avatarUrl)) {
            return null;
        }

        $hash = hash('sha256', $avatarUrl);

        if ($currentHash !== null && hash_equals($currentHash, $hash)) {
            // URL unchanged — assume the existing file is still good. We
            // don't re-verify on disk; a missing file is a separate
            // concern (admin can hit "Refresh now" to force re-download).
            $relativePath = $this->relativePathFor($pcPersonId, $avatarUrl);

            return new PhotoCacheResult($relativePath, $hash, false);
        }

        $bytes = $this->downloadBytes($avatarUrl);

        if ($bytes === null) {
            return null;
        }

        $relativePath = $this->relativePathFor($pcPersonId, $avatarUrl);
        $this->writeBytes($relativePath, $bytes);
        $this->writeThumbnail($relativePath);
        $this->writeWebVariants($relativePath);

        return new PhotoCacheResult($relativePath, $hash, true);
    }

    /**
     * Generate the browser-optimized web variants (thumb/medium × WebP/JPEG)
     * for a freshly cached photo, under `<cacheRoot>/web/`. Best-effort — the
     * serve proxy falls back to the original if a variant is missing.
     *
     * @param   string  $relativePath  Original photo path relative to cacheRoot.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function writeWebVariants(string $relativePath): void
    {
        $stem = pathinfo($relativePath, \PATHINFO_FILENAME);

        if ($stem === '') {
            return;
        }

        $root = rtrim($this->cacheRoot, '/');
        ($this->variants ?? new ImageVariants())->generate($root . '/' . $relativePath, $root . '/web', $stem);
    }

    /**
     * K.7: build the print-ready directory thumbnail for a freshly cached
     * photo. Best-effort — a thumbnail failure must not abort the sync; the
     * PDF builder regenerates missing thumbnails on demand.
     *
     * @param   string  $relativePath  Original photo path relative to cacheRoot.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function writeThumbnail(string $relativePath): void
    {
        $thumbName = PhotoThumbnailer::thumbFilename($relativePath);

        if ($thumbName === '') {
            return;
        }

        $root        = rtrim($this->cacheRoot, '/');
        $thumbnailer = $this->thumbnailer ?? new PhotoThumbnailer();

        $thumbnailer->generate($root . '/' . $relativePath, $root . '/thumb/' . $thumbName);
    }

    /**
     * Heuristic detector for PC's generic demographic avatars. Matches the
     * `/static/.../demographic_avatar_*.png` pattern PC currently returns.
     *
     * Conservative on purpose: anything we don't recognise as a placeholder
     * gets downloaded. A false-positive (skipping a real photo) just leaves
     * the UI placeholder visible; a false-negative (caching a placeholder)
     * wastes a kilobyte. Erring toward "real photos win" matches Decision #13.
     *
     * @param   string  $url
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function looksLikePlaceholder(string $url): bool
    {
        $path = parse_url($url, \PHP_URL_PATH);

        if (!\is_string($path) || $path === '') {
            return false;
        }

        return str_contains($path, '/static/')
            || str_contains($path, 'demographic_avatar')
            || str_contains($path, 'avatar_default')
            // Households with no uploaded photo get a generated coloured square
            // (e.g. cloudfront `/455-square.png`); treat it as "no photo".
            || str_ends_with($path, '-square.png');
    }

    /**
     * Build the relative path stored in `#__cwmconnect_details.image`.
     * Extension is derived from the URL path, defaulting to `jpg` when
     * the URL doesn't carry a clean extension.
     *
     * @param   int     $pcPersonId
     * @param   string  $avatarUrl
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function relativePathFor(int $pcPersonId, string $avatarUrl): string
    {
        $path = parse_url($avatarUrl, \PHP_URL_PATH) ?: '';
        $ext  = strtolower((string) (pathinfo($path, \PATHINFO_EXTENSION) ?? ''));

        if (!\in_array($ext, self::SAFE_EXTENSIONS, true)) {
            $ext = 'jpg';
        }

        return $pcPersonId . '.' . $ext;
    }

    /**
     * Fetch the avatar bytes. Returns null on transport failure, non-2xx,
     * over-size body, or empty body. Failures are deliberately
     * non-exceptional so one bad URL doesn't abort a sync run.
     *
     * @param   string  $url
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function downloadBytes(string $url): ?string
    {
        try {
            $response = $this->http->get($url, [], $this->timeoutSeconds);
        } catch (\Throwable) {
            return null;
        }

        $statusCode = (int) ($response->code ?? 0);
        $body       = (string) ($response->body ?? '');

        if ($statusCode < 200 || $statusCode >= 300) {
            return null;
        }

        if ($body === '' || \strlen($body) > self::MAX_BYTES) {
            return null;
        }

        return $body;
    }

    /**
     * Atomically write the cached image. Creates the cache root on demand;
     * writes to a temp file and renames so a partial download never leaves
     * a half-written photo behind.
     *
     * @param   string  $relativePath
     * @param   string  $bytes
     *
     * @return  void
     *
     * @throws  PcException  When the cache root can't be created.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function writeBytes(string $relativePath, string $bytes): void
    {
        if (!is_dir($this->cacheRoot) && !mkdir($this->cacheRoot, 0o755, true) && !is_dir($this->cacheRoot)) {
            throw new PcException(\sprintf('Could not create photo cache root: %s', $this->cacheRoot));
        }

        $finalPath = rtrim($this->cacheRoot, '/') . '/' . $relativePath;
        $tempPath  = $finalPath . '.tmp';

        file_put_contents($tempPath, $bytes);
        rename($tempPath, $finalPath);
    }
}
