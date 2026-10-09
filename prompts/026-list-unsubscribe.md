# 026 — Newsletters carry a one-click List-Unsubscribe header (E-1)

One branch, one task. Read `CLAUDE.md` (newsletter conventions), `DECISIONS.md` (the email-audit entry:
"`List-Unsubscribe` header not added (needs a POST endpoint): follow-up E-1") and
`audits/reports/email-audit.md`. **Size: small.** Written during unattended run 3 under Ben's "also fix anything
outstanding" (9 Oct 2026). E-1 was proposed by the email audit and never written as a prompt.

`git checkout main && git pull` → `git checkout -b feat/list-unsubscribe`.

> **Why this exists.** Gmail and Yahoo ask bulk senders for an RFC 8058 one-click unsubscribe: a
> `List-Unsubscribe` header with an https URL, plus `List-Unsubscribe-Post: List-Unsubscribe=One-Click`, where the
> mail client POSTs to unsubscribe. Without it the inbox shows no "Unsubscribe" button, and recipients reach for
> "Report spam" instead, which hurts the domain's reputation for every email the business sends.
>
> **Verified on `origin/main` = `88a5bf8` by code read:**
> - `NewsletterCampaignMail` (`app/Mail/NewsletterCampaignMail.php`) builds a signed
>   `newsletter.unsubscribe` URL for the footer but sets no headers;
> - `/newsletter/unsubscribe/{subscriber}` is **GET only** (`routes/web.php:41`), behind `signed`;
> - nothing is CSRF-exempt for a POST to it (`bootstrap/app.php`);
> - the Resend transport passes custom headers through (`ResendTransportFactory`: everything except
>   from/to/subject/… goes into `headers`).

## Build

- `NewsletterCampaignMail::headers()`: `List-Unsubscribe: <signed https URL>` and
  `List-Unsubscribe-Post: List-Unsubscribe=One-Click`. Same signed URL as the footer link: one URL, one
  subscriber.
- A **POST** route on the same path, behind `signed`, CSRF-exempt (mail providers POST with no session). It
  unsubscribes through the same `NewsletterService::unsubscribe()` (one writer) and returns a small 200.
  Idempotent: a second POST is still 200.
- The confirmation email and transactional mail get **no** header (they aren't bulk, and there's nothing to
  unsubscribe from).

## Rules

- The footer link's GET behaviour is unchanged.
- An unsigned or tampered POST is refused (403) and changes nothing.

## Tests

- The campaign mail's headers carry the signed URL and the one-click POST header. Red on `main`.
- POST to the header's URL unsubscribes; a second POST is 200; a tampered signature is 403 and leaves the
  subscriber subscribed; no CSRF token is needed.
- Confirmation and transactional mails carry no `List-Unsubscribe`.

## Finish

`composer check` green. DECISIONS entry (E-1 done). Push the branch. **Merge on green** (unattended run 3).
