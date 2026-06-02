# Site-side UI modernization plan

Goal: a cohesive, modern, responsive **Bootstrap 5** look across every front-end
(site) view, replacing the remaining Joomla-3 markup and unifying the
already-ported views under one design language. Built as a **shared design
system** so pages stay consistent and we don't hand-roll 24 templates.

## Current state (audit 2026-06-02)

Nine site views / ~24 templates. Legacy (BS2 `span*`/`pull-*`, `img-polaroid`,
`dl-horizontal`, `data-toggle`, removed `behavior.*`) is concentrated in:

| View | State | Work |
|------|-------|------|
| **member** (profile) + sub-tmpl (address, household, form, links, articles, profile) | Heavy J3 | **Rewrite** |
| **home** | Partly J3 (BS2 spans, polaroid) | **Rewrite** |
| members (browse) | BS5 (recent) | Polish for consistency |
| households | BS5 (recent) | Polish |
| categories / category (+ items, teamleaders, children, birthann) | BS5-ish | Polish |
| featured (+ items) | BS5-ish | Polish |
| directory (home, search) | BS5-ish | Polish |
| myprofile (+ placeholder) | BS5 (Phase H) | Polish |
| members/default_pdf, *KmlView/Vcf | non-HTML output | leave |

## Phase 0 — Design foundation (do first)

Reusable Joomla layouts under `site/layouts/cwmconnect/` so every view composes
the same building blocks:

- `photo.php` — responsive member/household `<img>` (thumb/medium srcset, WebP via
  proxy, lazy, click-to-full). Replaces the inline `<img>` repeated in 6 templates.
- `membercard.php` — directory card (photo + name + position + household chip).
- `pageheader.php` — page title + breadcrumb/back link + optional search slot.
- `sectioncard.php` — BS5 card with header (icon + title) + body slot.
- `emptystate.php` — consistent "nothing here" / CTA block.

Plus a small **`media/com_cwmconnect/css/cwmconnect.es6`** (built via the rollup
pipeline → `media/.../css`) for design tokens + a few component tweaks (card
hover, avatar ring, position chips). Keep it thin — lean on BS5 utilities.

Design language: BS5 cards, `row g-3` grids, `badge`/`btn`/`list-group`,
`icon-*` glyphs, `rounded`/`object-fit-cover` avatars, generous spacing,
mobile-first (`col-12 col-md-* col-lg-*`), dark-mode-safe (BS5 classes, no
hard-coded colours), a11y (alt text, heading order, aria).

## Phase 1 — Member profile (highest impact)

Hero header (photo + name + position/category/household + Email/vCard buttons)
over stacked **section cards** (Contact, Household w/ member thumbnails, Links,
Other info, Articles, Joomla profile, Email-this-member form). Single scrolling
page — drop `presentation_style` tabs/sliders/plain. Rewrite default.php + all 6
sub-templates onto the Phase 0 partials. (Detailed in the member-profile spec.)

## Phase 2 — Home / directory landing

Rebuild home/default.php on BS5: hero/intro, prominent "Browse directory" CTA,
search, and (when present) a featured-members grid using `membercard`. Fixes the
"empty when nothing featured" problem too.

## Phase 3 — Consistency polish (already-BS5 views)

members, households, categories, category, featured, directory, myprofile:
swap bespoke card markup for the shared `membercard`/`sectioncard`/`photo`
partials, align spacing/typography, unify empty states and the photo `srcset`.
Mostly mechanical; big consistency payoff.

## Order & sizing

0 (foundation) → 1 (member, biggest win + worst legacy) → 2 (home) → 3 (polish).
Phases 0–2 are the substantive build; phase 3 is incremental per view. Each phase
is its own PR (master needs **squash** merges — signed-commit rule).

## Out of scope / dependencies

- `presentation_style` config param becomes unused (prune from config.xml later).
- Category breadcrumbs depend on the **category-system review** (synced members
  have catid=0) — degrade gracefully meanwhile.
- AVIF photo format still deferred (no encoder); WebP/JPEG only.

Related: [[project-photo-subsystem]], [[project-category-system-review]].
