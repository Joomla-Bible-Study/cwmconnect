# Live testing findings — j5-dev — 2026-06-05

Environment: j5-dev (MAMP, Joomla 5.4.2), 531 PC-synced members, schema columns
current (but `#__schemas` version not bumped past 2.0.0-20260602 — cosmetic).

## Results by checklist section

| § | Test | Result |
|---|------|--------|
| 1 | Admin component loads | ✅ PASS — submenu + cpanel render, no console errors |
| 2 | Member edit / PC-lock | ✅ PASS with issues (see B1, B2) |
| 3 | Feed Tokens view | ✅ loads via URL — **not in submenu (B3)** |
| 3b | Feed token create + revoke | ✅ PASS — created id 3, one-time URL shown, revoke set `revoked_at` |
| 4 | Reports / Print PDF | ✅ PASS — "Directory PDF ready — 531 members", mpdf streams |
| PC | Test connection | ✅ "Planning Center connection verified" (live API OK) |
| 9–11 | KML feed | ⛔ DEFERRED — blocked by B0 (0 placemarks); token design reviewed (sound) |

Paused after §4 + token test at user request (KML blocked on geocode). NOT yet run:
front-end (§6–8), Finder reindex (§13), Privacy export (§12), Action logs (§5).
Cleanup done: test token id 3 revoked; member #5 checkout released.

### KML auth design (reviewed — sound, no change needed)
Two modes: (1) **one-time download** uses the Joomla session → no token; (2) **live
NetworkLink feed** is re-fetched by external clients (Google Earth) with no session,
so the token embedded in the feed URL is the credential. The token is a DB row
precisely so it's **revocable** per-recipient (Active→Revoked → URL 403s on next
refresh) without resorting to a public URL that would leak member PII. Revoke
verified working (token id 3 → `revoked_at` set).

## Geocode overhaul scope (B0 fix)
1. Add a key field to config.xml (e.g. `google_geocode_key`) so the API key is
   settable in admin — the missing piece that makes geocode runnable at all.
2. Modernise `GeoupdateModel::geocodeRow()`: drop deprecated `&sensor=true`, switch
   from the legacy XML endpoint to the JSON Geocoding API, replace `file_get_contents`
   with `Joomla\Http\HttpFactory`, parse JSON status/lat/lng.
3. Consider a free/keyless fallback (Nominatim/OSM) with rate-limit + attribution,
   since Google Geocoding requires billing — useful for dev + small churches.
4. After fix: run geocode to populate lat/lng, then complete KML §9–11.

## Bugs / findings

### B0 (BLOCKER for KML) — Geocode is non-functional; no API-key config field
- `GeoupdateModel.php:229` reads the Google key from `getParams()->get('apikey')`,
  but **no `apikey` field exists in config.xml** (and `git log -S apikey` shows it
  was never in the v2 config). No admin UI to set the key.
- Code is also legacy: deprecated `&sensor=true`, the Google XML endpoint, and
  `file_get_contents` (not Joomla HttpFactory).
- Consequence: geocoding has never run in v2 → **all 531 members have lat/lng=0**
  → KML feed renders 0 placemarks. §9–11 blocked until the Geoupdate overhaul.
- Overhaul scope: re-add a key field to config.xml (e.g. `google_geocode_key`),
  switch to the JSON endpoint, drop `sensor`, use Joomla\Http, then run geocode.

### B1 — `gender` editable on a PC-synced member (should be locked)
- On the member edit form, PC-locked fields (name, lname, email_to, address,
  suburb, telephone, mobile, birthdate) are correctly readonly, but `gender` is
  editable despite its "Synced from Planning Center" help text. Sync writes
  gender, so an admin edit is silently overwritten next sync. Add gender to
  PcLockedFields.

### B2 — `image` media field misconfiguration (console exception)
- Browser console throws `JoomlaFieldMedia` "Misconfiguration" on the member edit
  form (core joomla-field-media.js). The `image` field input is also not readonly
  even though the lock banner says "the cached photo are read-only here". Review
  the image media field's attributes + lock.

### B3 — Feed Tokens missing from admin submenu
- `view=feedtokens` works by direct URL but has no entry in the component submenu
  (which lists Control Panel, Members, Categories, Family Units, Directory Header,
  Positions, KML, Reports, Info). Users can't discover it. Add a submenu item.

### B4 — Language-string typos on the member edit form
- "Chiled" → "Child" (Family Positions option)
- "Baptisom" → "Baptism" (x2, Member Details)
- "Mebers Status of in the church" → garbled (Member Status desc)
- "entor the setting" → "enter" (KML Info)
- "whether the it is shown" → "whether it is shown" (KML Open desc)
- "None Member" → likely "Non-Member" (Member Status option)
