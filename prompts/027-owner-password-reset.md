# 027 — The owner can reset a forgotten admin password (A-3)

One branch, one task. Read `CLAUDE.md` (email conventions, `MailRenderTest` + `/dev/mail`), `DECISIONS.md` (the
admin-audit entry: "Deferred → A-3 owner password reset: a new email path, which should go through the email
inventory") and `audits/reports/email-audit.md`. **Size: small.** Written during unattended run 3 under Ben's
"also fix anything outstanding" (9 Oct 2026). A-3 was proposed by the admin audit and never written as a prompt.

`git checkout main && git pull` → `git checkout -b feat/admin-password-reset`.

> **Why this exists.** The panel has `->login()` only (`AdminPanelProvider.php:31`). An owner who forgets their
> password on the live server has no way back in except someone with SSH running `make:filament-user` or tinker.
>
> **Verified on `origin/main` = `8eff1c6` by code read:**
> - the `password_reset_tokens` table exists (the default users migration);
> - Filament's `ResetPassword` notification is `ShouldQueue`, so it goes through Horizon like every other mail,
>   and a failure lands in `failed_jobs` (the dashboard's failed-email figure);
> - Filament's request page is rate-limited (2 a minute);
> - **but** its failure path shows `__($status)`: for an unknown address, "We can't find a user with that
>   email address." That tells anyone which email is the admin login.

## Build

- `->passwordReset()` on the admin panel, with a `RequestPasswordReset` subclass that shows the **same "sent"
  notice for an unknown address** as for a real one (no enumeration). The throttle message stays.
- The email: Filament's notification in the `gforce` mail theme. Add it to `/dev/mail`, `MailRenderTest`, the
  email audit's inventory, and the launch checklist's email table (§2).
- Help guide: one line in the getting-started section ("Forgot your password? …").

## Rules

- Customers are unaffected (separate guard, magic links). Only `User` rows (staff) can reset.
- No change to login itself.

## Tests

- The request page renders; a staff email queues the reset notification to that user; an unknown email queues
  nothing **and shows the same notice**. The notice part is red with Filament's default page.
- The reset link works end to end: the token from the notification resets the password, and the new password
  logs in.
- The email renders in the brand theme (`MailRenderTest`).

## Finish

`composer check` green. DECISIONS entry (A-3 done). Push the branch. **Merge on green** (unattended run 3).
