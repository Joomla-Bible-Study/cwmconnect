# Local Testing Checklist

Manual testing steps for verifying cwmconnect against a local Joomla 5/6 dev install.
Run through these after `composer link` and applying all SQL migrations.

## Prerequisites

- Local Joomla install linked via `composer link` (check with `composer link-check`)
- All SQL migrations applied (check `#__schemas` for latest version)
- Extensions registered in `#__extensions` (component + all plugins + lib_mpdf)
- At least 2-3 test member rows in `#__cwmconnect_details` with:
  - `published = 1`, `display_in_directory = 1`
  - `lat`/`lng` populated (for KML placemarks)
  - `email_to`, `telephone`, `mobile` populated (for balloon content)
  - At least one with `catid` pointing to a category that has an image in params (for per-category pin icons)
- At least one KML settings row in `#__cwmconnect_kml` (id=1) with lat/lng for the LookAt camera
- A Joomla admin user account for admin testing
- A front-end user account for member portal testing

---

## 1. Admin — Component loads

- [ ] Navigate to `administrator/index.php?option=com_cwmconnect&view=cpanel`
- [ ] Control panel renders without errors
- [ ] Submenu shows: Dashboard, Members, Categories, Family Units, Dir Headers, Positions, KMLs, Reports, Feed Tokens, Info

## 2. Admin — Member edit form

- [ ] Navigate to `?option=com_cwmconnect&view=members` → click a member
- [ ] "Linked Joomla User" field shows the user picker (type="user")
- [ ] If member has `pc_person_id` set, PC-locked fields show as readonly with the blue "Synced from PC" banner

## 3. Admin — Feed Tokens CRUD

- [ ] Navigate to `?option=com_cwmconnect&view=feedtokens`
- [ ] Empty state shows "No feed tokens have been created yet."
- [ ] Click **New** in toolbar → form shows Label (text) and User (user picker)
- [ ] Fill in label (e.g. "Google Earth test") and pick a user → click **Save**
- [ ] Redirects to list with green alert showing the one-time KML feed URL
- [ ] **Copy the URL now** — it won't be shown again
- [ ] Token appears in the list with status "Active"
- [ ] Select the token checkbox → click **Revoke** → status changes to "Revoked"

## 4. Admin — Reports / Print Directory

- [ ] Navigate to `?option=com_cwmconnect&view=reports`
- [ ] Four export cards: CSV, KML, Print Directory, Missing Photos
- [ ] "Print Directory" card shows **Generate PDF** button
- [ ] If logged in as super admin: "Include hidden members (staff copy)" checkbox is visible
- [ ] Click **Generate PDF** → redirects back with success message and download link
- [ ] Click the download link → PDF opens with member table (name, email, phone, mobile, address, household)
- [ ] With "Include hidden members" checked: hidden members appear with `[hidden]` badge, header shows `{STAFF COPY}`

## 5. Admin — Action Logs

- [ ] After running a PC sync (Dashboard → Sync Now): check `System → Action Logs` for "Planning Center sync completed" entry
- [ ] After generating a PDF with hidden members: check Action Logs for "Generated print directory PDF including hidden members" entry

## 6. Front-end — Members list (requires login)

- [ ] Navigate to `index.php?option=com_cwmconnect&view=members`
- [ ] If not logged in: login wall redirects to Joomla login
- [ ] After login: member grid/table renders with search and layout toggle
- [ ] **Download PDF** button visible → downloads a PDF of the filtered directory
- [ ] **Download KML** button visible → downloads a KML file

## 7. Front-end — My Profile portal

- [ ] Navigate to `index.php?option=com_cwmconnect&view=myprofile`
- [ ] If user is paired to a member (`user_id` matches): edit form renders
- [ ] If user is NOT paired: placeholder message with admin contact email
- [ ] If member is PC-linked: locked fields are readonly, "My Planning Center" notice shown
- [ ] Edit an unlocked field → Save → changes persist
- [ ] Try to POST a locked field value (URL hack) → save fails with locked-field error

## 8. Front-end — PDF export

- [ ] Navigate to `index.php?option=com_cwmconnect&view=members&format=pdf`
- [ ] Downloads a PDF with all published directory members
- [ ] PDF has correct columns: Name, Last Name, Email, Phone, Mobile
- [ ] If no members match filters: returns 404 error (not an empty PDF)

## 9. KML output — Session auth (logged-in user)

- [ ] Navigate to `index.php?option=com_cwmconnect&view=members&format=kml`
- [ ] Downloads `church-directory.kml`
- [ ] Open in Google Earth Desktop:
  - [ ] **Folder hierarchy**: Category folders → Suburb sub-folders in sidebar
  - [ ] **LookAt camera**: initial view centers on the coordinates from `#__cwmconnect_kml` settings
  - [ ] **Pin icons**: categories with images use custom pins; others use red paddle
  - [ ] **Click a placemark** → balloon shows:
    - [ ] Photo (or initial-letter placeholder)
    - [ ] Name (bold, 16px)
    - [ ] Position (if set)
    - [ ] Household name (if set)
    - [ ] Contact table: Email (mailto link), Phone, Mobile, Fax, Spouse, Children
    - [ ] Formatted address
  - [ ] Members without lat/lng are NOT shown as placemarks
  - [ ] **ExtendedData**: in Google Earth sidebar, search for an email → should find the member

## 10. KML output — Token auth (external client)

- [ ] Using the feed URL from step 3 (with `?token=...`), open in:
  - [ ] Incognito browser window (no Joomla session) → KML downloads successfully
  - [ ] Google Earth → Add Network Link → paste URL → placemarks appear
- [ ] Revoke the token in admin → same URL now returns 403

## 11. KML output — NetworkLink (auto-refresh)

- [ ] Navigate to `index.php?option=com_cwmconnect&view=members&format=kml&networklink=1`
- [ ] Downloads `church-directory-live.kml`
- [ ] Open in Google Earth Desktop:
  - [ ] Shows "Live Church Directory Feed" in sidebar
  - [ ] Feed content loads automatically (NetworkLink resolves the inner data URL)
  - [ ] After 15 minutes (or right-click → Refresh), content updates

## 12. Privacy plugin

- [ ] Navigate to `administrator/index.php?option=com_privacy`
- [ ] Create an **Export Request** for a user who is paired to a member
- [ ] Process the request → export includes "cwmconnect_member" and "cwmconnect_feed_tokens" domains
- [ ] Create a **Removal Request** for a test user
- [ ] Process the request → member row is pseudonymised (name="Removed", contact fields NULL, published=0, user_id=NULL)
- [ ] Feed tokens for that user are revoked

## 13. Finder indexer

- [ ] Navigate to `administrator/index.php?option=com_finder`
- [ ] Run the indexer (Index → click the index button)
- [ ] Members with `display_in_directory = 0` should NOT appear in Smart Search results
- [ ] Members with `display_in_directory = 1` should be indexed and searchable

---

## Quick smoke test (minimum viable check)

If short on time, run just these:

1. Admin loads: `?option=com_cwmconnect&view=cpanel` → no errors
2. Members list: `?option=com_cwmconnect&view=members` (front-end, logged in) → renders
3. KML download: `?view=members&format=kml` → valid XML with placemarks
4. PDF download: `?view=members&format=pdf` → opens as a PDF
5. Feed token: create one in admin → use URL in incognito → KML works
