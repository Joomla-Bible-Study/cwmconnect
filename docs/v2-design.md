# CWM Connect v2.0 — Design Spec

> Status: **in planning** (2026-05-12).
> Drives implementation that follows the J3→J5/6 port (phases 0–9).

## 1. What this is

CWM Connect is a Joomla 5/6 component that runs a **church photo directory**.
After the port we have a clean PSR-4 skeleton; v2 is the design for the actual
modernised feature set that will run on it.

Core jobs:

1. **Member-facing directory browse** — logged-in church members find each other.
2. **Admin-side directory maintenance** — staff manage who's in, fix data, print.
3. **Planning Center People sync** — optional one-way mirror of person + custom-field
   data from PC, with field-level locks so PC-sourced data can't drift in Joomla.
4. **Output formats** — self-service PDF "what I see," admin-side printable
   directory, KML feed for mapping.

## 2. Audience

**Members-only frontend.** All site-side views require login + a Joomla access
level (`Registered` or a custom `Directory Members` level). Public visitors
get the login wall.

Admin surface is the usual Joomla backend, gated by `core.manage` on
`com_cwmconnect`.

## 3. Decision summary

| # | Decision | Resolution |
|---|---|---|
| 1 | Audience | Members-only frontend (logged-in church members). Admin gated by `core.manage`. |
| 2 | Source of truth for member data | PC People when sync is configured; standalone install works without PC. |
| 3 | PC sync direction | One-way pull (PC → Joomla). Treat PC API as read-only. |
| 4 | PC sync scope filter | Configurable subset of **membership status** values (essential — not all PC people are members) AND optionally a specific **PC List**. |
| 5 | PC sync trigger | Scheduled task (`plg_task_cwmconnect_pc_sync`) + manual "Sync now" button. Webhook deferred to v2.x. |
| 6 | When PC person no longer matches the filter | Archive locally: set `published = 0` + `display_in_directory = 0`; keep the row + `pc_person_id` link. Reversible. |
| 7 | Per-field local override of PC values | Not in v1. PC is authoritative; admin fixes at the source. |
| 8 | Intentional "unlink from PC" button | Yes — toolbar action on the member edit screen. Nulls `pc_person_id`, unlocks fields. |
| 9 | PC custom field mapping | Manual mapping screen (admin picks which PC FieldDefinitions to mirror). Type-mapped to `com_fields` rows in context `com_cwmconnect.member`. Drop PC `file` type from v1. Soft-orphan stale mappings (warn, don't delete). |
| 10 | "Edit my record" affordance | Generic My PCO link (`https://my.planningcenteronline.com`) on front-end member profile. Admin "View in PC" link goes to staff profile. |
| 11 | Joomla user ↔ PC person identity binding | Deferred to v2.x. v1 ships the generic My PCO link only. |
| 12 | Privacy / opt-in | Three-tier model mirroring PC Church Center: (1) **master gate** `display_in_directory` bool — in directory at all? (2) **scope gate** `directory_scope` enum — `public` / `household` / `hidden`; viewers outside same household don't see `household`-scoped rows. (3) **per-field gate** `pc_shared_info` JSON — Church Center's per-field share preferences. Member-facing rendering hides fields where `pc_shared_info[key] === false`; absent key defaults to visible (opt-out model). PC sync drives all three; standalone installs use the master + scope only (per-field via portal is v2.x). `plg_privacy_cwmconnect` handles GDPR. |
| 13 | Photo handling | Cache locally on sync at `media/com_cwmconnect/photos/`. Detect changes via the URL hash PC bakes into avatar paths. |
| 14 | KML feed access | Signed token URL per user (`?token=<hmac>`), revocable from admin. Lives in `#__cwmconnect_feed_tokens`. |
| 15 | Self-service PDF | "Download what I see" — frontend button on the member list view. Renders the current filter set through `mpdf`. |
| 16 | Admin printable directory | Dedicated admin "Reports → Print Directory" workflow. v1 ships one template (alphabetical with photo + contact + household). Output stored at `media/com_cwmconnect/exports/`. |
| 17 | Admin override of `display_in_directory` at print | Print form exposes "Include members marked hidden" toggle (default off). Override is gated by `core.admin` and logged to `com_actionlogs`. Rows printed under override flagged visually in the PDF ("Staff copy"). |
| 18 | Children handling | Children become an automatic case of `display_in_directory = 0`. PC's `Person.child` boolean drives the flag on sync and locks it. Household view shows children's first name + age to viewers in the same household only; non-household viewers see a count ("…and 2 children"). Standalone installs use the manual flag; auto-detection from birthdate deferred to v2.x. |
| 19 | Member self-service portal | Each logged-in member edits their own record at `view=myprofile`. **PC-synced records**: only local fields (`display_in_directory`, photo override) are editable; PC-sourced fields are read-only with a link to My PCO. **Local-only records**: full edit of name, contact info, custom fields. Identity binding via `user_id` FK on the member row — PC sync attempts email-match on insert; admin can manually pair from the admin edit screen. |

## 4. Data model changes (J3 → v2)

```
#__cwmconnect_details += {
    pc_person_id              bigint    NULL  UNIQUE
        ;; FK to Planning Center person.id; null on local-only records.
    pc_last_synced_at         datetime  NULL
        ;; when sync last touched this row.
    user_id                   int unsigned NULL  UNIQUE
        ;; FK to #__users.id; null when the member has no Joomla account.
        ;; Drives view=myprofile lookup. PC sync attempts email-match on
        ;; insert; admin can manually pair from the edit screen.
    display_in_directory      tinyint(1) NOT NULL DEFAULT 1
        ;; master visibility gate. Auto-set to 0 for PC.child=true OR for
        ;; PC.directory_status='no' (flag locked in either case). Editable by
        ;; admin and by the row's own user.
        ;; Admin print mode can override; member-facing views always honor it.
    directory_scope           enum('public','household','hidden')
                              NOT NULL DEFAULT 'public'
        ;; second-tier gate. Mirrors PC.directory_status when synced:
        ;;   'public'   → visible to all logged-in members
        ;;   'household'→ visible only to viewers in the same household
        ;;   'hidden'   → not in directory (equivalent to display_in_directory=0)
        ;; Standalone installs: admin-controlled per-row.
    pc_shared_info            json NULL
        ;; Mirrors PC.directory_shared_info — per-field share preferences
        ;; from PC Church Center. Example:
        ;;   {"home_address": true, "primary_phone_number": false,
        ;;    "primary_email_address": true, "birthdate": false}
        ;; Rendering: for each PC-mapped field key, if pc_shared_info[key] is
        ;; explicitly false, hide that field in member-facing views. Absent
        ;; key defaults to visible (opt-out model — fits a church directory
        ;; whose default mode is "share unless I said otherwise"). When the
        ;; column is null (local record, or PC field not synced), render normally.
    image_filename            varchar(255) NULL
        ;; relative path under media/com_cwmconnect/photos/; null when no photo.
    image_hash                varchar(64)  NULL
        ;; PC avatar URL hash; used to detect changes on sync.
}
```

New table:

```
#__cwmconnect_feed_tokens (
    id              int unsigned auto_increment PRIMARY KEY,
    user_id         int unsigned NOT NULL,         -- Joomla user_id
    token_hash      char(64) NOT NULL UNIQUE,      -- SHA256 of the secret
    label           varchar(120) NOT NULL,         -- "Jane's Google Map"
    created_at      datetime NOT NULL,
    last_used_at    datetime NULL,
    revoked_at      datetime NULL,
    INDEX (user_id)
);
```

Custom field metadata lives in Joomla's existing `#__fields` table — we use
the `params` registry to store our backlinks:

```
#__fields.params  (Registry JSON) += {
    "pc_field_definition_id": "12345",
    "pc_field_name":          "Baptism Date"
}
```

No new tables for households / dirheaders / positions — those existing entities stay
and gain a `pc_*_id` column when they have a PC analog (Household for familyunit,
Campus for dirheader).

## 5. PC sync architecture

> **API version pinned**: [`2025-11-10`](https://api.planningcenteronline.com/docs/apps/people/versions/2025-11-10).
> PC People API versions are date-stamped and stable. All field names + URL
> shapes in this section reference that spec. The PC client should pin via
> the `X-PCO-API-Version` header so we don't get surprised by a future
> default-version bump.

### 5.1 Trigger paths

| Path | Implementation |
|---|---|
| Scheduled | `plg_task_cwmconnect_pc_sync` exposes one task type ("Sync from Planning Center"); admin schedules cadence via Joomla's standard task UI |
| Manual | `task=geoupdate.start`-style controller endpoint behind a Cpanel "Sync now" button (AJAX, JSON response, progress polling identical to the geocode worker) |
| CLI | `php cli/joomla.php cwmconnect:pc:sync` for ops use |

### 5.2 Configuration (admin-side)

`Components → CWM Connect → Options` adds a "Planning Center" tab:

```
[ ] Enable PC sync

PC Personal Access Token       [................]
PC Application ID              [................]

Include people whose membership status is:
   ☑ Member
   ☑ Regular Attender
   ☐ Guest
   ☐ Visitor
   ☐ Inactive
   ☐ (other statuses present in the org)

Restrict further by PC List      [— none —          ▾]
   ↑ optional; AND'd with the status filter above.

When a person no longer matches the filter:
   ⦿ Archive locally (display_in_directory = 0, keep the row)
   ◯ Hard-delete the row

Photo cache:
   [Refresh now]   [Last refreshed: 2026-05-12 09:14]

Custom field mappings:  [Open mapping screen →]
```

### 5.2.1 Person attributes we sync (and don't)

The PC People `Person` resource exposes the attributes below. We mirror only
those relevant to a directory, and explicitly skip ones that are sensitive,
PC-internal, or out of scope.

| Person attribute | Sync? | Notes |
|---|---|---|
| `first_name`, `last_name`, `middle_name`, `nickname`, `given_name`, `name` | yes | Core identity |
| `birthdate`, `anniversary` | yes | Subject to per-field `pc_shared_info` gate |
| `gender` | yes | Subject to per-field gate |
| `avatar`, `demographic_avatar_url` | yes | Drives photo cache (§ photo handling) |
| `child` | yes | Drives `display_in_directory = 0` + lock |
| `membership` | yes | Filter input (Decision #4) |
| `status` | yes | Filter input (active vs inactive) |
| `inactivated_at` | yes | For audit / future filter rules |
| `directory_status` | yes | Maps to `directory_scope` enum |
| `directory_shared_info` | yes | Mirrored to `pc_shared_info` JSON |
| `grade`, `graduation_year`, `school_type` | yes | Useful in household view for kids |
| `primary_campus` (relationship) | yes | Maps to dirheader (Campus) |
| Emails (separate resource) | yes | `?include=emails`, primary email mirrored to local |
| Phone numbers (separate resource) | yes | `?include=phone_numbers` |
| Addresses (separate resource) | yes | `?include=addresses` |
| Households (separate resource) | yes | `?include=households` → familyunit mapping |
| `mfa_configured`, `login_identifier`, `stripe_customer_identifier` | **no** | PC-internal |
| `accounting_administrator`, `site_administrator`, `can_create_forms`, `can_email_lists`, `people_permissions`, `resource_permission_flags` | **no** | PC role assignments, not directory facts |
| `created_at`, `updated_at`, `created_by` | observed only | Used to detect "what changed since last sync"; not stored in mirror |
| `remote_id` | **no** | PC-internal ID for legacy import linkage |

**Never synced, even if requested via `?include=` or a future config option:**

| Attribute / resource | Why |
|---|---|
| `medical_notes` | Confidential pastoral / health information. Not directory data. The PC client should **never** request this field on Person fetches, and any future fetch helper that does \*get receive it should drop the field before storage. |
| `passed_background_check` | Internal church compliance / volunteer screening state. Not directory information; misuse risk is real (visible "this person failed a background check"). Skip even on debug logging. |
| Notes (separate resource: `?include=notes`) | Free-text pastoral notes attached to a person. Same reasoning as `medical_notes`. Never `?include=notes`. |
| Background check details (separate resource: `?include=background_checks`) | Compliance / screening records. Never fetch. |
| App permissions list (`?include=person_apps`) | Role assignments for PC apps; not relevant to a directory. |

The "no" rows further up the table aren't security-critical (church staff with admin access to cwmconnect typically have access to PC anyway), but they're noise in a directory context. The "**Never synced**" rows in *this* sub-table are different — they're sensitive data the church staff specifically expect to live *only* in PC. Excluding them by design (not by config) keeps the trust boundary clean.

### 5.3 Per-person sync

For each person matching the filter:

1. `GET /people/v2/people/{id}?include=field_data,emails,phone_numbers,addresses,households,primary_campus`
2. Upsert `#__cwmconnect_details` keyed on `pc_person_id`. Lock the PC-mapped
   columns (write only on sync; admin form renders them read-only).
3. For each PC FieldDatum that has a mapping in `#__fields`:
   `FieldsHelper::setFieldValue('com_cwmconnect.member', $memberId, $fieldId, $value)`.
4. If `avatar_url` hash differs from `image_hash`: download, store at
   `media/com_cwmconnect/photos/{pc_person_id}.<ext>`, update both columns.
5. Update `pc_last_synced_at = NOW()`.

### 5.4 Sweep step

After per-person pass:

- Local rows with `pc_person_id` not present in the current PC result → archive
  per config (display_in_directory=0 + published=0).
- Reappearing PC people → un-archive on next sync.

### 5.5 Audit + observability

Every sync run writes one `com_actionlogs` row summarising: rows seen, added,
updated, archived, errored. Errors raised mid-sync log to the same log channel
and continue (one bad person doesn't abort the run).

## 6. UI surfaces

### 6.1 Front-end (member-facing, login-gated)

| Route | View |
|---|---|
| `option=com_cwmconnect&view=directory` | Landing — search box + filter facets (category, dirheader, household), default to photo grid |
| `option=com_cwmconnect&view=members` | Filtered member list. Photo grid / table toggle. Has "Download PDF" + "Get KML feed" buttons. |
| `option=com_cwmconnect&view=member&id=N` | Single member profile. Photo, contact links (`tel:` / `mailto:`), household member list, position, optional "Update your info in Planning Center" link |
| `option=com_cwmconnect&view=households` | Browse by household |
| `option=com_cwmconnect&view=members&format=pdf` | Self-service PDF render of the current filter |
| `option=com_cwmconnect&view=members&format=kml&token=…` | KML feed (signed token; bypasses session auth) |

### 6.2 Admin

| Menu | View |
|---|---|
| Dashboard | Cpanel with schema-findings banner + sync-status panel ("Last synced: …", "Sync now") |
| Members | List view with PC-linked indicator column; per-row dropdown includes "View in Planning Center" when linked |
| Member edit | Locked PC fields render read-only with badge; toolbar has "Sync now" + "Unlink from PC" actions when linked |
| Family Units | Familyunit list (PC-linked to households) |
| Dir Headers | (Campus mirror when PC-linked) |
| Positions | Local-only roles (no PC equivalent) |
| Categories | Joomla core |
| Reports → Print Directory | Filter form + template picker + "Include hidden members" toggle (admin-only). Generates PDF, stores in `media/com_cwmconnect/exports/<date>.pdf`, gives a download link |
| Options → Planning Center | Sync config (see §5.2) |
| Options → PC Field Mappings | Per-org PC FieldDefinition → `com_fields` mapping table |
| Options → Feed Tokens | Per-user KML token management |

### 6.3 Member edit form (PC-linked example)

```
┌──────────────────────────────────────────────────────────┐
│ Edit Member: Jane Smith       [⚓ Synced from PC ▾]     │
│                              ├ Sync now                  │
│                              ├ Unlink from PC            │
│                              └ View in Planning Center   │
├──────────────────────────────────────────────────────────┤
│ First name    [Jane            🔒]                        │
│ Last name     [Smith           🔒]                        │
│ Email         [jane@…          🔒]                        │
│ Phone         [+1-555-…        🔒]                        │
│ Address       […               🔒]                        │
│ ─── Custom fields (mirrored from PC) ────────────────────│
│ Baptism Date  [2018-06-15      🔒]                        │
│ Volunteer Bg  [Cleared          🔒]                        │
│ ─── Directory-only ──────────────────────────────────────│
│ Alias         [jane-smith       ]   ← editable           │
│ Photo         [override...      ]   ← editable           │
│ Position      [Elder ▾          ]   ← editable           │
│ Show in       [☑] directory          ← editable          │
│ Featured      [☑]                   ← editable           │
└──────────────────────────────────────────────────────────┘
```

`Show in directory` is editable but **read-only when the row was set to 0 by
the PC `directory_status` sync** — same lock mechanism. Admin can unlink first
if they want to override.

## 7. Children handling

> Originating ask: issue #68 — kids exist in the data but shouldn't appear
> in directory output or search; protect under-age members.

Children are modelled as an **automatic case of `display_in_directory = 0`**,
not as a parallel concept. The same flag that hides opted-out adults also hides
kids; the difference is who sets it and whether it's lockable.

### 7.1 Where the flag comes from

| Source | Behaviour |
|---|---|
| PC sync | When `pc_person.child === true` (PC computes this from `birthdate` against the org's child-age threshold), set `display_in_directory = 0` on the local row **and** mark the flag locked (same lock machinery as PC-mapped fields). Admin cannot accidentally enable visibility on a minor while PC says they're under age. |
| Manual admin | Standalone (non-PC) install or PC-unlinked record: admin sets the flag directly. v1 ships no birthdate-based auto-detection — admin owns the call. |

### 7.2 Visibility surfaces

| Surface | Rule |
|---|---|
| Front-end member browse | `WHERE display_in_directory = 1` (already in design — kids fall out for free). |
| Front-end search | `plg_finder_cwmconnect` indexer skips rows where `display_in_directory = 0` so com_finder never returns kids. |
| Household / familyunit view | Viewer's household membership decides. **In same household**: kids' first name + age (or birthday) visible. **Not in household**: aggregate only ("…and 2 children"); no names. |
| Admin members list | Default filter hides children. Toolbar toggle "Show children" available, gated by `core.manage`. Same toggle the admin print uses for the "include hidden" override. |
| Admin printable directory | Same `core.admin` override that includes opted-out adults also surfaces children. Override is logged to `com_actionlogs`; output marked "Staff copy" in the PDF. |

### 7.3 Schema impact

None new. The existing `display_in_directory` column on `#__cwmconnect_details` already covers it.

The legacy `members.children` TEXT column (J3-era free-text list of kids' names per parent) becomes **deprecated** in v2:

- PC-synced installs: don't write to it; don't display it. Kids are first-class rows with household links instead.
- Standalone installs that filled it in: keep the column readable so the data isn't lost, but the new admin form doesn't expose it for editing.
- Drop the column entirely in v3 once we're confident no one's relying on it.

### 7.4 Implementation phases

| Phase | What lands |
|---|---|
| C (sync core) | PC `child` boolean drives `display_in_directory = 0` + flag lock |
| F (admin form lock) | Locked rendering of `display_in_directory` when PC says child |
| G (front-end member views) | Browse + search already filter; household view branches on viewer-household membership |
| K (admin print) | "Include children" toggle reuses the `core.admin` "include hidden" path |
| M (polish) | Finder indexer skip rule |

No new phase added — folds into the existing plan.

### 7.5 Out of scope for v1 (children-specific)

- **Birthdate-based auto-detection in standalone installs.** PC owns the threshold logic for PC installs; standalone admins set the flag manually for v1. Auto-detection (configurable threshold, age-based UI hint) deferred to v2.x.
- **Per-child profile pages.** Kids appear in household context only; no `view=member&id=N` route renders for a record where `display_in_directory = 0` (returns 403).
- **Child-specific custom field privacy rules.** Custom fields locked to PC's mapping; if PC chooses to expose a custom field on a child record, it goes through the same `display_in_directory` filter as everything else (i.e. invisible front-end, household view only to same-household viewers).

## 8. Member self-service portal

Every logged-in member visits `option=com_cwmconnect&view=myprofile` to view +
edit their own record. The form's depth of control depends on where the data
came from:

| Field group | PC-synced record | Local-only record |
|---|---|---|
| Photo | Read-only; member may upload a local *override* photo that wins over PC's avatar in the directory | Editable directly |
| Name, contact info, address, household | Read-only with "Update your info in Planning Center" link | Editable |
| Custom fields | Read-only if PC-mapped | Editable |
| `display_in_directory` flag | Editable, unless PC says `child=true` (locked) | Editable |
| Sort name preferences | Editable | Editable |

This view is the standalone-install equivalent of PC's Church Center directory
preferences — when the church isn't running PC, members still need a way to
manage their own visibility and basic info. When PC IS running, the same view
gives members a single place to flip local-only knobs (photo override, opt-out
toggle) without needing the admin to do it.

### 8.1 Identity binding

Each member row gets a nullable `user_id` FK to `#__users.id`. The portal looks
up the current viewer's member record via `WHERE user_id = currentUserId`.

| Scenario | Behaviour |
|---|---|
| User logs in, has a paired member row | `view=myprofile` renders the edit form |
| User logs in, no paired member row | Render a placeholder: "You're not in the church directory. Please contact the office to be added." with admin email |
| Multiple member rows somehow paired to same user | Impossible — `user_id` is `UNIQUE` |

### 8.2 Pairing strategies

| Trigger | How `user_id` gets populated |
|---|---|
| PC sync, email match | On insert/update, sync queries `#__users` for a matching email; if exactly one match, sets `user_id` |
| Admin manual pair | Admin edit form has a "Linked Joomla user" select2 field — type a name to bind |
| User registers, matching member exists | `onUserAfterSave` listener tries email match in reverse and pairs |
| Member created locally with email | `onContentAfterSave` for `com_cwmconnect.member` tries email match |

All four use the same email-match heuristic; the only difference is what event
triggers the check. Conflict resolution: if there's already a `user_id` set on
the row, the new pairing attempt is skipped (admin must unlink first).

### 8.3 Edit conflict handling (PC mode)

When a PC-synced member tries to edit a locked field (e.g. by URL-hacking past
the read-only attributes), the controller short-circuits:

- Save fails with a flash message: "This field is managed by Planning Center.
  Update it at [my.planningcenteronline.com](https://my.planningcenteronline.com)."
- No silent merge; no partial update.

### 8.4 Out of scope (portal-specific)

- **Per-field privacy toggles** (member-controlled "hide my phone" / "show my
  email"). Members-only audience makes this lower-priority. v2.x polish.
- **Account creation flow** for non-Joomla-user members. v1: admin or the
  Joomla user registration plugin creates the account, then sync/admin pairs.
- **Bulk pair-by-email tool** in admin. v1: per-row. v2.x: bulk reconcile.

## 9. Joomla 5/6 extension surface

Inventory of Joomla machinery the implementation will lean on (no decisions
here, just what we'll touch so the rough cost is visible):

- **com_fields** — custom-field storage + admin UI for the per-org mirrored
  PC fields. Context `com_cwmconnect.member`. Lock mechanism via
  `onContentPrepareForm`.
- **com_finder** — already wired (`plg_finder_cwmconnect`). Smart Search
  indexes the directory for free.
- **com_categories + com_tags** — existing category support stays; PC Tags
  can mirror into `com_tags` if useful (v2 polish).
- **com_privacy + com_consents** — ship `plg_privacy_cwmconnect` for export /
  forget-me handling.
- **com_workflow** (J4+) — not used in v1; could model member lifecycle
  (pending → active → archived) later.
- **com_actionlogs** — sync audit trail.
- **com_mails** — email templates: "your sync failed" admin notification,
  "welcome to the directory" member email (v2).
- **Joomla Task Scheduler** (`plg_task_*`) — PC sync schedule.
- **Joomla Console (CLI)** — `cwmconnect:pc:sync` for ops.
- **Joomla\CMS\Http\HttpFactory** — PC API client (built-in Guzzle wrapper).
- **Joomla\CMS\Crypt\Crypt** — KML feed token HMAC.
- **WebAssetManager** — already in place for our CSS/JS.
- **Layout overrides** — templates can override our admin print template under
  `templates/<tpl>/html/com_cwmconnect/print/…`.
- **mpdf/mpdf** (composer dep) — HTML/CSS → PDF for both self-service and
  admin print. ~15 MB vendor footprint.

## 10. Out of scope for v1

Capturing now so reviewers don't expect these:

- **Bidirectional sync** — Joomla never writes back to PC.
- **PC webhook ingestion** — only polling-based sync.
- **Joomla user ↔ PC person identity binding** — `My PCO` link is generic.
- **Per-field local overrides of PC values** — if you want different data, fix it in PC.
- **PC `file` field type mirroring** — text/date/select/checkbox only.
- **Admin print custom templates** — v1 ships one fixed template.
- **Public-facing directory** — members-only login wall is mandatory.
- **Bulk CSV / vCard export** — separate ask; not in this scope.
- **Email blast integration** — outside scope; defer to existing
  Joomla mailing extensions or PC's own broadcast tools.

## 11. Open questions (still need to decide before implementation)

These didn't come up in the framing pass but will need answers when we start
building. None block the scope above; flagging so they're tracked:

- ~~**PC `Person.directory_status` API availability**~~ — **resolved.** Both
  `directory_status` and `directory_shared_info` are first-class Person
  attributes in the PC People API (verified against the 2025-11-10 spec). The
  three-tier privacy model in Decision #12 is the implementation; sync mirrors
  both fields directly.
- **PC auth model**: Personal Access Token (simpler, per-user) vs OAuth app
  (cleaner, per-install)? PC supports both. Default to PAT for v1 to avoid
  the OAuth callback dance.
- **PC API rate limits**: PC publishes per-token limits. With a few hundred
  members the sweep is fine, but custom-field-heavy orgs may need pagination
  + backoff. Code-level concern, not a design decision.
- **Print template HTML/CSS** — needs a designer pass for the alphabetical
  template. The technical scaffold (mpdf + Joomla layout pipeline) is settled;
  the visual is not.
- **Default membership statuses** — first install should pre-select something
  sensible. Probably "Member" + "Regular Attender" (most common). User can
  edit.
- **Feed token rotation policy** — do tokens auto-expire (30 days?) or live
  forever until revoked? Forever-until-revoked is simpler; auto-expiry is
  more security-conscious. Pick one before shipping.

## 12. Sequencing (rough phase plan, not commitments)

Implementation phases after this spec is locked, ordered to land working
software early:

1. **Phase A — data model.** Add the new columns + table. Migration SQL.
   Existing admin still works.
2. **Phase B — PC client + config screen.** Token storage, PC API helpers,
   options screen. No sync yet — just "save my config."
3. **Phase C — sync core.** People + filter, no custom fields, no photos.
   First end-to-end working sync.
4. **Phase D — custom fields.** Mapping screen + field-data writes.
5. **Phase E — photos.** Avatar download + cache.
6. **Phase F — admin form lock.** Read-only rendering of PC-mapped fields.
7. **Phase G — front-end member views.** Browse, profile, search.
   Members-only access wall. Includes household-view children visibility rules.
8. **Phase H — member self-service portal.** `view=myprofile`, user↔member
   pairing (email match + admin pair UI + `onUserAfterSave` listener), local
   editing in standalone mode, locked rendering + My PCO link in PC mode.
9. **Phase I — self-service PDF.** mpdf integration, `format=pdf` view.
10. **Phase J — KML feed.** Tokens table, signed-URL view, admin UI.
11. **Phase K — admin print.** Reports → Print Directory workflow.
12. **Phase L — privacy plugin.** GDPR export / forget.
13. **Phase M — polish.** Action logs UI, sync error notifications, finder
    indexer skip for hidden rows, edge cases surfaced by C–L.

Each phase ships an independent PR. We don't need to do them in this exact
order if priorities shift; A → B → C is the only hard prereq chain.

## 13. Printed directory PDF — options catalog & plan

The current PDF (`site/src/View/Members/PdfView.php` + `tmpl/members/default_pdf.php`)
is a flat 5-column contact table. The goal is a real **pictorial directory**
PDF in the spirit of the major commercial tools. This section captures the
full option surface those tools offer (so we don't under-scope), maps each to
our data, and lays out a phased build.

### 13.1 Reference formats (what the market offers)

Surveyed against a real Instant Church Directory export (`churchdirectory.pdf`,
30pp, US-Letter) plus the published option lists of Instant Church Directory,
Universal Church Directories (UCDir), and Church Pictorial.

**Sample structure (Instant Church Directory):** full-bleed photo cover →
pastor welcome letter → "Our Staff" (photo-left, name/title/bio-right) →
"Our Church Activities" (photo collage + captions) → **member directory**
(4 entries/page, photo-left, `LASTNAME, First [and Spouse]` + street +
city/state/zip + anniversary + phone(s) + email, alphabetical by surname) →
back-cover event flyer.

**Member-entry layout styles (the core choice):**

| Style | Density | Per entry | Source |
|---|---|---|---|
| Photo + details beside | 4 / page | photo + name + full contact to the right | ICD "Photo + details"; UCDir "Premier Connect" (8/pg) |
| Photo grid + names only | 9–16 / page | photo + name only (details on separate roster) | ICD "12 photos + roster"; UCDir "Premier/Traditional" |
| Photo + contact below | ~6–12 / page | photo on top, name + short contact under it | Church Pictorial entry style |
| Roster only (no photos) | many / page | text listing of families/individuals + contact | ICD "Roster only" (≈ our current table) |
| Photos-front / roster-back | — | pictorial section, then a text roster section | ICD page-arrangement option |

**Document sections (toggleable, drag-to-reorder in the commercial tools):**
cover page · pastor/welcome letter · staff/leadership pages · activities /
ministry pages · **member listing** · alphabetical text roster · custom
inserts (PDF/image upload) · back cover.

**Appearance settings:**
- Color vs. black-and-white.
- Page size: US-Letter 8.5×11 **or** booklet 5.5×8.5 (half-letter).
- Font family + font size (large-print roster option for older members).
- Image quality / resolution.
- Photo corners: square vs. rounded; optional drop shadow.
- Alphabetical section dividers (A, B, C…).
- Index / table of contents.

**Sort / grouping:** by surname (default), by family/household, by
ministry/position, by category (directory header). Couples shown as
"`Surname, First and Spouse`"; children optionally listed.

### 13.2 Mapping to our data model

Everything the photo-detail layout needs already exists on `#__cwmconnect_details`:

| Directory element | Column(s) |
|---|---|
| Photo | `image` (root-relative path) or PC avatar cached under `media/com_cwmconnect/photos/` |
| Name / family name | `surname`, `lname`, `name`, `spouse`, `children` |
| Street / city / state / zip | `address`, `suburb`, `state`, `postcode`, `country` |
| Phones / email | `telephone`, `mobile`, `email_to` |
| Anniversary / birthday | `anniversary`, `birthdate` (both present, currently `DATETIME`) |
| Position / role (staff section) | `con_position` |
| Grouping | `funitid` (household), `catid` (category), `kmlid`, `dirheader_name` |
| Cover church name/contact | component options (K.2) — the dirheader table has no contact columns; PC campus can supply name + address (see §13.5) |

**mpdf note:** mpdf cannot reliably fetch remote image URLs — resolve every
photo to an absolute filesystem path (`JPATH_ROOT . '/' . image`) and fall back
to a neutral silhouette placeholder when the file is missing, so a member with
no photo still renders a clean cell.

### 13.3 Decisions locked with the user (2026-05-29)

- **Primary member-entry layout:** *Photo + details beside* (4/page) — closest
  to the sample. Built first.
- **Sections to include:** cover page, staff/leadership, alphabetical section
  headers, and the member listing — **each a component option (default on)**, so
  "member listing only" is reachable by toggling the others off (resolves the
  mutually-exclusive selection cleanly).

### 13.4 Build phases (printed directory)

1. **K.1 — Photo-detail layout. ✅ DONE.** `default_pdf.php` rewritten to the
   photo-left/details-right entry, alphabetical by surname, photo→path resolver
   + initials placeholder. Couples rendered "Surname, First and Spouse."
2. **K.2 — Cover + sections. ✅ DONE.** "Printed Directory" `config.xml`
   fieldset: cover page (image + church name/address/phone/email/website), staff
   section (driven by `con_position`), alphabetical (A/B/C…) dividers — each a
   component option (default on). Cover fields ARE the manual/override layer
   (see K.6). `MembersModel` now selects `anniversary`.
3. **K.3 — Appearance options.** `config.xml`: layout style, color/B&W,
   page size (Letter / booklet), font size, photo corners. Wire into PdfView's
   mpdf config + the template.
4. **K.4 — Additional layout styles.** Photo-grid (names only) + roster-only,
   selectable via the layout option; optional photos-front/roster-back ordering.
5. **K.5 — Admin print parity.** Point the admin Reports → Print Directory path
   (`ReportbuildHelper::getPdf()`, currently deferred) at the same renderer, with
   the hidden-member override the admin export is allowed.
6. **K.6 — Planning Center sourcing + override (cover/church info). ✅ DONE.**
   See §13.5.

K.1, K.2, K.6 are done; K.3–K.5 layer on the remaining option set.

### 13.5 Planning Center sourcing & override

Requirement (user, 2026-05-29): data that *can* come from Planning Center
should be **syncable from PC and locally overridable**.

**What PC can supply for the directory:**
- **Member fields** (name, address, anniversary, phones, email) + **photos** —
  already synced (Phases C–E). The member listing is therefore already
  PC-sourced; local edits in standalone mode, locked rendering in PC mode
  (Phase F pattern).
- **Cover church name + postal address** — available from the **PC campus**
  object (`people_church_campuses`: `name`, `street`, `city`, `state`, `zip`,
  `country`, `phone_number`, `contact_email_address`, `website`). Verified live:
  the org returns one campus ("NFSDA Church", 2800 Blair Boulevard, Nashville TN
  37213); phone/email/website were null there, so those stay manual.

**Implemented (K.6):**
1. `#__cwmconnect_dirheader` extended with `pc_street/city/state/zip/country/
   phone/email/website` (install SQL + `updates/mysql/2.0.0-20260529.sql`).
2. `Client::listCampuses()` → `CampusMapper` (pure, unit-tested) →
   `DatabaseCampusRepository::upsertByPcCampusId()`, orchestrated by
   `CampusSync`. `CpanelController::pcSync()` runs it best-effort (campus
   failures never abort the people sync), stamping `pc_last_synced_at`.
3. Override model: a non-empty manual `config.xml` cover field wins; a blank
   field falls back to the synced PC campus value. Gated by the
   `pdf_cover_use_pc` toggle (`showon` pc_enabled). Standalone installs (PC off)
   always use the manual values.
4. `PdfView` resolves each cover field as `manual_override ?? pc_campus ??
   sitename` via `DatabaseCampusRepository::findPrimary()`.

Mirrors how PC-mapped member fields already work, so the cover behaves
consistently with the rest of the PC integration. **Needs live verification**
on a J5/J6 install with PC connected (the campus fetch + DB write can't run in
the local harness).
