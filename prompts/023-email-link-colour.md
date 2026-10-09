# 023 — Email buttons and links use a blue that fails contrast

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the 016 entry: "emails use their own `#2f8de4`
(3.47:1): listed, not changed"; the 011 CID logo entry) and `audits/reports/email-audit.md`. **Size: small.
Depends on 016 being merged** (it brings `primary-strong` `#0078cc`).

`git checkout main && git pull` → `git checkout -b fix/email-link-colour`.

> **Why this exists.** Ben, 9 Oct 2026: yes, move the emails to the accessible blue.
>
> **Verified on `origin/main` = `fd6bc55` by grep:** `#2f8de4` appears in:
> - `resources/views/mail/blocks/button.blade.php`, `featured_course.blade.php` and `two_column.blade.php`;
> - `mail/newsletter/shell.blade.php` and `footer.blade.php`;
> - `vendor/mail/html/themes/gforce.css`;
> - `pdf/booking-receipt.blade.php` and `pdf/voucher.blade.php`.
>
> White on `#2f8de4` is 3.47:1. The site moved to `#0078cc` in 016.

## Build

- Replace the email and PDF uses that are **text, or a fill under text**, with `#0078cc`, defined once per
  medium. Email CSS can't read the site's CSS variables, so use one variable or partial for mail and one for
  the PDFs. Keep any purely decorative rule or border bright if that's what the site does.
- Check the white button text on `#0078cc` the same way 016 did (4.61:1 against pure white). The mail
  buttons must use pure `#ffffff` text.

## Rules

- No change to any email's copy, layout, recipients or logo (011's CID embed stays).
- The mail render test and `/dev/mail` render every mailable. The newsletter builder preview matches.

## Tests

- No mailable or PDF view contains `#2f8de4` used as a text or fill colour under text (a structural grep
  test with an allowlist for decoration, each entry with a reason). Red on current `main`.
- The render tests still pass.

## Finish

`composer check` green. Send each template to the log mailer, and save one booking confirmation `.eml` plus a
rendered receipt PDF screenshot (cropped) as evidence. DECISIONS entry. Push the branch. **Do not merge**
(unless on an authorised unattended run).
