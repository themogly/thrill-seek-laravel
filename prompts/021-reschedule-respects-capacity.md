# 021 — Rescheduling can overbook a full tandem slot

One branch, one task. Read `CLAUDE.md` ("one writer per fact"; "a gate must never become a picture of a
gate"), `DECISIONS.md` (the tandem capacity and holds entries) and `verification/CHECKLIST.md` (018's gap
report: "rescheduling doesn't check the target slot's capacity"). **Size: small to medium.**

`git checkout main && git pull` → `git checkout -b fix/reschedule-capacity`.

> **Why this exists.** 018 found it while tailoring the launch checklist. Ben, 9 Oct 2026: **it should refuse**.
> Staff rescheduling after a weather cancellation could put more people into a slot than there are
> instructors and rigs. That's a real problem on a jump day.
>
> **Verified on `origin/main` = `fd6bc55` by code read:**
> - `RescheduleBooking::handle()` (`app/Actions/RescheduleBooking.php:22-38`) sets `tandem_date_id` to the
>   target `TandemDate` and saves. No capacity check.
> - `TandemDate` has `remaining_capacity` and `isFull()` (`app/Models/TandemDate.php:64-71`), and the online
>   booking path respects them.
> - The admin action is `BookingsTable.php:72-125`.
>
> **Ruled out:** the ad-hoc date/time path (`Carbon`, no slot). It has no capacity to check, so leave it
> alone, and say in DECISIONS that it's deliberately unconstrained.
>
> Confirm by doing first: fill a slot locally, then reschedule another booking into it. Already refused →
> stop.

## Build

- **One writer.** The capacity check lives in `RescheduleBooking` (or in the single place the online path
  checks capacity, if that's shared), not in the Filament form. Count the moving booking's party size if
  bookings carry one, and don't count the booking against its own current slot when it "moves" within the
  same slot.
- Refuse with a plain-English reason ("That slot has 1 place left; this booking needs 2."), and surface it as
  the admin action's error notification.
- **The button explains as well:** in the action's slot select, mark full slots as full (or disable them).
  Pick the clearer of the two. That's a convenience; the action is still the gate.
- Holds: decide whether unexpired checkout holds count against capacity the way the online path counts
  them. **Match the online path exactly**, and record what it does.

## Rules

- No change to online booking capacity logic, except to reuse it.
- The ad-hoc time path unchanged.
- The customer email only goes out when the reschedule succeeded (`RescheduleBooking` returns its result
  today; keep that).

## Tests

- Reschedule into a full slot → refused, the booking unchanged, no email queued. Red on current `main`.
- Into a slot with exactly enough places → succeeds.
- Party size counted.
- Calling the action directly with a full slot (bypassing the select) is still refused: the gate isn't a
  picture.
- If capacity now has two readers, a test asserting the online path and the reschedule agree on the same
  slot.

## Finish

`composer check` + MySQL green. DECISIONS entry. Push the branch. **Do not merge** (unless on an authorised
unattended run).
