# Design: member-managed live KML feeds

Status: **draft for review** (2026-06-05). No code yet.
Decisions taken: **multiple named feeds per member**, managed in the **My Profile
portal**, admin keeps a global view.

## 1. Goal

Let a logged-in member create and manage **several independent live KML feeds**
("laptop", "phone", "share with Deacon Smith"), each with its own revocable URL,
from the My Profile portal — without an admin minting tokens for them, and without
one feed silently breaking another.

## 2. Current state

**Schema** — `#__cwmconnect_feed_tokens` already models multiple named tokens per
user: `id, user_id, label, token_hash, created_at, last_used_at, revoked_at`
(`UNIQUE(token_hash)`, `KEY(user_id)`). The multi-feed model needs **no** schema
change; the expiry decision (§3.6) adds **one nullable `expires_at` column**.

**Service** — `Administrator\Service\FeedToken\FeedTokenService`:
- `generate(): {cleartext, hash}` — `bin2hex(random_bytes(32))`, stores `sha256` only
- `validate(cleartext): ?object` — match by hash where `revoked_at IS NULL`
- `touchLastUsed(id)`, `revoke(int[] ids): int`

**Two KML modes today**
1. **One-time snapshot** — front-end `format=kml`, session-authed. No token.
2. **Live NetworkLink feed** — `MembersModel::buildKmlFeedFile(userId, username)`
   bakes a token into a NetworkLink doc that external clients auto-refresh.

**Member self-service today** (`tmpl/myprofile/default.php:174–186`)
- "Download my live KML feed" (`members.kmlFeed`)
- "Revoke my KML feeds" (`myprofile.revokeKml`) — **revoke-all**

**The limitation we're fixing.** `buildKmlFeedFile` does *find-active-or-create* and
**rotates the hash on every download** (MembersModel:~211). So:
- a second download **silently breaks** the first feed's URL (laptop vs phone);
- "management" is all-or-nothing (no list, no naming, no per-feed revoke).

Admin `feedtokens` view already lists/creates/revokes across all users — keep it.

## 3. Target design

One token row = one named live feed. Members own and manage their own rows; the
table already supports it, so the work is **behavior + UI + ownership guards**.

### 3.1 Token lifecycle (resolves silent breakage)
- **Create** = INSERT a new row. Never touches sibling feeds → laptop + phone +
  shared coexist.
