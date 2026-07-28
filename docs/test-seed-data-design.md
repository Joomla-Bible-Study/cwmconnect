# Design: test seed data

Status: **draft for review** (2026-07-28). No code yet.
Open decisions are collected in §7 — none of them are settled.

## 1. Goal

Make every part of the directory testable **without a Planning Center
connection**, by shipping a member dataset that deliberately contains the
awkward cases rather than a tidy handful of names.

Two audiences, and they want different things:

- **A developer or CI run** needs a fixture that is reproducible, disposable,
  and shaped to trip the bugs that matter.
- **A church evaluating the component** needs something that looks like a
  directory so they can see what it does before wiring up PC.

Whether one artefact serves both is the first open question (§7.1).

## 2. Current state

**The shipped sample data is a decade stale and functionally invisible.**
`admin/sql/install.mysql.utf8.sql` inserts **3 member rows**, written in 2012 for
the Joomla 3 component. Their column list predates every v2 column — no
`pc_person_id`, `is_child`, `display_in_directory`, `directory_scope` or
`image_hash` — so all of those fall back to defaults:

| Column | Default | Effect on a demo row |
|---|---|---|
| `display_in_directory` | `1` | visible |
| `directory_scope` | `public` | unrestricted |
| `is_child` | `0` | treated as an adult |
| `pc_person_id` | `NULL` | local-only, so `PcLockedFields` locks nothing |
| `access` | `0` | **not a real view level** |

That last row matters: `access = 0` matches no view level, so those members are
invisible to Smart Search and to anything else reading the row's access. They
also carry `images/sampledata/fruitshop/apple.jpg` as a photo, which is a
Joomla sample-data path that will not exist on most installs.

So today, a developer with no PC connection has effectively **no usable data**.

**Everything verified in this codebase to date was verified against live data.**
The 2026-07-27/28 sessions found and fixed a dozen bugs across the admin save
paths, the KML access gate and geocoding — every one of them against 531–540
real PC-synced members on `j5-dev`. That worked, but it means the evidence is
not reproducible by anyone else, cannot run in CI, and disappears the moment the
database is reset.

**The unit suite cannot reach any of it.** `tests/bootstrap.php` runs against
hand-written stubs under `tests/stubs/`, with no Joomla CMS classes and no
database. That is why `MemberGroupSync::reconcile()`, `AccessCodeService::grant()`
and `LockedmediaField` are all covered by "verified live" rather than by tests.
A fixture does not fix that on its own — see §5.

## 3. Why a thin fixture would be worse than none

The temptation is twenty tidy members with names, addresses and photos. That
would have caught **none** of the bugs found this week. Each of these was
visible only because the real data was messy:

| Bug | What in the data exposed it |
|---|---|
| Household coordinate inheritance | 85 members with **no address at all**, the address living on one household member |
| Geocode dedupe on the composed query | PO boxes, and `300 Bakertown Rd` vs `300 Bakertown Rd Apt 2D` |
| KML placemark count not matching member count | 65 rows with `is_child = 1`, excluded from the directory |
| Pairing ceiling | 316 members with no email; 24 households sharing one |
| `publish_up` null-date guard | empty calendar fields posting `''` into `NOT NULL` datetime columns |
| PC field locking | rows both **with** and **without** `pc_person_id` |

The fixture's value is proportional to how uncomfortable it is. A dataset where
every member has a name, an email, an address and a photo is a dataset that
proves nothing.

## 4. What the fixture should contain

Roughly 40–60 members — enough for pagination and grid layout to be real,
small enough to read. Composition matters more than size:

**Identity and pairing**
- Members with no email at all *(the 59% case — cannot ever auto-pair)*
- Two members sharing one email *(ambiguous; `findUnpairedMemberIdByEmail`
  deliberately refuses)*
- A member whose email matches a seeded Joomla user *(auto-pairs)*
- A member already paired, and one paired then unpublished *(exercises
  `MemberGroupSync` in both directions)*

**Households**
- A household where one member holds the address and the others have none
  *(coordinate inheritance)*
- A household spanning two genuinely different addresses *(must **not**
  collapse — 17 of 73 real households look like this)*
- A single-person household, and members with no household

**Addresses and geocoding**
- Pre-set coordinates on most rows, so the fixture does not need network access
- A PO box, and an `Apt`/`Unit` suffix on an otherwise duplicate address
  *(compose-key dedupe)*
