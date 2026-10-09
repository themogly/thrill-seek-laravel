# 012 — Resolve the 10 orphaned CMS fields

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the June CMS-field cleanup entry, `f8aa32d`) and
`audits/reports/cms-field-usage.md` §1 and §2. **Size: small to medium.**

`git checkout main && git pull` → `git checkout -b chore/cms-orphans`.

> **Why this exists.** The CMS-field gate (unattended run 1) found 10 fields that are editable in the admin but
> used nowhere: the owner edits them and nothing changes. Ben's decisions, 9 Oct 2026:
> - **Location address and coordinates** (`address_line`, `town`, `region`, `postcode`, `country`, `lat`,
>   `lng`): **re-surface them** in the course-event structured data. That was the June plan, withdrawn in
>   this queue in favour of this prompt.
> - **`Product::duration`:** **remove** it. It isn't shown anywhere.
> - **`HomePageSettings::team_lead`** and **`Location::image`:** the gate recommends removing both. Ben
>   agreed the gate's recommendations in general but wasn't asked about these two by name. Remove them and
>   mark it `OVERNIGHT-DEFAULT — CONFIRM`.
>
> **Verified on `origin/main` = `7e01bfe` by code read:**
> - `StructuredData::courseEvent()` sets the Event `location` to a `Place` whose `name` **and** `address` are
>   both `$course->location->name` (`app/Support/StructuredData.php:143-146`). `address` holding a name is
>   wrong data, not just missing data.
> - Only `region` and `country` are filled on the 4 locations.
>
> Re-confirm each field is still unused (report §1) before removing anything. Fields in report §2 are used
> by email, meta or logic. **Don't touch them.**

## Build

1. **Event location.** Emit a schema.org `PostalAddress` built from whichever Location address fields are
   filled, and `geo` (`GeoCoordinates`) only when both `lat` and `lng` are set. If none are filled, omit
   `address` rather than repeating the name. Keep `name`.
2. **Remove `Product::duration`.** Remove it from the admin form (`ProductForm.php:~103`) and from
   `$fillable`, then drop the column with a migration. Its only value is Tandem's "Approx. half a day at the
   dropzone": record that text in DECISIONS so it isn't lost.
3. **Remove `team_lead`.** Remove the admin field (`ManageHomePageSettings.php:115`) and the settings
   property, and add a settings migration that deletes `home.team_lead`. Follow the June
   `remove_orphaned_cms_settings` pattern.
4. **Remove `Location::image`.** Remove the admin field, the `image_url` accessor and the column (migration).
   All 4 are empty. Delete no files on disk.

## Rules

- **The homepage is signed off.** `team_lead` isn't rendered (`b494dd6`), so the homepage must be
  pixel-identical. Screenshot it before and after at 1440 and 390.
- Migrations are tested against a seeded copy, on MySQL as well as SQLite.
- Nothing in report §2 changes.

## Tests

- Course-event JSON-LD: `address` is a `PostalAddress` when the fields are filled and absent when they're
  empty, and never equal to the location name. Red on current `main`.
- The admin forms no longer have the three removed fields.
- The `HomePageSettings` loads without `team_lead`. A stale settings cache must not break a queued mail;
  see the typed-settings rule in CLAUDE.md.

## Finish

`composer check` + MySQL green. Homepage screenshots (cropped JPEG). DECISIONS entry. Push the branch. **Do not
merge** (unless on an authorised unattended run).

Owner content line in the summary: enter real dropzone addresses and coordinates in Locations; until then the
events carry the name only.
