# 018 — Tailor `verification/CHECKLIST.md` to G-Force

One branch, one task. Docs only. Read `CLAUDE.md`, `SETUP.md`, `PRE-STAGING-CHECKLIST.md`, `DECISIONS.md`,
`audits/reports/email-audit.md` (the inventory table), `verification/CHECKLIST.md` (the kit's checklist,
copied in untailored by 001) and `verification/real-device-checks.md`. **Size: medium.** Run it after the code
prompts in this queue have merged, so it describes the real build.

`git checkout main && git pull` → `git checkout -b docs/launch-checklist`.

> **Why this exists.** The checklist is the human launch gate: a person with a real card and a real inbox. Kit
> wording like "every money path" is useless at 7am on launch day. It has to name G-Force's actual paths,
> emails and settings, in the order someone walks them on staging.

## Build

Keep the kit's structure and its non-negotiables, and replace the generic items with G-Force's real ones.
**Enumerate from the code, not from memory:**

- **Every money path**, from the routes, Livewire booking components, Stripe Actions and the voucher flows:
  - Tandem booking and pay;
  - AFF deposit, then balance from the account;
  - voucher purchase;
  - voucher redemption;
  - payment link from an enquiry;
  - bank-transfer recording;
  - refunds, if any.

  For each: the steps, the test card, **the amount that must appear in the Stripe dashboard in pence → £**,
  and which email(s) must arrive.
- **Every email**, one line per row of the email-audit inventory: what triggers it, and what to look for in a
  real inbox (logo with images blocked, links to the staging/production host, reply-to).
- **Staging specifics:** the admin email setting pointed at a test inbox, `MAIL_MAILER` choice, basic-auth,
  `noindex`, Stripe TEST webhook events (from the webhook controller's handled events), `gforce:mail-test`,
  the dashboard's failed-emails figure.
- **The server's silent killers:** Horizon running, the `schedule:run` cron (list the scheduled commands
  from `routes/console.php` or the scheduler, with what proves each one ran), `storage:link`,
  `config:cache`.
- **Stripe API version.** stripe-php 22 sends API version `2026-09-30.endive` (004 majors; the app doesn't
  pin one). Each webhook endpoint, staging TEST and later live, must be created with that same API version
  in the Stripe dashboard, or events arrive in the account's older default shape. Make it an explicit
  checklist line, with where to see the endpoint's version.
- **Go-live flip:** live keys, the separate live webhook and its `whsec_`, one real low-value transaction and
  a refund, the production admin created with the 006 command, no `test@example.com`.
- **Device checks:** the subset of `real-device-checks.md` that applies to a public booking site on phones
  (toolbar, keyboard over the lowest input, date pickers, touch targets, the font loading on the live URL).
  Drop the tablet/counter items that don't apply, and say so.

## Rules

- Docs only. No code, config or test changes.
- Every item names something that exists. Grep for each route, command, setting and mail you list. No phantom
  items.
- No secrets or real card numbers. Stripe's public test cards are fine.

## Finish

`composer check` green, unchanged. DECISIONS entry. Remove the "NOT YET TAILORED" line. Push the branch. **Do
not merge** (unless on an authorised unattended run).
