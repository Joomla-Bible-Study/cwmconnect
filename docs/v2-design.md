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
| 12 | Privacy / opt-in | Local `display_in_directory` bool on each member. PC's `directory_status` drives the flag on sync. `plg_privacy_cwmconnect` handles GDPR export/delete requests. |
| 13 | Photo handling | Cache locally on sync at `media/com_cwmconnect/photos/`. Detect changes via the URL hash PC bakes into avatar paths. |
| 14 | KML feed access | Signed token URL per user (`?token=<hmac>`), revocable from admin. Lives in `#__cwmconnect_feed_tokens`. |
| 15 | Self-service PDF | "Download what I see" — frontend button on the member list view. Renders the current filter set through `mpdf`. |
| 16 | Admin printable directory | Dedicated admin "Reports → Print Directory" workflow. v1 ships one template (alphabetical with photo + contact + household). Output stored at `media/com_cwmconnect/exports/`. |
| 17 | Admin override of `display_in_directory` at print | Print form exposes "Include members marked hidden" toggle (default off). Override is gated by `core.admin` and logged to `com_actionlogs`. Rows printed under override flagged visually in the PDF ("Staff copy"). |

## 4. Data model changes (J3 → v2)

```
#__cwmconnect_details += {
    pc_person_id              bigint    NULL  UNIQUE
        ;; FK to Planning Center person.id; null on local-only records.
    pc_last_synced_at         datetime  NULL
        ;; when sync last touched this row.
    display_in_directory      tinyint(1) NOT NULL DEFAULT 1
        ;; member-facing visibility. Synced from PC `directory_status` when linked.
        ;; Admin print mode can override; member-facing views always honor it.
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

## 7. Joomla 5/6 extension surface

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

## 8. Out of scope for v1

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

## 9. Open questions (still need to decide before implementation)

These didn't come up in the framing pass but will need answers when we start
building. None block the scope above; flagging so they're tracked:

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

## 10. Sequencing (rough phase plan, not commitments)

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
   Members-only access wall.
8. **Phase H — self-service PDF.** mpdf integration, `format=pdf` view.
9. **Phase I — KML feed.** Tokens table, signed-URL view, admin UI.
10. **Phase J — admin print.** Reports → Print Directory workflow.
11. **Phase K — privacy plugin.** GDPR export / forget.
12. **Phase L — polish.** Action logs UI, sync error notifications, edge
    cases surfaced by phases C–K.

Each phase ships an independent PR. We don't need to do them in this exact
order if priorities shift; A → B → C is the only hard prereq chain.
