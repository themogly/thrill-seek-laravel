# Email Audit (report-first, then fix in phases)

A reusable audit of every email the app sends: whether it is sent at all, whether it survives the queue, whether it
reaches the person in their language with working links, and whether anyone would notice if it stopped. Email is
the most common silent failure in a Laravel app. The screen says "sent", the test is green, and nothing arrives.

Read the project's `CLAUDE.md`, `DECISIONS.md`, `SETUP.md`, the `laravel-craft` skill (the "Background work,
webhooks, mail" section) and `../false-green.md` first.

## Method

- Run `git checkout main && git pull`, then create the branch `email/audit-pass`. State the starting commit.
- **Build the inventory before judging anything.** Every email the app can cause to be sent goes in one table:
  - every class in `app/Mail`;
  - every `Notification` whose `via()` can return `mail`;
  - everything a package sends for you: password reset, email verification, Filament / Fortify / Breeze flows,
    invitations from an admin package;
  - every raw `Mail::raw` or `Mail::html`.
- **Record for each email:**
  - what triggers it;
  - **every** call site, by `file:line`;
  - whether each send is queued, and on which connection and queue;
  - whether the recipient's locale is pinned;
  - what the UI says after sending;
  - which test asserts the send.
- **Grep for the absences, not just the presences.** An email that should exist but has no caller is the finding
  that matters most, and reading the callers you did find will never show it.
- **Verify by doing, not by reading.** Use `MAIL_MAILER=log`, or Mailpit if the project has it. Press the real
  control in the real UI, then read what landed in the log or inbox. A `Livewire::test()->call()` or a unit test
  proves the method, not the button (false-green §1).
- Do not change the wording of an email or who receives it, except as the fix to a finding. Route any conflict with
  a documented decision to a `## Discussion` section.

## Step 1 — Report FIRST

Write `audits/reports/email-audit.md`. **Open it with the inventory table**, then list the findings as
`- [email]: [finding] → [fix] → [why it matters]`, under PHASE 1 / 2 / 3, each phase ending in `Review:`. Keep
OWNER/OPS tasks (DNS, provider dashboard, API keys on the server) in a section of their own. Commit the report
before fixing anything.

### PHASE 1 — Must-fix (an email that is promised but never arrives)

**Every promised email has a real send.**

- Every mailable has at least one production caller outside `app/Mail` and any dev preview. A mailable nobody sends
  is either dead code or a missing send. Find out which.
- **Sibling paths:** when the same act can happen from two screens (admin panel and counter, web and API, manual
  and scheduled), each one sends the same emails.
  - The usual cause of a gap is the send living in one caller instead of the single action both callers use.
  - The fix is to move the send into that action, not to copy it into the second caller.
- **Every sentence in the UI that claims an email went out** is preceded, in the same code path, by a real
  `Mail::…->queue()`, `->send()` or `notify()` that can actually run. Grep the views, flashes and notifications
  for the words, in every locale the app has: *enviado/enviada*, *sent*, *emailed*, *you'll receive*, *check your
  inbox*.
- A success message is never shown when the send threw or returned false. A `try/catch` that swallows the error
  and still flashes success counts as a finding.

**Configuration that silently kills every send.**

- In production the mailer is not `log` or `array`.
- **The provider key's env var name matches what `config/services.php` actually reads.** Check it character by
  character against `.env.example`, `SETUP.md` and any runbook. `RESEND_KEY` against `RESEND_API_KEY` is a real
  example, and a mismatch means an empty key and every mail failing in the worker.
- The from-address is on a domain verified with the provider, not `example.com` and not an unverified domain.
- `APP_URL` is the real public URL, because every link in every email is built from it inside the worker.

**The queue: queued is not sent.**

- A worker (Horizon or `queue:work`) runs under a supervisor in production, and it consumes **the queue the mail is
  actually pushed to**. A mailable with `onQueue('mail')` and a Horizon supervisor that only watches `default` is
  never sent. Check it against the Horizon config, not by assumption.
- `QUEUE_CONNECTION` in production is what `SETUP.md` says it is, and is not `sync` by accident. With `sync`, a
  provider timeout blocks the user's request.
- Mail jobs **retry**. With `tries => 1` and no `$tries` or `$backoff` on the mailable, one network blip loses the
  email for good.
  - Set retries per mailable, through one abstract base class, rather than globally. Other jobs may not be safe to
    retry.
  - A sensible default is 3–4 tries with backoff of roughly 30 s → 2 min → 10 min.
