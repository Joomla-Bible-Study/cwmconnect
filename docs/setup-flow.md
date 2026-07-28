# Setting up CWM Connect — observed flow

Status: **working notes**, not user documentation (2026-07-28).

Recorded while rebuilding j6-dev from nothing: clean install → Planning Center
→ sync → working directory. The point is to capture what a real setup actually
requires, so the eventual user guide describes the product rather than someone's
memory of it.

Bugs hit along the way are deliberately **excluded** from the steps — they are
listed separately in §7 so the happy path stays readable.

---

## 1. Install

Upload the package through **System → Install → Extensions**. On a symlinked
dev checkout use **Discover** instead.

The install creates:

- 9 database tables
- 26 **Positions** (Pastor, Elder, Deacon … ) — a lookup list, editable
- 2 **Directory Headers** — a header block and a footer confidentiality notice
- a hidden site menu type (`cwmconnect-hidden`) with routing targets for
  `members` and `myprofile`, needed for SEF URLs
- the admin Components menu entries

It creates **no members, no KML settings and no categories**.

### 1a. Enable the plugins — required

Every bundled plugin installs **disabled**. Nothing warns you.

| Plugin | Needed for |
|---|---|
| User – Church Directory Pairing | pairing a member to their Joomla account at registration |
| Content – Church Directory Pairing | pairing on the other content event |
| Smart Search – Church Directory | members appearing in site search |
| Privacy – Church Directory | GDPR export / removal covering member data |
| Task – CWM Connect Sync | scheduled background syncs (see §7, issue #191) |

The component works without them, but pairing, search and privacy silently do
nothing — which looks like the feature being broken rather than switched off.

---

## 2. Connect Planning Center

**Options → Planning Center**

1. **Enable Planning Center sync** → Yes
2. **Client ID** and **Secret** — from a PC Personal Access Token
   (api.planningcenteronline.com → your app → Personal Access Tokens)
3. Leave **API base URL** at the default
4. **Save**
5. **Test connection** — confirm before going further

### 2a. Reopen Options, then choose membership types

**Membership types to sync** is fetched live from PC, using the **saved**
credentials. Before the first save it shows a hardcoded fallback
(Member / Regular Attender / Visitor / Participant), which is usually *not*
what your PC account defines.

So: save credentials → reload Options → the real list appears → tick the types
you want → save again.

Leaving all types unticked syncs **everyone** in Planning Center, which is
rarely what a directory wants. On the reference account this is the difference
between ~540 members and 797 active people.

### 2b. Decide the archive policy

**When a person no longer matches** — archive locally (hide, keep the row) or
delete the local row. This decides what happens when someone leaves the church
or their membership type changes. Worth choosing deliberately.

---

## 3. Sync

**Control Panel → Sync now.**

On the reference dataset the first sync took **415 seconds** for 540 members and
544 photos, and it runs as a single request — see issue #191 before running this
on a large congregation.

What the sync brings in:

| | |
|---|---|
| Members | names, contact details, addresses, birthdays, gender, membership status |
| Photos | downloaded and cached locally, with sized web variants |
| Households | family units, and each member's link to theirs |
| Campuses | written to Directory Headers, including address and contact details |

What it does **not** bring in: coordinates, categories, positions *assignments*,
KML settings, or any access configuration.

---

## 4. Geocode

Synced members have addresses but **no coordinates**, so the map is empty until
this runs.

1. **Options → Geocoding** — choose Nominatim (free, needs a contact email per
   its usage policy) or Google (needs an API key)
2. Go to **Geo status** and press **Run geocoding**

Geo status is currently not in the menu — see issue #192. Until that is fixed:

```
index.php?option=com_cwmconnect&view=geostatus
```

Members with no address of their own inherit their household's point, and
addresses that resolve to the same query are only looked up once, so the number
of API calls is well below the member count.

---

## 5. Decide who may see the directory

The directory is gated by a **view level**, and a view level is granted through
a **user group**. There are two independent routes into it.

1. Create a user group, e.g. **Church Members** — granted automatically by pairing
2. Create a second group, e.g. **Directory Guests** — granted by the access code
3. Create a view level, e.g. **Church Directory**, listing **both** groups —
   and **Super Users**, or administrators lose front-end access
4. **Options → Global Member Options**
   - **Directory member group** → the first group
   - **Member view level** → the new view level
5. Optionally **Options → Shared access code**: enable, generate a code, and
   point **Group granted by the code** at the second group

### Ordering matters here

Set **Member view level** *last*, after pairing has populated the group.
Switching it first locks out every existing member until the group fills.

### Know the ceiling before relying on pairing

Pairing matches on the email Planning Center holds. On the reference account
only **160 of 540 (30%)** can ever pair automatically — 316 have no email in PC
at all, and 64 share one with a housemate (shared emails are refused as
ambiguous). The shared access code exists to cover the rest.

---

## 6. Remaining setup

- **KML settings** — the install ships none, so the map has no camera position
  and the admin KML export reports that settings are missing. Create a record
  under **KML** and set the centre point.
- **Menu items** — the install creates hidden routing targets; add your own
  visible menu item pointing at the directory.
- **Categories** — optional. The directory works fine with everyone
  uncategorised, which is the current policy.
- **Positions** — 26 ship as starting data; assign them to members, and edit the
  list to match your church.
- **Directory Headers** — reword the shipped header and footer blocks.

---

## 7. Known rough edges

Things that currently make this harder than it should be. Each is tracked:

| | |
|---|---|
| [#191](../../issues/191) | Sync runs as one blocking request — times out on shared hosting, and reports failure even when it succeeded |
| [#192](../../issues/192) | Geo status unreachable from the menu; PC Mappings and Reconcile missing too |
| [#188](../../issues/188) | Campus data silently discarded on installs missing the `dirheader.pc_*` columns |
| [#187](../../issues/187) | `details.note` exists only on upgraded installs |

Also worth fixing before this becomes user documentation:

- Plugins installing disabled with no prompt (§1a)
- Membership types needing a save-and-reload before they populate (§2a)
- No KML settings row shipped, so the map is uninitialised (§6)
- Nothing tells an admin the directory is invisible until a view level is
  configured (§5)

---

## 8. Rough order of operations

```
install → enable plugins → PC credentials → save → reload
       → membership types → sync → geocode
       → groups + view level → populate by pairing/code
       → set member view level (last) → KML settings → menu item
```
