# Admin / CMS audit — G-Force Skydiving

Kit file `audits/admin-audit.md`, run verbatim as item 4 of unattended run 1. Branch `admin/audit-pass` off
`main` = `39fd24a`. Report written before any fix.

**How it was audited.**
- **By using it:** logged in as the seeded admin on `thrill-seek-laravel.test` (rebuilt assets) and
  loaded, edited, saved, reloaded and reverted every settings page. I opened the edit screens with
  consequential delete buttons and saved a voucher's status. Deletes were run against the local MySQL
  data inside rolled-back transactions, so nothing was lost.
- **By code read:** a field-by-field read of every resource (forms, tables, relation managers,
  migrations' FK rules, public display ratios). Items verified only by reading are marked *code read*.

**Overall:** the admin is in good shape.
- Every settings page works.
- Image uploads are crop-locked to their display ratio through `ImageCrop`, with `object-cover` on the
  public side (one mismatch noted below).
- Customers, enquiries and campaigns already can't be hard-deleted, and GDPR erase is confirmed.

The real problems are **unguarded deletes and edits on records that money or other records hang off.**

## The settings-page check (kit's new singleton check) — verified, not a finding

All seven settings editors (`General`, `Home`, `Tandem`, `AFF`, `Coached`, `Other pages`,
`Before-your-jump info`) are custom pages on one base, `SettingsPage`. Pressed in the browser, each one:
- loads the saved values on mount;
- saves, and shows "Saved — Your changes are live on the site";
- persists across a reload (the General tagline change also showed on the public homepage);
- restores cleanly.

Blanking a required field doesn't save. The base runs `form->getState()`, so the field rules apply on
the server too (code read). The base is a complete settings page, not a loose half-built one.

## PHASE 1 — Critical

- **Products → Delete** (edit page and bulk): deleting a product **cascade-deletes all its course
  dates**, and with them their messages and reminders. It also unlinks bookings and add-ons
  (`course_dates.product_id` is `cascadeOnDelete`). → Disable Delete while the product has course
  dates, bookings, enquiries or vouchers, with a tooltip saying why, and remove bulk delete. "Active"
  is the way to retire a product. → Verified by doing: rolled-back delete of "AFF Course Levels 1–8"
  left 0 of its 2 course dates.
- **Bookings → Delete:** no guard, even on paid bookings. Payments and vouchers lose their booking
  (`nullOnDelete`), so revenue loses its record. → Disable while the booking has payments or a
  redeemed voucher; use status Cancelled instead. → Verified by doing: rolled-back delete of booking 1
  orphaned its paid £100 payment.
- **AFF courses → Delete:** no guard while students are enrolled. Bookings lose their course, and the
  course's message history and reminders are deleted. → Disable while it has any booking. → *Code read*:
  the cascade comes from the migrations. (Correction: the first draft said the button was seen on a
  course with bookings. Neither local course has bookings; the guard is proven by its test.)
- **Vouchers → Delete:** no guard, including on bought and redeemed vouchers. → Allow only an unused,
  admin-issued voucher; otherwise use Revoke. → *Code read*, button seen.
- **Vouchers → Status (and value) freely editable.** A **redeemed** voucher can be set back to Active
  and redeemed again; value and product stay editable after purchase. → Drop Status from the form
  (Redeem and Revoke actions own it). Lock value and product unless the voucher is admin-issued and
  unused. → **Verified by doing:** saving the edit form turned a redeemed voucher back to Active and
  `isRedeemable() === true`. That's money.

Review: every P1 is "the owner can destroy or double-spend money-linked data with one ordinary click".
The fixes are guards on the existing actions, through one shared helper (a model says why it can't be
deleted, and the Delete button is disabled with that reason). No schema change.

## PHASE 2 — Refinement (owner UX)

- **Tandem dates → Delete** with customers booked silently unlinks them, and no one is emailed. →
  Same guard as P1. *Code read.*
- **Locations → Delete** of a location in use hits the `restrictOnDelete` FK, and the owner gets an
  error page. → Same guard, with the reason in the tooltip. *Code read.*
- **Bookings → Status** offers "Awaiting payment", which the webhook owns. Editing a hold makes the
  webhook skip confirmation and voucher redemption. → Only show "Awaiting payment" when the booking
  is already in it, and lock the field while it is. *Code read.*
- **Disciplines:** `PageController` relies on the slugs `tandem`/`aff`/`coaching`. Renaming or
  deleting one silently empties the instructor sections on those pages, and the slug helper text is
  wrong. → Lock those three slugs and block their delete. *Code read.*
- **News → "Published" on with no publish date** never appears. → Require the date when Published is
  on, with today as the default. *Code read.*
- **Newsletter → a sent campaign** opens as "View" but is fully editable and saveable. → Read-only
  once sent. *Code read.*
- **Products → AFF deposit** isn't required. If it's cleared, the product drops out of the course
  picker and courses become unbookable. → Required for AFF. *Code read.*
- **Course / tandem capacity** can be set below the number already booked. → Minimum = current
  bookings. *Code read.*
- **General → "Social sharing image"** is a typed path (`/images/…`), so the owner can't upload a share
  image. → Proposed as follow-up **A-1**. It changes a settings property's consumer (`og:image`
  resolution across every page), which deserves its own check.