- A mailable that serialises a model is queued **after commit**, with `$afterCommit = true` on the base class.
  Otherwise the worker can pick it up before the row exists, or after a rollback, and fail on a missing model.
- A mail job that finally fails is **visible to the business**: a `failed()` hook, or an event listener, that
  records it somewhere the owner looks. A `failed_jobs` table nobody reads doesn't count.

### PHASE 2 — The email that arrives is right

**It renders correctly in the worker.**

- **The locale is pinned** on every queued send (`->locale(...)`, or `HasLocalePreference` on the notifiable). The
  worker has no session. Without a pinned locale, every queued email goes out in the app's default language,
  whatever the recipient chose.
- Templates don't rely on request, session, `auth()` or cached state that only exists in a web request. The
  `laravel-craft` note on typed-settings and readonly properties in queued contexts applies here.
- Each mailable has a **render test**, and a **dev-only preview route** that returns 404 in production. Mocked mail
  never renders, so a template error shows up only in the worker.

**The content and links are right.**

- Links are absolute, point at the production host, and still work after the job has sat in the queue for a while.
  A signed URL has to outlive the retry window.
- Subject, sender name and reply-to are the business's own, never the framework default.
- There is a plain-text alternative.
- The email carries the minimum personal data. Tokens appear only in the link, never in logs.

**It is sent once.**

- Scheduled or reminder emails carry a "sent" marker, so a second run of the command, or a retried parent job,
  doesn't send them again.
- A batch is queued **one message per recipient**, so one bad address can't fail the whole batch, and no recipient
  can see another's address.
- A retried mail job doesn't produce duplicates of anything else: tokens issued, rows written.

**Privacy.**

- Audit logs record the *act* of sending, never the address or the token.
- Marketing mail has an unsubscribe link that works. Transactional mail doesn't pretend to be marketing, or the
  other way round.

### PHASE 3 — Someone would notice

**Tools to see it working.**

- **A mail test command,** `php artisan <app>:mail-test {email}`. It sends one plain message **synchronously**
  through the configured mailer and prints either "sent" or the transport's actual error. It's the one tool that
  answers "is mail working?" on a live server in ten seconds.
- **A health check,** on the app's health or status page if it has one. It reads configuration only and never
  sends:
  - it flags `log`/`array` in production, an empty provider key and a placeholder from-address;
  - it shows the number of mail jobs failed in the last 7 days, grouped by mailable.

**A structural guard, so this can't drift again.** Add an inventory test (`tests/Feature/Mail/MailInventoryTest.php`)
that walks the whole class, not one instance:

1. Every mailable, other than any declared as dev-only, has a production caller.
2. Every mailable extends the base class that carries the retry and after-commit rules.
3. Every `Mail::to(...)` call site pins a locale before `queue(`. A source-text match is crude, but it catches
   exactly this mistake.
4. Every UI string that claims an email was sent is listed in the test, next to the mailable that makes it true. A
   new claim fails the build until someone pairs it.

**Prove the guard fails on purpose.** Plant one violation of each rule, watch the test go red, then remove it
(false-green, "prove a guard").

**Tests that assert the send.** Every test whose name says an email is sent must actually assert it. Use
`Mail::fake()` with `assertQueued`, or `assertSent`, with the recipient and the key data. Rename, or fix, any test
that only asserts a database row or a success flag.

**Deliverability (OWNER/OPS).** These go in the owner section, and the audit never fakes them:

- SPF, DKIM and DMARC records for the sending domain;
- the domain verified in the provider's dashboard;
- optionally, a bounce/complaint webhook, so a dead address becomes visible.

## Step 2 — Fix in phase order

Work through Phase 1 first, with a check gate between phases.

- **Failing-first:** every missing send gets a test that asserts the mail is queued, and that test is seen red
  against current main before the fix.
- **One commit per item.** Keep the check gate green, and never commit red.
- Move sends into the single action both paths share rather than duplicating them.
- Don't change who receives an email or what it says, except as the fix to a finding.

## Finish

Update the report, with the inventory table in its final state. Then confirm:

- every mailable has a caller and a render test;
- the inventory test passes, and was proven to fail on a planted violation;
- the mail test command works against the log mailer;
- the full suite is green.

Update `DECISIONS.md` and `SETUP.md`. Include the exact env var names, and the post-deploy step:
`php artisan <app>:mail-test you@yourdomain`, then check that failed mail jobs read zero after the first day. Push
the branch, and do not merge.

Summarise the owner/ops tasks: DNS records, domain verification, the provider key on the server under the right
name, and a real email received in a real inbox. See `../verification/pre-launch-checklist.md` §2.
