# CWM Connect — Church Photo Directory for Joomla 5/6

[![CI](https://github.com/Joomla-Bible-Study/cwmconnect/actions/workflows/ci.yml/badge.svg)](https://github.com/Joomla-Bible-Study/cwmconnect/actions/workflows/ci.yml)

`pkg_cwmconnect` is a church photo directory component for Joomla 5/6 on PHP 8.4. It ships with a bundled birthday/anniversary site module and a Smart Search adapter for directory entries. The package element is `com_cwmconnect`; the PSR-4 namespace root is `CWM\Component\Cwmconnect`.

## Status

The Joomla 3.x / PHP 7.x → Joomla 5/6 / PHP 8.4 **port is complete** (phases 0–9). The component now runs on a fully namespaced PSR-4 layout under `admin/src/` and `site/src/`, with a Dispatcher-pattern module, a `SubscriberInterface` finder plugin, `WebAssetManager`-managed assets, utf8mb4 schema, and PHPUnit + GitHub Actions CI on PHP 8.4.

**Modernization (v2.0) is in planning.** Tracker: [#111](https://github.com/Joomla-Bible-Study/cwmconnect/issues/111). The design covers a members-only directory with optional one-way sync from Planning Center People, a member self-service portal, a three-tier privacy model (master + scope + per-field), a signed-token KML feed, a self-service PDF, and a dedicated admin printable directory. Full spec at [`docs/v2-design.md`](docs/v2-design.md) — 19 framing decisions locked, 12-phase implementation plan (A → M).

## Where to look first

| Document | Why |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | Project layout, target shape, command reference, dev-environment setup. Migration phase table. |
| [`docs/v2-design.md`](docs/v2-design.md) | v2 design spec — decisions, data model, sync architecture, UI surfaces, phase plan. |
| Tracker [#111](https://github.com/Joomla-Bible-Study/cwmconnect/issues/111) | v2 phases + open questions, mirrored from the spec. |

## Quick start for contributors

```bash
composer install        # PSR-4 autoload + dev tooling (php-cs-fixer, phpunit, cwm/build-tools)
composer check          # lint:syntax + lint + test — runs in CI on every push
composer build          # build pkg_cwmconnect-*.zip (currently a stub; phase 1d note in CLAUDE.md)
```

Local dev against a real Joomla install (multi-version supported — j5 + j6 side by side):

```bash
cp build.properties.tmpl build.properties   # one-time per clone; gitignored
composer setup                                # interactive wizard for install paths + DB creds
composer link                                 # symlink the component, module, finder plugin, media into each install
composer verify                               # confirm Joomla registered the extensions; --fix to reconcile
```

Full dev-environment reference: see the "Dev environment" section in [`CLAUDE.md`](CLAUDE.md).

## Sibling repositories

Referenced during the port and the upcoming modernization:

- [Proclaim](https://github.com/Joomla-Bible-Study/Proclaim) — gold-standard Joomla 5/6 namespaced layout.
- [CWMScriptureLinks](https://github.com/Joomla-Bible-Study/CWMScriptureLinks) — first cwm-build-tools consumer; mirror its `composer.json` script aliases and CI workflow.
- [CWMLivingWord](https://github.com/Joomla-Bible-Study/CWMLivingWord) — closest structural analog (component + bundled module + bundled task plugin packaged as `pkg_*`).
- [cwm-build-tools](https://github.com/Joomla-Bible-Study/cwm-build-tools) — shared toolchain (release, package, ARS publish, changelog, language sync).

## License

GNU General Public License version 2 or later. See [LICENSE.txt](LICENSE.txt).