- **No rotation on download.** The cleartext is shown/streamed **once at creation**
  (we store only the hash and can't recover it later — by design). The member's
  downloaded `.kml` keeps auto-refreshing via its embedded token until revoked.
- **Regenerate** (explicit, per feed) rotates the hash → new `.kml`, old URL dies.
  This is the *only* way a feed's URL changes — never silently.
- **Revoke** (per feed) sets `revoked_at` → that feed 403s on next refresh.
- **Revoke all** kept as a "panic button".
- **Expire** (automatic) — see §3.6.

### 3.6 Token expiry (decided 2026-06-05)

Two complementary expiries, both enforced in `FeedTokenService::validate()` so they
apply on every Google Earth refresh — no change to the NetworkLink build:

- **Inactivity (sliding) expiry.** A token is invalid if it hasn't been refreshed
  within the inactivity window. Works *with* KML because the NetworkLink already
  auto-refreshes (`<refreshMode>onInterval</refreshMode>` / `<refreshInterval>900</refreshInterval>`
  in `KmlView::streamNetworkLink`) — each ~15-min refresh calls `touchLastUsed()`,
  so an actively-used feed keeps sliding forward and never expires; only an
  abandoned feed (Google Earth closed / feed removed for the whole window) lapses.
  - Window is **config-driven**: `kml_feed_inactivity_days`, **default 90**, `0` =
    disabled (pure set-and-forget). No new column — computed from `last_used_at`.
  - **Seed `last_used_at` at creation** so a created-but-never-loaded feed still
    expires N days after creation (no immortal unused tokens).
- **Optional per-feed absolute expiry.** Member may set an "expires on" date when
  creating a feed (temporary guest shares). Hard cutoff, ignores activity. Needs a
  nullable **`expires_at`** column; `validate()` rejects rows past `expires_at`.

**Friendly expiry (not a bare 403).** When a token is invalid/expired/revoked, the
feed endpoint returns a minimal *valid* KML with a single balloon —
"This feed has expired. Reconnect it in My Profile." (reuse
`COM_CWMCONNECT_KML_FEED_TOKEN_INVALID`) — so Google Earth shows *why* the map went
stale instead of a silent HTTP error.

Optional housekeeping: a small **task plugin** could hard-set `revoked_at` on
long-lapsed tokens for tidiness, but `validate()`-time enforcement is the gate.

### 3.2 Service additions (`FeedTokenService`)
- `issue(int $userId, string $label): {cleartext, id}` — INSERT one row; centralises
  the create that currently lives inline in `MembersModel`.
- `regenerate(int $id): string` — new cleartext+hash for an existing row; returns
  cleartext. (Caller enforces ownership.)
- `listForUser(int $userId, bool $includeRevoked=false): object[]` — id, label,
  created_at, last_used_at, revoked_at, expires_at, computed status (never the hash).
- `issue` accepts an optional `?string $expiresAt` for the per-feed cutoff (§3.6).
- Extend `validate` to also reject **inactivity-expired** (`last_used_at` older than
  `kml_feed_inactivity_days`, when > 0) and **absolute-expired** (`expires_at` in the
  past) tokens, returning the friendly-expiry signal. Keep `touchLastUsed` / `revoke`.

### 3.3 Member portal — "My live map feeds" panel (`view=myprofile`)
- **List**: label · created · last used · status (Active/Revoked) for the current
  user only (`user_id = identity`).
- **Create**: label (required) → `myprofile.createKmlFeed` → mints a feed, then
  **one-time** shows the raw URL + a "Download .kml" button (same one-time pattern
  as the admin Feed Token create screen).
- **Per row**: Download (only meaningful at creation), **Regenerate**, **Revoke**.
- Keep a top-level "Revoke all" + the existing one-click snapshot download.

### 3.4 Controller (`MyprofileController`)
- `createKmlFeed()` — `Session::checkToken`; label from POST; `issue(identity, label)`;
  build NetworkLink `.kml`; stream or redirect with the one-time URL flashed.
- `revokeKmlFeed()` — checkToken; **ownership guard** (token.user_id === identity)
  then `revoke([id])`.
- `regenerateKmlFeed()` — checkToken; ownership guard; `regenerate(id)`; re-stream.
- Keep `revokeKml()` (all).

### 3.5 Model (`MyprofileModel`)
- `getMyFeeds(): object[]` via `listForUser(identity)`.
- `ownsToken(int $id): bool` helper used by every by-id mutation.

## 4. Security (must-haves)

- **IDOR guard** — every by-id member operation verifies `token.user_id ===
  current identity` before acting. Without this, a member could revoke/regenerate
  another member's feed by guessing ids. This is the single most important check.
- **Hash-only storage** unchanged; cleartext displayed exactly once.
- **No privilege escalation** — a feed authorises *as its owner*, so it exposes only
  what that member may see. Confirm `KmlView` applies the same `member_access` +
  visibility gating under token auth as under session auth (verify during build).
- **Bounded credential lifetime** — inactivity + optional absolute expiry (§3.6)
  auto-close abandoned/leaked feeds, so a `.kml` on a lost device can't serve member
  PII forever. This is the main reason expiry is in scope.
- **Abuse cap** — optional config: max active feeds per member (e.g. 5). Surfaces a
  friendly error instead of unbounded token creation.
- CSRF on all mutations (`Session::checkToken`), already the pattern.

## 5. Migration / compatibility
- **One small migration:** add `expires_at DATETIME NULL` to
  `#__cwmconnect_feed_tokens` (+ install SQL). Inactivity expiry needs no column.
- Add config field `kml_feed_inactivity_days` (default 90, 0 = off).
- Existing tokens keep working (NULL `expires_at` = no absolute cutoff; inactivity
  applies from their existing `last_used_at`, or `created_at` once backfilled).
- `buildKmlFeedFile`'s rotate-on-download is replaced by explicit create/regenerate;
  the old one-click "Download my live KML" can stay (auto-labelled "Quick feed") or
  be folded into the panel — see open Q1.
- Admin `feedtokens` view unchanged; it now simply also shows member-created rows.

## 6. Phasing
1. **Service + expiry** — `issue`/`regenerate`/`listForUser`; `expires_at` migration +
   `kml_feed_inactivity_days` config; `validate()` inactivity + absolute checks +
   friendly-expiry signal; seed `last_used_at` at creation (+ unit tests, incl.
   expiry-window edge cases).
2. **Model/controller** — `getMyFeeds`, create (with optional expiry)/revoke/
   regenerate with ownership guards (+ tests for the IDOR guard).
3. **Portal UI** — the "My live map feeds" panel (list w/ status incl. expired,
   create w/ optional "expires on", per-feed actions) + language strings.
4. **Polish** — optional abuse cap config, optional stale-token prune task plugin,
   admin-view note, docs, live test (§7/§10).

Each phase is its own PR (squash; signed-commit rule).

## 7. Open questions
1. Keep the one-click auto-named "Quick feed" download alongside the managed panel,
   or replace it entirely with the panel?
2. Max active feeds per member — enforce a cap? default value? config-driven?
3. Ship **Regenerate** in v1, or just Create + Revoke first and add Regenerate later?
4. Should admins create feeds *on behalf of* a member from the admin view (already
   possible — they pick the user), and should those show in the member's panel?
   (They will, since it's the same table — just confirming that's desired.)
5. Naming/UX: "live map feed" vs "KML feed" vs "Google Earth feed" in the UI copy.

## 8. Not in scope
- The geocode/`apikey` blocker (B0) — feeds render 0 placemarks until that's fixed;
  tracked separately in `docs/testing/live-test-findings-2026-06-05.md`.
- Multiple KML *camera/settings* configs (`#__cwmconnect_kml` is a single row today);
  unrelated to per-member feed tokens.