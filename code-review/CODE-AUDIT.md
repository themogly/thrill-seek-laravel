# Laravel code-style audit

Branch `chore/code-style-audit`, off main `237392c`.

Audited `app/` (controllers, models, Actions/Services, view models, Livewire, Filament,
jobs, events, mailables, observers, enums), `routes/`, `database/`, `config/` and
`tests/` against the philosophy in the brief and the project's documented conventions
(CLAUDE.md / DECISIONS.md).

## Headline

**The codebase is already idiomatic and consistent** — it has had explicit consistency
passes and they hold up. Four independent sweeps (controllers/routes, Actions/Services/
Mail, models/migrations, Support/ViewModels/Livewire) found almost nothing to change.
There are exactly **two items worth acting on** (one real bug, one duplication), plus a
couple of deliberate-by-design things noted and deferred. No documented decision needs
revisiting. This is a short report on purpose.

---

## PHASE 1 — Correctness & convention

- **`App\Support\StructuredData::breadcrumbs()` — every breadcrumb `position` is `1`.**
  → The positions are produced by `$position++` *inside an arrow function*
  (`fn (string $url, string $name) => [... 'position' => $position++ ...]`). PHP arrow
  functions capture by value, so the increment mutates a per-call copy and never
  advances — every `ListItem` gets `position => 1`. → Compute the position from the
  item's index explicitly (no hidden mutation), e.g. map to `name`/`url` pairs then
  `->values()->map(fn ($item, $i) => ['position' => $i + 1, ...])`. → It's live: the
  news-article page (`pages/news/show.blade.php`) emits this JSON-LD, so every article's
  breadcrumb structured data is invalid (Google expects 1, 2, 3…). The existing
  `StructuredDataTest` only asserts the `BreadcrumbList` type, not the positions — thin
  coverage, so pin with a test first. **(CA-P1.1)**

`Review:` This is the only genuine correctness defect found anywhere — and it's
invisible without rendering the JSON-LD, which is why it survived. It ranks first
because it's a real bug in shipped output, not a style preference. Everything else in
Phase 1 territory (delegation, casts, scopes, relationships, validation placement,
eager-loading, fail-loud error handling) is already correct across controllers, models
and Actions.

## PHASE 2 — Simplification & de-abstraction

- **`ConvertEnquiryToBooking::sendEmails()` and `ConfirmHeldBooking::sendReceipt()` are
  near-identical.** → Both queue the same `payment_received` `TemplatedMail` with the
  same variables (name, amount, product, reference, balance_note) and the same
  `PaymentReceivedAdminNotification`, wrapped in the same try/catch-and-log; only the log
  string differs. → Extract one shared `App\Actions\SendPaymentReceipt` with a
  `handle(Payment, Booking)` method (matches the project's established Action pattern)
  and have both call it. → DRY + the project's "one way to do everything" value: the
  receipt wording/recipients now live in a single place, so the two paths can't drift.
  Behaviour is identical (same mail, same recipients); pinned by the existing
  `StripeWebhookTest` which exercises both the enquiry-conversion and direct-booking
  webhook paths. **(CA-P2.1)**

`Review:` Sequenced after the bug because it's a maintainability/consistency win rather
than a defect. No other over-abstraction found: the single-implementation
`NewsletterService` interface is justified in CLAUDE.md (swap seam), `StripeCheckout` and
`ResolvesCheckoutPayment` earn their keep, and there is no dead code, no commented-out
blocks and no unused imports anywhere in the audited tree.

## PHASE 3 — Polish & readability

Nothing worth a code change. The naming, accessor/scope placement, docblocks ("why" not
"what"), enum/Filament structure and view-model shape are uniform across similar classes.

`Review:` Forcing Phase-3 edits here would be churn for taste against working, already-
consistent code — explicitly against the brief. None made.

---

## Considered and deliberately NOT changed

- **Inline `$request->validate()` in the Account/Login/Message/Review controllers** is
  idiomatic Laravel for simple, single-use rules; extracting Form Requests here would add
  indirection without reuse. Left as-is (correct).
- **`CourseMessageMail` uses the `Queueable` trait without `implements ShouldQueue`.**
  This is deliberate and documented in the class docblock — the per-recipient
  `SendCourseMessageToRecipient` job does the queueing so one bad address can't fail a
  batch. `Queueable` is the framework-default trait set; removing it would be cosmetic
  churn. Deferred (no behaviour or clarity gain).
- **`NewsletterSignup` honeypot state via the `ProtectsAgainstSpam` concern** matches the
  project's trait usage; not a violation. Left as-is.

## Discussion (needs owner decision)

None. Nothing in the audit conflicts with a documented architectural decision, so there
is nothing to escalate.

---

## Status

- [ ] CA-P1.1 — fix breadcrumb positions (+ pin test)
- [ ] CA-P2.1 — extract shared `SendPaymentReceipt` action
