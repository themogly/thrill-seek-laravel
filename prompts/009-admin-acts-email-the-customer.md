# 009 — Admin-originated confirmations and redemptions email the customer (toggle, default on)

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the email-audit entry and its `OWNER DECISION —
PENDING` on admin-originated emails) and `audits/reports/email-audit.md` ("OWNER DECISION — PENDING" and
follow-up E-4). **Size: small to medium.**

`git checkout main && git pull` → `git checkout -b feat/admin-email-customer-toggle`.

> **Why this exists.** Ben, 9 Oct 2026, answering the email audit's owner decision: **option B**, an "Email
> the customer" toggle, default on, the same pattern as Reschedule.
>
> From the report:
> - **(a)** Creating a booking in the admin with status Confirmed sends **no** `booking_confirmed`, while
>   confirming an existing booking does. Verified by doing.
> - **(b)** Vouchers → Redeem against a booking records a paid payment but sends **no** receipt, while
>   recording a bank transfer sends one. Code read.
>
> Mark the DECISIONS entry's `OWNER DECISION — PENDING` as answered.

## Build

- Add the toggle to (a) the admin booking create form, visible only when the status is Confirmed, and (b) the
  Redeem action's form. Default on. Copy how Reschedule does it, and reuse its component and wording.
- When it's on, send the **same** mail the online path sends, through the **same** action. Don't add a second
  send path; the email audit just removed one (`MailInventoryTest`).
- When it's off, send nothing, and don't show a "sent" message.
- Update `MailInventoryTest` pairings for any new "emailed" UI string.

## Rules

- No change to the online booking or payment emails.
- No back-filling. Editing an old booking never sends anything.
- `QueuedMailable` rules apply: retries and after-commit.

## Tests

- (a) and (b) each with the toggle on → mail queued, with the recipient and key data asserted. Red on current
  `main`.
- Each with the toggle off → nothing queued.
- Editing an existing confirmed booking → nothing queued.

## Finish

`composer check` green. DECISIONS entry. Push the branch. **Do not merge** (unless on an authorised unattended
run).
