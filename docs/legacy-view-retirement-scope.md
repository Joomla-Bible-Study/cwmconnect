# Legacy site-view retirement — scope

**Status:** scoping (no code changed yet) · **Created:** 2026-06-04

## Goal

Remove the legacy Joomla-3-era **site** views that the v2 PC-synced directory has
superseded, so the front end runs on one modern stack. The payoff: the component
**Options** page collapses to the v2 essentials (Planning Center / Reports /
Printed Directory / Permissions), and ~10 legacy config tabs + their language
strings + dead helpers can finally be deleted (today they're kept *only* because
these views still consume them — see [PR #152](../admin/config.xml)).

## View inventory

| View | Era | Site menu item | Status |
|------|-----|----------------|--------|
| `members` | **v2** | — (the directory; reached via hidden routing) | **keep** — browse/list, search, PDF, KML |
| `myprofile` | **v2** | 243 (hidden) | **keep** — self-service profile |
| `households` | **v2** | — (sub-view of members) | **keep** |
| `directory` | legacy | 245 (hidden) | retire — superseded by `members` |
| `home` | legacy | 241 (hidden) | retire — landing page, superseded |
| `member` | legacy | 242 (hidden) | retire — **but see blocker below** |
| `category` | legacy | — | retire — category system is keep-but-hide (0 cats) |
| `categories` | legacy | 244 (trashed) | retire — same |
| `featured` | legacy | — | retire — **fully isolated, safe now** |

All legacy site menu items live in the **`cwmconnect-hidden`** menu ("Church
Directory (hidden)") — SEF routing targets, not public navigation.

## The core blocker: the v2 directory has no profile page

`site/tmpl/members/default.php` (lines 89, 108) links every member in the v2 list
**out to the legacy `member` view** (`view=member&id=…`). The v2 stack has **no
public profile view of its own** — it borrows the 481-line legacy `member`
view + its 7 templates (`default_address`, `_articles`, `_form`, `_household`,
`_links`, `_profile`).

**Therefore retirement is not a deletion task — it's a "build parity, then delete"
task.** The legacy `member` view cannot be removed until the v2 stack grows its
own profile page (or folds the profile into the `members` view).

Secondary blocker: the component's **default landing view is the legacy
`directory`** (`site/src/Controller/DisplayController.php:30`,
`$default_view = 'directory'`). Must repoint to `members` first.

Good news — **search parity already exists** in the v2 `members` model
(`filter.search`), so `directory`'s search is not a blocker.

## Dependency entanglements (what makes deletion non-trivial)

1. **Router hierarchy** (`site/src/Service/Router.php`) hard-codes
   `categories → category → member` with segment resolvers. Removing those views
   means rewriting the router to a flat `members`/`myprofile`/`households` scheme.
2. **Default view** = `directory` (above) — repoint to `members`.
3. **`member` view loads `CategoryModel`** to list "other members in this category"
   — dies with the category system (already empty), but the reference must be cut.
4. **Shared helpers:**
   - `RouteHelper` — **used by v2 `households`** (household photo routes) → **survives**.
   - `RenderHelper` — used only by legacy `home`/`featured`/`category`/`directory`
     → deletable *with* them.
5. **Config options** — the Member/Icons/Category/Categories/List-Layouts/Directory/
   Form/Home tabs are still read by these templates. They become dead only after
   the views go, at which point the big config + language prune is unlocked.

## What is already safe to delete

- **`featured`** — no menu item, no inbound v2 references, isolated. Could go in a
  standalone PR today (pending a product decision on whether "featured members" is
  a feature we want to keep in some form).
- **`category` / `categories`** — functionally inert already (0 categories, every
  member `catid=0`), but still wired into the router hierarchy, so they go in the
  router-simplification step, not before.

## Proposed phasing (each phase its own squash-merged PR)

**Phase 0 — v2 profile parity (the real work, blocks everything else)**
- Add a v2 public profile: either a new `members` layout (`view=members&layout=profile&id=`)
  or a dedicated lightweight view, rendering name/photo/household/contact from
  `#__cwmconnect_details` + `PhotoAccess`.
- Point the `members` list profile links at it; drop the `view=member` links.
- Repoint `DisplayController::$default_view` → `members`.
- **Decision needed:** which legacy profile features survive (see below).

**Phase 1 — router simplification**
- Rewrite `Router.php` to a flat scheme for `members`/`myprofile`/`households`;
  drop the `categories → category → member` chain and its resolvers.
- Verify SEF URLs for the v2 views still resolve; migrate the `cwmconnect-hidden`
  menu items (drop the retired-view targets, keep what `members` needs).

**Phase 2 — delete legacy views**
- Remove `home`, `member`, `category`, `categories`, `featured`, `directory`:
  their Controller / Model / View / `tmpl/<name>/` / menu-type XMLs.
- Drop `RenderHelper` and the now-unused bits of `MemberModel`/`DirectoryModel`/etc.
- Cut the `member`→`CategoryModel` reference.

**Phase 3 — config + language prune (the payoff)**
- Delete the now-dead config fieldsets (Member, Icons, Category, Categories,
  List Layouts, Directory, Form, Home) from `admin/config.xml`.
- Remove orphaned `COM_CWMCONNECT_*` language strings.
- The Options page is left with the v2 tabs only.

## Product decisions (settled 2026-06-04)

1. **Profile feature set** — the v2 profile keeps **photo / name / address /
   household** (default) **+ contact/enquiry form + vCard download**.
   **Dropped:** linked Joomla articles, custom links (A–E). → In Phase 0, port
   only `default_address`, `default_household`, `default_form` (enquiry) and the
   vCard action; skip `default_articles` and `default_links`.
   - **Social media links — PC DOES have them; our sync just doesn't pull them
     (corrected 2026-06-04).** PCO People exposes a first-class **`SocialProfile`**
     resource (attributes `site` = twitter/facebook/linkedin/instagram…, `url`).
     Brent Cordis's PC record shows X `@bcordis`, Facebook, LinkedIn, Instagram.
     Our `SyncEngine::PEOPLE_INCLUDES` does **not** request `social_profiles`, the
     `Client` has no handling, and there are no social columns — so we capture none
     of it. (My first pass reported "no social in PC" — that was a tooling blind
     spot: the PlanningCenter MCP tools only expose emails/phones/addresses +
     custom field_data, not `social_profiles`.) The legacy A–E links were manual
     free-text; **social is better sourced from PC.**
     → **Enhancement (small, mirrors existing field mapping):** add
     `social_profiles` to `PEOPLE_INCLUDES` (confirm it's an allowed include on the
     people index vs. a per-person fetch), map each `{site,url}` into the member row
     (e.g. a `pc_social` JSON/text column or per-platform columns), and render
     known platforms as icons on the v2 profile. Decouples from the legacy A–E
     links entirely.
   - **Campus website** (`pc_website`) is a separate, already-synced field — it's
     the *church's* site, used on the PDF cover, not per-member.
2. **Featured members** — **drop entirely.** It's isolated, so this can ship as a
   standalone first PR ahead of the rest.
3. **Categories** — keep-but-hide with zero categories; OK to remove the category
   browse views outright (the `catid` column / data model stays).

## Rough size

- Phase 0: **largest** — new profile rendering (~150–250 LOC + template) + decisions.
- Phases 1–2: mechanical but touches the router (highest regression risk) and deletes
  ~6 view stacks (~2,500 LOC across controllers/models/views/templates).
- Phase 3: deletes ~600 lines of `config.xml` + language strings; low risk once the
  views are gone.