- **Testimonial photo** crop is 16:9, but `/testimonials` shows non-featured photos in a 4:5 tile;
  `object-cover` saves it from distortion but crops hard. → **Deferred, design decision:** the
  featured band shows the same photo at 16:9, so the fix is a choice between two crops (second crop
  field vs one ratio). Follow-up **A-2**.
- **No password reset for the owner** (`->login()` only). → Follow-up **A-3**. It adds a new email path
  (a framework notification), which should go through the email audit's inventory and render tests,
  not be bolted on here.

Review: Phase 2 fixes are validation and guards on existing fields. The three deferrals each carry a
decision (consumer change, design choice, new email path).

## PHASE 3 — Polish

- **Dashboard furniture:** the stock `AccountWidget` ("Welcome / Sign out") is registered. Sign-out
  already lives in the user menu. → Unregister it.
- **Panel primary is `Color::Amber`,** Filament's default, which nobody chose. → Set it deliberately
  to a blue ramp (the brand is blue), checked for white-on-primary button contrast.
  `OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS)`.
- Noted, not changed:
  - **Upload limits:** no image field sets `maxSize` or `acceptedFileTypes` (only Livewire's 12 MB
    default); the WebP optimise job already shrinks what's stored.
  - Enquiry table forces unread-first ordering ahead of column sorts.
  - Unused slug fields on Product and Location.
  - Raw IDs on past slots and courses in some selects.
  - The `navigationSort` clash on Unmatched messages.
  - Public `app.css` scans `views/filament` and keeps a `dark` variant: bundle bloat, no visible effect.

  All are proposed as one tidy-up prompt, **A-4**.

Review: kept to what the kit names; the rest is batched, not churned.

## OWNER tasks / decisions

- **`email_signoff`** (`GeneralSettings`) has no admin field. The owner can't change the email
  sign-off. Is it meant to be editable? (One-line answer → a field on General.)
- **Admin primary colour:** confirm the blue, or name another.
- **Testimonial photos (A-2):** one crop ratio for both places, or two crops?

## Proposed follow-up prompts

- **A-1** — the social sharing image as an upload, with an absolute-URL consumer.
- **A-2** — testimonial photo crop (after the owner's answer).
- **A-3** — owner password reset, through the email inventory.
- **A-4** — polish batch: upload limits, enquiry sort, unused slugs, raw-ID selects, nav sort, public
  CSS sources.

## Status after fixes (`admin/audit-pass`)

| Item | Status | Commit / proof |
|---|---|---|
| P1 Product / Booking / Course / Voucher delete guards (+ Tandem date, Location from P2) | **Done** | `fix(admin): guard Delete…`. `DeletionGuardsTest` (8 tests, red on main). Shared: `GuardsDeletion` + `AdminActions::guardedDelete()` / `guardedBulkDelete()`. Re-checked in the admin: Delete disabled with the reason on hover. |
| P1 Voucher status / value lock | **Done** | `fix(admin): a redeemed voucher…`. `VoucherEditLockTest` red on main. Re-checked: no Status field, Value locked on a used voucher. |
| P2 Booking "Awaiting payment" | **Done** | `BookingStatusFieldTest` |
| P2 Page-discipline slugs + delete | **Done** | `CoreDisciplinesTest`. Slugs are now `Discipline` constants used by `PageController`. |
| P2 News publish date | **Done** | `PublishDateRequiredTest` |
| P2 Sent newsletter read-only | **Done** | `SentCampaignReadOnlyTest`. A disabled schema alone still saved (a picture of a gate), so `beforeSave()` halts. |
| P2 AFF deposit required | **Done** | `AffDepositRequiredTest` |
| P2 Capacity floor | **Done** | `CapacityFloorTest` |
| P2 Social sharing image upload | Deferred → **A-1** | |
| P2 Testimonial crop | Deferred → **A-2** (owner) | |
| P2 Owner password reset | Deferred → **A-3** | |
| P3 AccountWidget removed, primary Blue | **Done** | `PanelFurnitureTest`. Measured white-on-primary: blue-600 **5.26:1** (old amber 3.19:1, brand sky 3.45:1). `OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS)`. Screenshots: `admin-audit-dashboard-after.png`, `admin-audit-product-edit-after.png`. |
| P3 polish batch | Deferred → **A-4** | |
