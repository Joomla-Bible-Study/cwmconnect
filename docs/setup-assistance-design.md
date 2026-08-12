# Design: setup assistance (readiness checklist, in-place provisioning, wizard)

Status: **draft for discussion** (2026-07-28). No code.
Captures the thinking from the j6-dev clean build. Nothing here is decided.

Companion to [`setup-flow.md`](setup-flow.md), which records what setup actually
requires today.

---

## 1. The problem

Setting the component up is not hard because there are many steps. It is hard
because of three specific things.

**Failures are silent.** Plugins install disabled, so pairing and Smart Search
do nothing and look broken. Synced members have no coordinates, so the map is
empty. No view level is configured, so members cannot see the directory. In
every case the component *appears* faulty rather than unfinished.

**Order matters, and getting it wrong is punishing.** Setting the member view
level before pairing has populated the group locks out every existing member.
Nothing warns you.

**Half the steps throw you out of the component.** Creating the user groups and
the view level means leaving for `com_users`; enabling the plugins means leaving
for `com_plugins`. You come back and have to remember where you were, and which
of the ids you just created goes in which setting.

That last one is the sharpest, because it is the one the component could simply
stop doing.

---

## 2. Not everyone uses Planning Center

An important constraint, and one `setup-flow.md` currently obscures by assuming
PC throughout.

`pc_enabled` gates only two things in the entire codebase — whether the Control
Panel shows the PC card (`Cpanel/HtmlView.php:97`), and whether the PDF cover
uses PC branding (`PdfView.php:299`). Everything else works without it:

- `PcLockedFields::forItem()` returns an empty list for rows with no
  `pc_person_id`, so manually created members are **fully editable**
- `ReconcileModel` is written for the mixed case — *"Manual means
  `pc_person_id IS NULL` — a row the sync never owns"* — and can merge a manual
  row into a PC person

So there are three real paths, and any assistance has to branch on the first
question:

| Path | Shape |
|---|---|
| **PC-synced** | Connect, sync, geocode. Most member fields locked. |
| **Manual** | No PC at all. Members entered by hand, every field editable. The access code matters *more*, since there is no email data to pair on. |
| **Hybrid** | Synced members plus manual rows, reconciled where they overlap. |

The manual path is shorter but not simpler: it still needs groups, a view level,
KML settings and geocoding, and it has *no* automatic route into the member
group, because pairing has nothing to match on.

---

## 3. In-place provisioning

The component should be able to create the Joomla objects setup needs, instead
of sending the admin to another component to make them and come back with ids.

**There is precedent in this codebase.** `script.php::ensureHiddenMenu()`
already creates a menu type and menu items directly, idempotently, checking for
existence first. Provisioning groups and a view level is the same pattern.

**The core tables support it.** `Joomla\CMS\Table\Usergroup::store()` performs
its own nested-set rebuild, so placement is handled; `ViewLevel::bind()`
JSON-encodes a rules array. Both are what `com_users` itself uses.

So a single **"Set up directory access"** action could:

1. Create a **Church Members** group, if absent — granted by pairing
2. Create a **Directory Guests** group, if absent — granted by the access code
3. Create a **Church Directory** view level listing both, **plus Super Users**
4. Point `member_group` and `access_code_group` at the new groups
5. Leave `member_access` alone, and say why (see below)

That collapses the most error-prone part of setup into one reviewable action,
and removes three classes of mistake seen during this session: forgetting Super
Users in the view level (which locks administrators out of the front end),
mismatched group ids between installs, and the ordering trap.

### It must not set the view level for you

`member_access` is the switch that makes the directory members-only. Setting it
while the group is still empty locks out every existing member. Provisioning
should create everything and then say plainly: *"Nobody is in these groups yet.
Once pairing or the access code has populated them, set Member view level to
Church Directory."*

Do the safe part automatically; make the dangerous part deliberate.

---

## 4. A readiness checklist

