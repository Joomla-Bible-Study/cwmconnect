# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

cwmconnect is in **active modernization**: a Joomla 3.x / PHP 7.x component is being rewritten as a Joomla 5/6 / PHP 8.3 package (`pkg_cwmconnect`) following the [Proclaim](../Proclaim) layout and the [cwm-build-tools](../cwm-build-tools) toolchain (with [CWMScriptureLinks](../CWMScriptureLinks) and [CWMLivingWord](../CWMLivingWord) as the wire-in references).

The legacy Joomla 3 source still lives under [admin/](admin/), [site/](site/), [modules/site/mod_birthdayanniversary/](modules/site/mod_birthdayanniversary/), and [plugins/finder/churchdirectory/](plugins/finder/churchdirectory/) — that code does not run on Joomla 5/6 and will be migrated, not preserved, as the phases land. Treat existing classes as **migration source**, not as patterns to follow for new code.

## Migration phases

| # | Phase | State |
|---|---|---|
| 0 | Decisions: package layout, naming, drop legacy search plugin | done |
| 1 | Toolchain swap (composer / cwm-build-tools / CI / package wrapper) | **done** |
| 2 | Manifests: `<namespace>` + `<compatibility>` + `<scriptfile>script.php</scriptfile>` for component, module, finder plugin | next |
| 3 | PSR-4 layout: `admin/src/` + `site/src/` + `admin/services/provider.php` + dispatcher + extension class | pending |
| 4 | API migration: `JFactory`/`JHtml`/`JText`/`JTable`/`JControllerLegacy` → namespaced `Joomla\CMS\*`; finder + module rewritten as subscribers | pending |
| 5 | PHP 8.3 idioms (strict_types, type declarations, property promotion, readonly) | pending |
| 6 | Frontend: drop LESS pipeline, register WebAssets via `joomla.asset.json` | pending |
| 7 | SQL: utf8mb4 + J5/J6 update files | pending |
| 8 | Tests + CI parity (`tests/unit/`, J5×J6 × PHP 8.3×8.4 matrix) | pending |
| 9 | Release plumbing: changelog XML, ARS category/stream IDs, updateservers | pending |

## Target shape (Proclaim-mirrored, unprefixed Joomla-standard naming)

```
churchdirectory.xml                          # type=component, J5+J6, php 8.3
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
composer.json                                # PHP 8.3 / PSR-4 / cwm/build-tools  ✅ phase 1
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

## Things to watch out for

- **Mixed-era state.** Until phase 3 lands, the repo contains *both* the new toolchain and the legacy Joomla 3 source. Don't take the legacy `JControllerLegacy` / `JFactory` patterns under [admin/controllers/](admin/controllers/) and [site/controllers/](site/controllers/) as templates — they are migration source, scheduled for replacement, and won't run on Joomla 5/6 even if they look superficially similar to modern Joomla code.
- **Build is a stub.** [build/build-package.php](build/build-package.php) currently exits 0 without producing a zip. CI exercises it for shape only. The real fileset rules land at the end of phase 3 once `admin/src/` + `site/src/` exist.
- **ARS IDs unset.** [cwm-build.config.json](cwm-build.config.json) has `categoryId: 0`, `updateStreamId: 0` placeholders. Run `composer ars-list` before the first real release to discover the right values.
- **Manifest version is `2.0.0-dev`.** Pre-modernization the component shipped at 1.8.3; we're using 2.0.0-dev to flag the J3→J5/6 break. Adjust before first stable release if a different scheme is preferred.
- **`<namespace>` is mandatory.** Joomla 5/6's MVCFactory class-name resolver only finds your unprefixed `MemberController` / `MemberModel` / `MemberTable` if the component manifest declares `<namespace path="src">CWM\Component\Churchdirectory</namespace>` and `services/provider.php` registers an `MVCFactory` provider. Both land in phase 2/3 — without them the legacy `Cwm`-prefix workaround would creep back in.
