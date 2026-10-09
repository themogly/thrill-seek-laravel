# 011 — Embed the mailer logo (CID) instead of hot-linking it from APP_URL

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the mailer-logo PNG entry and the email-audit entry),
`audits/reports/email-audit.md` (the E-2 decision) and the kit's `bootstrap.md` §3 (the CID rule).
**Size: small. Before staging.**

`git checkout main && git pull` → `git checkout -b fix/mail-logo-cid`.

> **Why this exists.**
> - **The logo URL:** `resources/views/vendor/mail/html/header.blade.php:11` loads
>   `url('/images/email/logo.png')`, an absolute URL built from `APP_URL`. Verified on `origin/main` =
>   `7e01bfe` by code read.
> - **Staging:** it sits behind HTTP basic-auth, so every recipient's client gets a 401 for that image and
>   every staging email shows a broken logo.
> - **Production:** many clients block remote images by default.
> - **The decision:** the email audit kept the absolute URL and deferred the switch to its own prompt (E-2),
>   because it changes every mail's markup and needs a real render-and-inbox check. This is that prompt.
> - **Ruled out:** SVG. Clients don't render it. The logo stays a PNG and stays out of the image-optimisation
>   pipeline.

## Build

- Embed the PNG via `$message->embed(...)` in the shared mail header. Find whether there's one header or
  several: `vendor/mail/html/header.blade.php` *and* `resources/views/mail/blocks/logo.blade.php` both exist.
  Route every mail through **one** logo partial; don't leave two.
- Keep the same rendered size (180×68), alt text and markup otherwise.
- Make sure the newsletter mails (`NewsletterCampaignMail`, rendered in the builder preview) still show the
  logo in the admin preview too. A preview has no `$message`. Handle it, decide how, and record it.

## Rules

- No change to any email's copy, recipients or layout, other than the logo source.
- The mail render test and `/dev/mail` must still render every mailable.

## Tests

- Every mailable's rendered HTML references the logo by `cid:`, not by an `http` URL. Red on current `main`.
- The render test's absolute-URL assertion still holds for the other links.
- The newsletter preview shows the logo.

## Finish

`composer check` green. Send one real email of each template to the log mailer, and save the `.eml` for the
booking confirmation in `audits/reports/` as evidence. DECISIONS entry. Push the branch. **Do not merge**
(unless on an authorised unattended run).

Owner check after staging: one real email in Gmail and one in Outlook shows the logo with images blocked.