More useful than a wizard, and much cheaper, because it keeps earning its keep.
A wizard answers "how do I set this up?" once. A checklist also answers "why
can't members see the directory?" six months later — which is the question that
actually generates support.

Roughly:

```
✓ Planning Center      connected — 540 members, last synced 2h ago
✗ Plugins              3 of 5 disabled — pairing and search are inactive
✗ Geocoding            0 of 540 members have coordinates — the map is empty
✗ Directory access     no view level set — members cannot see the directory
✗ Map settings         no KML record — the map has no centre point
```

Each row is derived from real state, not from a "setup complete" flag — so it
cannot drift out of sync with reality, and it stays correct if someone later
disables a plugin or deletes the KML row.

### Three states, not two

Some setup steps need permissions the component does not control:

| Step | Requires |
|---|---|
| Enable the bundled plugins | `com_plugins` |
| Create user groups | `com_users` core.admin |
| Create view levels | `com_users` core.admin |

None of these are grantable through Church Directory's own `access.xml`. So a
**Church Directory manager** — `core.manage` on the component, not a Super User
— cannot finish setup. Showing them a row with a link they will be refused at is
worse than useless.

Each row therefore needs one of:

- **Done** — with the number that proves it
- **You can fix this** — with the action
- **Needs a Super User** — with what to ask for, and no dead link

In-place provisioning (§3) does not remove this constraint, since creating groups
is itself privileged. It removes the *navigation*, not the permission.

### Related question worth settling separately

`CpanelController::assertAdminAjax()` requires **`core.admin` on
com_cwmconnect** to run a sync or a geocode. So a directory manager cannot run
either. That may be deliberate — a sync burns API quota and rewrites every row —
but if a "directory manager" role is meant to operate the thing day to day, that
is the line to revisit.

---

## 5. Where it lands

**Deliberately unresolved.** The Control Panel is the obvious home but is due
for rework, and placement should not be decided before that.

Building the checks as their own view keeps the decision cheap and late: the
same view can be embedded in the Control Panel, given a menu entry, or surfaced
through a Joomla post-install message, without changing the logic.

Whatever happens, the state checks themselves are the reusable part — a wizard,
a checklist and a post-install message would all consume the same handful of
`isPlanningCenterConnected()` / `pluginsEnabled()` / `hasViewLevel()` answers.
**Build those first**, decide the presentation later.

---

## 6. Wizard versus checklist

Not either/or, but the checklist should come first.

| | Checklist | Wizard |
|---|---|---|
| Useful after setup | yes — it is the diagnostic | no |
| Cost | low | high |
| Duplicates existing screens | no | yes |
| Enforces ordering | by showing what is blocked | actively |
| Branches on PC / manual | by showing only relevant rows | must, from question one |

A wizard is the better *first-run* experience and worth building once the state
checks exist and the Control Panel has settled. Joomla 5/6 also ships **Guided
Tours** natively, which is a cheap complement — it can walk someone through the
UI, though it cannot check state or prevent the ordering trap, which is where
the real damage is.

---

## 7. Open questions

1. **Does provisioning create one group or two?** Two is the current design
   (pairing-managed and code-granted, so `MemberGroupSync` cannot strip a
   code-granted user). A single group is simpler to explain but reintroduces
   that conflict.
2. **Should the checklist offer to enable the plugins?** It can, with
   `core.admin` — but silently enabling plugins on an admin's site is a bigger
   liberty than creating an unused group.
3. **What does the manual path show?** The PC rows should disappear rather than
   sit there permanently red. Probably keyed on `pc_enabled`, which means asking
   the question explicitly somewhere.
4. **Post-install message?** Joomla's own mechanism for "you have installed this,
   here is what to do next" — good for discovery, and it survives the Control
   Panel rework.
5. **Should `core.manage` be able to run a sync?** See §4.

---

## 8. Not in scope

- The Control Panel rework itself
- Test seed data — see [`test-seed-data-design.md`](test-seed-data-design.md);
  related, since a wizard demo and a fixture both need believable non-PC data
- Guided Tour content