- One address that cannot geocode *(failure path)*
- Members with no address *(never queued)*

**Visibility and access**
- `is_child = 1` rows *(excluded from directory and KML)*
- `display_in_directory = 0` *(hidden from listing and Finder)*
- `published = 0`
- A `directory_scope` of `household` alongside the `public` default
- A real `access` view level, **not** `0`

**PC surface**
- Rows with `pc_person_id` set *(locked fields)* and local-only rows *(fully
  editable)* — the same split `PcLockedFields` branches on
- Plausible `pc_last_synced_at` values

**Photos** — see §7.3; this is the one genuinely hard part.

## 5. What this does and does not unlock for CI

Being honest about the ceiling: **a SQL fixture alone does not make the unit
suite able to test any of this.** `tests/bootstrap.php` has no database and no
CMS. Loading a fixture changes nothing there.

Getting real coverage needs an **integration** suite — a second PHPUnit
configuration that boots a Joomla install against a scratch database, applies
the schema, loads the fixture, and tears down after. That is a larger piece of
work than the fixture, and it is what would actually let CI catch a repeat of
this week's bugs.

The fixture is a prerequisite for that, and useful on its own for manual
testing, but it should not be sold as "now it is tested".

Suggested order:

1. **The fixture** — usable immediately for manual testing without PC
2. **A loader** — an admin action and/or a CLI command (§7.2)
3. **Integration harness** — scratch DB, schema, fixture, teardown
4. **Port the live checks to it** — the save paths, the KML gate, the geocode
   passes, all of which are currently prose in commit messages

Only step 4 turns "verified once by hand" into "verified on every push".

## 6. What it should *not* do

- **Not touch a database that already holds real members.** The loader must
  refuse if `#__cwmconnect_details` contains rows with a `pc_person_id`, or at
  minimum require an explicit confirmation. Seeding fake people into a live
  church directory is the worst outcome this feature could produce.
- **Not ship enabled.** Nothing should install sample data by default.
- **Not reuse the 2012 rows.** They should be removed from the install SQL as
  part of this work (§7.4).
- **Not invent real-looking people.** Names should be obviously fictional and
  the addresses non-deliverable — this data will end up in exports, PDFs and
  KML files that get emailed around.

## 7. Open questions

**7.1 — One artefact or two?** A dev fixture (ugly, edge-case-heavy, under
`tests/`) and demo data for evaluators (pretty, plausible, installable) pull in
opposite directions. Options: build only the dev fixture; build only demo data
and accept weaker coverage; or build one dataset and accept it is a compromise.
*Recommendation: build the dev fixture first — it is the one with a concrete
motivating problem. Demo data is a marketing artefact and can wait.*

**7.2 — How does it load?** A button in the Control Panel is discoverable but
puts a data-destroying action next to "Sync now". A CLI command is safer and
CI-friendly but invisible to a non-technical admin. A `tests/fixtures/*.sql`
file applied by the integration harness is simplest of all and serves no manual
use. *These are not exclusive — the SQL file could be the source of truth, with
a thin CLI wrapper.*

**7.3 — Photos.** Much of the directory is visual — cards, PDF, KML balloons —
so a fixture with no photos leaves the most-looked-at surface untested. But
committing 50 face images is both a repository-size problem and an ethical one
(whose faces?). Options: ship none and rely on the initial-letter placeholder;
generate simple synthetic images at load time; or commit a handful of tiny
abstract images reused across members. *Recommendation: generate at load time —
no binaries in the repository, and it exercises the real photo-cache paths.*

**7.4 — Remove the 2012 demo rows?** They are stale, carry `access = 0`, and
point at a Joomla sample-data image path. Removing them is almost certainly
right, but it is a behaviour change for anyone installing fresh, so it should be
a deliberate decision rather than a side effect.

**7.5 — How much is enough?** 40–60 members is a guess. Large enough that
pagination, the fluid grid and the PDF are exercised realistically; small enough
to read and reason about. Worth sanity-checking against the real distribution
(540 members, 73 households, 59% with no email).

## 8. Not in scope

- The integration harness itself (§5 step 3) — dependent on this, but a
  separate piece of work
- Seeding Planning Center; this is explicitly about testing *without* it
- Performance/load datasets — a different problem needing a different shape
