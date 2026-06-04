<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Decodes the `pc_social` JSON column (synced from PC SocialProfile resources)
 * into a render-ready list of links, normalising each PC `site` label to a
 * stable platform key (for icon/CSS hooks) and a display label.
 *
 * @since  __DEPLOY_VERSION__
 */
final class SocialLinks
{
    /**
     * Known platforms: canonical key => {label, match tokens}. A token matches
     * when it equals the PC `site` value (case-insensitive) or appears as a
     * domain label in the URL host — so "Twitter", "X" and host "x.com" all
     * resolve to the canonical `x` entry, without `x` substring-matching hosts
     * like "example.com".
     *
     * @var    array<string, array{label: string, match: list<string>}>
     * @since  __DEPLOY_VERSION__
     */
    private const PLATFORMS = [
        'x'         => ['label' => 'X',         'match' => ['x', 'twitter']],
        'facebook'  => ['label' => 'Facebook',  'match' => ['facebook']],
        'instagram' => ['label' => 'Instagram', 'match' => ['instagram']],
        'linkedin'  => ['label' => 'LinkedIn',  'match' => ['linkedin']],
        'youtube'   => ['label' => 'YouTube',   'match' => ['youtube']],
        'tiktok'    => ['label' => 'TikTok',    'match' => ['tiktok']],
        'threads'   => ['label' => 'Threads',   'match' => ['threads']],
        'snapchat'  => ['label' => 'Snapchat',  'match' => ['snapchat']],
        'pinterest' => ['label' => 'Pinterest', 'match' => ['pinterest']],
        'github'    => ['label' => 'GitHub',    'match' => ['github']],
    ];

    /**
     * Decode `pc_social` into a list of links. Malformed JSON, entries without
     * a usable http(s) URL, and non-arrays are dropped.
     *
     * @param   string|null  $json  The raw `pc_social` column value.
     *
     * @return  list<array{site: string, url: string, key: string, label: string}>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function fromJson(?string $json): array
    {
        $json = trim((string) $json);

        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!\is_array($decoded)) {
            return [];
        }

        $out = [];

        foreach ($decoded as $entry) {
            if (!\is_array($entry)) {
                continue;
            }

            $url  = trim((string) ($entry['url'] ?? ''));
            $site = trim((string) ($entry['site'] ?? ''));

            if ($url === '' || preg_match('~^https?://~i', $url) !== 1) {
                continue;
            }

            $key = self::platformKey($site, $url);

            $out[] = [
                'site'  => $site,
                'url'   => $url,
                'key'   => $key,
                'label' => self::PLATFORMS[$key]['label'] ?? ($site !== '' ? $site : 'Website'),
            ];
        }

        return $out;
    }

    /**
     * Resolve a normalised platform key from the PC `site` label, falling back
     * to the URL host. Unknown platforms return a slugified site label (or
     * 'website' when nothing usable is present).
     *
     * @param   string  $site  The PC SocialProfile `site` value.
     * @param   string  $url   The profile URL.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function platformKey(string $site, string $url): string
    {
        $site = strtolower(trim($site));
        $host = strtolower((string) parse_url($url, \PHP_URL_HOST));

        foreach (self::PLATFORMS as $key => $def) {
            foreach ($def['match'] as $token) {
                // Exact site label, or the token as a domain label in the host
                // (e.g. "x.com", "www.linkedin.com") — never a loose substring.
                if ($site === $token
                    || str_contains($host, $token . '.')
                    || str_contains($host, '.' . $token . '.')) {
                    return $key;
                }
            }
        }

        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $site));

        return trim($slug, '-') ?: 'website';
    }
}
