#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * pkg_cwmconnect package builder.
 *
 * Reads build/pkg_cwmconnect.xml plus the three sub-extension manifests listed
 * in cwm-build.config.json, builds one zip per sub-extension into build/dist/,
 * then bundles them with the package manifest into pkg_cwmconnect-{version}.zip.
 *
 * Status: STUB. Sub-extension filesets are not yet defined because the source
 * tree is still pre-modernization. Phase 3 (PSR-4 layout) lands the admin/src
 * and site/src directories whose contents this builder will package; until then
 * `composer build` reports the shape and exits 0 without producing real zips.
 */

const BASE_DIR     = __DIR__ . '/..';
const BUILD_DIR    = __DIR__;
const DIST_DIR     = __DIR__ . '/dist';
const PKG_MANIFEST = __DIR__ . '/pkg_cwmconnect.xml';
const CONFIG_FILE  = BASE_DIR . '/cwm-build.config.json';

function fail(string $msg): never
{
    fwrite(STDERR, "build-package: {$msg}\n");
    exit(1);
}

function info(string $msg): void
{
    fwrite(STDOUT, "build-package: {$msg}\n");
}

function load_package_version(): string
{
    if (!is_file(PKG_MANIFEST)) {
        fail('package manifest missing: ' . PKG_MANIFEST);
    }

    $xml = simplexml_load_file(PKG_MANIFEST);

    if ($xml === false || !isset($xml->version)) {
        fail('package manifest is malformed or has no <version>: ' . PKG_MANIFEST);
    }

    return (string) $xml->version;
}

function load_config(): array
{
    if (!is_file(CONFIG_FILE)) {
        fail('cwm-build.config.json missing at repo root');
    }

    $raw = file_get_contents(CONFIG_FILE) ?: fail('cannot read ' . CONFIG_FILE);
    $cfg = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

    if (!is_array($cfg) || empty($cfg['manifests']['extensions'])) {
        fail('cwm-build.config.json is missing manifests.extensions');
    }

    return $cfg;
}

$command = $argv[1] ?? 'build';
$version = load_package_version();
$cfg     = load_config();

if (!is_dir(DIST_DIR) && !mkdir(DIST_DIR, 0o755, true) && !is_dir(DIST_DIR)) {
    fail('cannot create build/dist');
}

switch ($command) {
    case 'info':
        info("package: {$cfg['extension']['name']} v{$version}");
        info('sub-extensions:');

        foreach ($cfg['manifests']['extensions'] as $ext) {
            info(sprintf('  - %-9s %s', $ext['type'], $ext['path']));
        }
        exit(0);

    case 'build':
        info("preparing pkg_cwmconnect v{$version}");
        info('sub-extension filesets are not yet defined — see TODO below');

        foreach ($cfg['manifests']['extensions'] as $ext) {
            $manifest = BASE_DIR . '/' . $ext['path'];

            if (!is_file($manifest)) {
                info(sprintf('  SKIP %-9s %s (manifest not found)', $ext['type'], $ext['path']));
                continue;
            }

            info(sprintf('  TODO %-9s %s (no fileset yet)', $ext['type'], $ext['path']));
        }

        info('phase 1 stub: no zip emitted. Real packaging lands once Phase 3 produces admin/src + site/src.');
        exit(0);

    default:
        fail("unknown command '{$command}'. Try: info, build");
}
