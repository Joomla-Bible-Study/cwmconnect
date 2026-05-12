# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

cwmconnect is in **active modernization**: a Joomla 3.x / PHP 7.x component is being rewritten as a Joomla 5/6 / PHP 8.4 package (`pkg_cwmconnect`) following the [Proclaim](../Proclaim) layout and the [cwm-build-tools](../cwm-build-tools) toolchain (with [CWMScriptureLinks](../CWMScriptureLinks) and [CWMLivingWord](../CWMLivingWord) as the wire-in references).

The legacy Joomla 3 source tree has been deleted in phases 4a–4d. Every component / module / plugin runtime path now lives under `admin/src/`, `site/src/`, `modules/site/mod_birthdayanniversary/src/`, and `plugins/finder/churchdirectory/src/`.

## Migration phases

| # | Phase | State |
|---|---|---|
| 0 | Decisions: package layout, naming, drop legacy search plugin | done |
| 1 | Toolchain swap (composer / cwm-build-tools / CI / package wrapper) | done |
| 2 | Manifests: `<namespace>` + `<compatibility>` + `<scriptfile>script.php</scriptfile>` for component, module, finder plugin | done |
| 3a | Dispatch infra: `admin/services/provider.php` + admin/site Dispatcher + Extension class | done |
| 3b | Admin PSR-4 entities + singletons + helpers + HTML services + custom fields (all under `admin/src/`) | done |
| 4a | Drop legacy admin entry stubs (`admin/api.php`, `admin/churchdirectory.php`, `admin/controller.php`) | done |
| 4b | Port `site/` to PSR-4 under `site/src/` (Controller, Model, View, Helper, Router, Service) | done |
| 4c | Rewrite `mod_birthdayanniversary` with the J5 Dispatcher pattern | done |
| 4d | Rewrite `plugins/finder/churchdirectory` as event subscriber (`SubscriberInterface`) | done |
| 5a | PHP 8.4 minimum: composer.json + manifest `<php minimum>` + CI workflow | done |
| 5b | `admin/src/` idioms: `declare(strict_types=1)`, tight type declarations, property promotion, `readonly`, PHP 8.4 features where they fit | done |
| 5c | `site/src/` idioms | done |
| 5d | Module + finder plugin idioms | done |
| 6 | Frontend: drop LESS pipeline, register WebAssets via `joomla.asset.json` | done |
| 7 | SQL: utf8mb4 + J5/J6 update files | done |
| 8 | Tests + CI parity (`tests/unit/`, J5×J6 × PHP 8.4 matrix) | done |
| 9 | Release plumbing: changelog skeleton + `<changelogurl>` + ARS placeholders | **done (placeholders)** |

Phases 0–9 cover the **port** from Joomla 3 to a Joomla 5/6 / PHP 8.4 package skeleton. After this point we'll be doing a lot of **rebuilding** against a live J5/J6 install — wiring up integrations, fixing what live testing surfaces, and populating real ARS category / stream IDs (currently `0` in [cwm-build.config.json](cwm-build.config.json)) before the first production release.

## Target shape (Proclaim-mirrored, unprefixed Joomla-standard naming)

```
churchdirectory.xml                          # type=component, J5+J6, php 8.4
churchdirectory.script.php                   # rewritten for namespaced base
admin/
  services/provider.php                      # registers MVCFactory + ComponentDispatcherFactory
                                             # + RouterFactory + CategoryFactory; PHP_VERSION_ID gate
  src/
    Controller/{Member,Members,Familyunit,Familyunits,Dirheader,Dirheaders,
                Position,Positions,Kml,Kmls,Cpanel,Geoupdate,Geostatus,
                Database,Reports,Info}Controller.php
    Model/{Member,Members,…}Model.php
    Table/{Member,Familyunit,Dirheader,Position,Kml}Table.php
    View/{Member,Members,…}/HtmlView.php
    Field/
    Helper/
    Dispatcher/Dispatcher.php
    Extension/ChurchdirectoryComponent.php
  forms/                                     # XML form definitions
  language/
  sql/{install.mysql.utf8.sql, updates/mysql/}
  tmpl/                                      # ← admin view templates land here
site/
  src/{Controller,Model,View,Helper,Dispatcher,Service}/
  forms/  layouts/  tmpl/  language/
modules/site/mod_birthdayanniversary/        # rewritten with Dispatcher pattern
plugins/finder/churchdirectory/              # rewritten as event subscriber
media/com_churchdirectory/{css,js,images}    # legacy LESS dropped; joomla.asset.json
build/
  pkg_cwmconnect.xml                         # package wrapper manifest          ✅ phase 1
  build-package.php                          # zip builder                        ✅ phase 1 (stub)
  cwmconnect-changelog.xml                   # ← phase 9
cwm-build.config.json                        # build-tools config                 ✅ phase 1
composer.json                                # PHP 8.4 / PSR-4 / cwm/build-tools  ✅ phase 1
.github/workflows/ci.yml                     # GH Actions                         ✅ phase 1
```

