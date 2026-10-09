# 008 — The database, not just the admin button, refuses deletes that money hangs off (CHECKPOINT)

One branch, one task, **in two phases with a checkpoint between them.** Read `CLAUDE.md` (the "gate must never
become a picture of a gate" rule), `DECISIONS.md` (the admin-audit entry and its gap report: "the FK cascade
was left for its own prompt") and `audits/reports/admin-audit.md` P1. **Size: medium.** This is a schema
change, and the schema is Ben's, hence the checkpoint.

`git checkout main && git pull` → `git checkout -b fix/fk-delete-rules`.

> **Why this exists.** Ben, 9 Oct 2026: make the database refuse these deletes rather than cascade them.
>
> The admin audit added `GuardsDeletion` + `AdminActions::guardedDelete()` / `guardedBulkDelete()`, which
> disable Delete in Filament. **Verified on `origin/main` = `7e01bfe` by code read:** nothing below the admin
> enforces it. There's no `deleting` model event on the seven `GuardsDeletion` models, and the foreign keys
> still say:
> - `course_dates.product_id` → `cascadeOnDelete` (`2026_06_10_110000_create_course_dates_table.php:13`).
>   Deleting a product deletes its course dates, and their messages and reminders cascade too.
> - `payments.booking_id` → `nullOnDelete` (`2026_06_10_000010_…:34`). Deleting a booking orphans its
>   payment. The audit proved this by doing.
> - `vouchers.booking_id`, `bookings.product_id`, `enquiries.product_id` → `nullOnDelete`.
>
> So any delete that doesn't go through those two buttons (tinker, a future action, a queued job, a seeder,
> a package) still destroys or orphans money-linked records.
>
> **Ruled out:** GDPR erasure. `EraseCustomerData` anonymises bookings and payments rather than deleting them,
> so restricting these deletes doesn't break erasure. Confirm it with a test.

## Phase 1 — proposal (then STOP)

Write `audits/reports/fk-delete-rules.md` with one table row per foreign key in `database/migrations` (all of
them, not just the ones above). Columns:
- child table and column → parent;
- current `onDelete`;
- does money, a booking, a payment or the customer's history hang off the parent?
- every code path that deletes the parent (grep `->delete(`, `forceDelete`, `DeleteAction`, bulk actions,
  erasure, tests, seeders);
- **proposed** `onDelete` (`restrict`, `cascade` for pure child data such as login links, or keep
  `nullOnDelete` where the history should survive the parent);
- your reasoning.

Also propose the model-level refusal: a `deleting` listener on each `GuardsDeletion` model that throws when
`deletionBlocker()` is non-null. Say how it relates to `guardedDelete()`: the button explains, the model
refuses.

**Commit the report. Push the branch. STOP.** On an unattended run, this branch stays unmerged and the run
continues with the next item. Ben approves or edits the table before Phase 2.

## Phase 2 — build (only after Ben's approval is recorded in DECISIONS)

- One migration that changes the FKs exactly as approved. It has to work on MySQL 8 *and* SQLite (Laravel's
  schema builder handles the SQLite rebuild; prove it).
- Test it against a **seeded copy**, not just a fresh DB.
- The `deleting` listeners.
- Leave `GuardsDeletion` / `guardedDelete()` as they are.

## Rules

- No data deleted or changed by the migration. Before it changes an FK, it checks for existing rows the new
  rule would forbid and aborts with a clear message if there are any.
- Erasure still works. Customer-facing flows unchanged.

## Tests (Phase 2)

- Deleting a product with course dates throws at the model, and at the DB with the listener bypassed
  (`deleteQuietly` / raw query). Red on current `main`.
- The same for a booking with a payment.
- A structural guard: every FK whose parent implements `GuardsDeletion` is `restrict` or listed in an
  approved allowlist with a reason. Proven by a planted violation.
- `EraseCustomerData` still passes on a customer with paid bookings.
- The full MySQL suite.

## Finish

Phase 1: the report pushed, then stop. Phase 2: `composer check` + MySQL green, DECISIONS entry, push. **Do not
merge** (unless on an authorised unattended run after Ben's approval).
