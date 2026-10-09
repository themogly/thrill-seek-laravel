# 025 — Admin tidy-up: past choices that can't be saved, unsafe upload types, a sort that can't sort (A-4)

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the admin-audit entry: "Deferred → A-4") and
`audits/reports/admin-audit.md` Phase 3. **Size: small to medium.** Written during unattended run 3 under Ben's
"also fix anything outstanding" (9 Oct 2026). The admin audit proposed A-4 as one tidy-up prompt; it was never
written. This is it.

`git checkout main && git pull` → `git checkout -b chore/admin-tidy-up`.

> **Why this exists.** The admin audit's polish batch (A-4), re-verified on `origin/main` = `747468a`. One item
> turned out to be a real defect, not polish.
>
> - **A past choice blocks Save (verified by doing, in a Livewire test).** The booking form's "Jump slot" select
>   lists only upcoming slots (`BookingForm.php:66-77`). For a booking whose slot has passed, the select shows the
>   raw id ("1"), and **Save fails with "The selected jump slot is invalid."** The same happens on a news
>   article linked to a past or closed course (`NewsForm.php:82-92`: "The selected link an AFF course (optional)
>   is invalid."). The newsletter's featured-course block builds its options the same way
>   (`NewsletterCampaignForm.php:138-146`). The audit only saw "raw IDs".
> - **Uploads accept any image type, including SVG, onto the public disk.** 18 `FileUpload::make()` image
>   fields; none sets `acceptedFileTypes`, and only the `ImageCrop` ones set `->image()` (which allows
>   `image/svg+xml`). Livewire's 12 MB default is the only size cap. Documents set their own types and size, and
>   are fine.
> - **The enquiry inbox can't be re-sorted.** `EnquiriesTable::configure()` forces `read_at is null desc` in
>   `modifyQueryUsing`, so clicking the "Last activity" sort still keeps unread rows on top.
> - **Navigation sort ties.** In *Bookings & sales*, Bookings and Unmatched Messages are both 2, and Documents
>   and Newsletters both 11. In *Site content*, Disciplines and Testimonials are both 12, and FAQs and Hall of
>   Fame both 13. The order is decided by registration order, not by intent.
> - **The public CSS compiles the admin's views.** `app.css` `@source '../views'` includes `views/filament`, and
>   `@custom-variant dark` exists only for those three views' `dark:` classes. The admin has its own theme
>   (`resources/css/filament/admin/theme.css`, which sources `views/filament`).
> - **`motion` is a dependency nobody imports** (`package.json`; 0 imports in `resources/js`; DECISIONS, run 1:
>   "a possible cleanup for its own prompt").
>
> **Ruled out:** the "unused slug fields on Product and Location". Product's slug is now the key of the price
> tokens (014) and is used. Location's slug is a schema question for the CMS-field gate, not a tidy-up. Left
> alone.

## Build

1. **Past choices stay valid.** One helper for every select whose options are time-filtered: the upcoming
   options **plus the field's current value**, labelled the same way (marked "(past)" when it is). Use it in the
   booking slot, news course and newsletter featured-course selects. The reschedule action's slot select picks
   a *new* slot and has no stored value: exempt it, with the reason, in the guard.
2. **One image upload factory**, `AdminImages::upload($name)` (like `AdminDates`): `->image()`, accepted types
   JPEG/PNG/WebP (no SVG, no GIF), `maxSize` 12 MB (Livewire's existing cap, now with Filament's friendly
   message). Replace every image `FileUpload::make()`. Documents keep their own factory-free field. The size and
   type list are agent defaults: mark them `OVERNIGHT-DEFAULT — CONFIRM`.
3. **Enquiries:** the unread-first ordering becomes the table's *default* sort (a closure), so choosing a column
   sort takes over.
4. **Navigation:** unique sorts within each group, **preserving today's rendered order exactly**.
5. **Public CSS:** exclude `views/filament` from `app.css` sources and drop the `dark` custom variant. The public
   pages must render pixel-identically; the admin is unaffected (its own theme).
6. `npm uninstall motion`, and remove the stale "Motion One" mentions in code comments.

## Rules

- No visual change to any public page (prove the homepage full-page at 1440 and 390, 0 px) and no change to the
  admin's navigation order.
- No migration, no data change.

## Tests (required)

- Editing a booking whose slot has passed saves, and the select shows its label, not its id. The same for a news
  article linked to a past course. **Red on `main`.**
- A structural guard: every select whose options call `upcoming()` or `upcomingOpen()` goes through the helper
  (allowlist with reasons), proven by a planted violation.
- A structural guard: no `FileUpload::make(` in `app/Filament` except Documents, proven by a planted violation;
  an SVG upload to an image field is refused.
- Enquiries: sorting by "Last activity" ascending puts the oldest first, unread or not. Red on `main`.
- Navigation: no two items in a group share a sort, and the rendered order equals today's.

## Finish

`composer check` + MySQL green. DECISIONS entry. Help guide if the owner sees a change (the upload limits).
Push the branch. **Merge on green** (unattended run 3).