PSR-4 roots: `CWM\Component\Churchdirectory\Administrator\` → `admin/src/`, `CWM\Component\Churchdirectory\Site\` → `site/src/`.

**Naming convention:** unprefixed, Joomla-standard. The component manifest's `<namespace path="src">CWM\Component\Churchdirectory</namespace>` plus PSR-4 in [composer.json](composer.json) provide full isolation — no need for the `Cwm` class-name prefix that Proclaim uses (we're skipping that historical workaround). Cross-reference `com_content` in [joomla-cms](../joomla-cms) for the canonical pattern.

## Sibling repos referenced during migration

- [../Proclaim](../Proclaim) — gold-standard Joomla 5/6 namespaced layout. Mirror its `admin/services/provider.php`, dispatcher, extension class, and admin/site `src/` tree shape (but not its `Cwm`-prefixed class names).
- [../CWMScriptureLinks](../CWMScriptureLinks) — first cwm-build-tools consumer. Mirror its `composer.json` script aliases, `cwm-build.config.json`, and `.github/workflows/ci.yml`.
- [../CWMLivingWord](../CWMLivingWord) — closest *structural* analog (component + bundled module + bundled task plugin packaged as `pkg_*`). Mirror its `cwm-build.config.json` `manifests.extensions[]` shape and its `build/pkg_*.xml` wrapper manifest.
- [../cwm-build-tools](../cwm-build-tools) — the toolchain itself (CHANGELOG documents what's actually shipped; PackageBuilder is not yet implemented, so each consumer still owns its `build/build-package.php`).

## Common commands

```bash
composer install                 # PSR-4 autoload + dev tooling (cwm/build-tools, php-cs-fixer, phpunit)
composer lint:syntax             # parallel php -l across admin/src + site/src + modules + plugins
composer lint                    # php-cs-fixer dry-run
composer lint:fix                # php-cs-fixer write
composer test                    # phpunit (no tests yet — phase 8)
composer check                   # lint:syntax + lint + test
composer build                   # pkg_cwmconnect zip (stub — see phase 1d note)
composer release                 # full 8-step pipeline via cwm-release (phase 9 prerequisites needed)
composer bump-version            # cwm-bump (phase 9 prerequisites needed)
composer ars-list                # discover ARS categoryId/updateStreamId for cwm-build.config.json
```

The legacy `composer phpcs` script and the Phing build under `build/build.xml` were removed in phase 1; PHPCS / Joomla coding standards have been replaced by `php-cs-fixer`, and Phing has been replaced by `cwm-build-tools` + project-local `build/build-package.php`.

## Dev environment

The full dev-environment surface is driven by [cwm-build-tools](../cwm-build-tools) v0.4+:

```bash
cp build.properties.tmpl build.properties     # one-time per clone
composer setup                                  # interactive: install paths, DB creds
composer joomla-install                         # optional: download Joomla into each path
composer link                                   # symlink everything into each install
composer verify                                 # confirm extensions registered, --fix to reconcile
composer link-check                             # day-to-day: are the symlinks still healthy?
composer clean                                  # remove all dev symlinks
```

`composer link` reads [cwm-build.config.json](cwm-build.config.json) and creates **relative** symlinks for the component (`admin/`, `site/`, `media/`), the bundled module (`mod_birthdayanniversary`), the bundled finder plugin, and the internal `admin/churchdirectory.xml` mirror (the [churchdirectory.xml](churchdirectory.xml) source-of-truth lives at the root; Joomla expects it under `admin/` when the component is installed, so the linker mirrors it). `build.properties` is gitignored — secrets stay on your machine.

## Things to watch out for

- **Mixed-era state.** Until phase 3 lands, the repo contains *both* the new toolchain and the legacy Joomla 3 source. Don't take the legacy `JControllerLegacy` / `JFactory` patterns under [admin/controllers/](admin/controllers/) and [site/controllers/](site/controllers/) as templates — they are migration source, scheduled for replacement, and won't run on Joomla 5/6 even if they look superficially similar to modern Joomla code.
- **Build is a stub.** [build/build-package.php](build/build-package.php) currently exits 0 without producing a zip. CI exercises it for shape only. The real fileset rules land at the end of phase 3 once `admin/src/` + `site/src/` exist.
- **ARS IDs unset.** [cwm-build.config.json](cwm-build.config.json) has `categoryId: 0`, `updateStreamId: 0` placeholders. Run `composer ars-list` before the first real release to discover the right values.
- **Manifest version is `2.0.0-dev`.** Pre-modernization the component shipped at 1.8.3; we're using 2.0.0-dev to flag the J3→J5/6 break. Adjust before first stable release if a different scheme is preferred.
- **`<namespace>` is mandatory.** Joomla 5/6's MVCFactory class-name resolver only finds your unprefixed `MemberController` / `MemberModel` / `MemberTable` if the component manifest declares `<namespace path="src">CWM\Component\Churchdirectory</namespace>` and `services/provider.php` registers an `MVCFactory` provider. Both land in phase 2/3 — without them the legacy `Cwm`-prefix workaround would creep back in.
