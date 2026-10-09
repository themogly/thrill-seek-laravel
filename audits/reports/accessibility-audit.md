# Accessibility audit — G-Force Skydiving

Kit file `audits/accessibility-audit.md`, run verbatim as item 5 of unattended run 1. Branch `a11y/audit-pass`
off `main` = `69be8dd`. Report written before any fix.

**How it was audited.**
- **Automated:** axe-core 4.10.2 (WCAG 2.0/2.1 A+AA plus best-practice) on rebuilt assets, injected into
  the real pages in Chromium (Playwright). It covered 21 public pages at 1440 px and 390 px, the 404,
  and 6 signed-in account pages via a locally issued magic link. Reduced motion was emulated so the
  entrance reveals didn't hide text from the contrast check (they're skipped under reduced motion).
- **By hand:** a keyboard pass on `/tandem` (tab order, focus ring on every stop; "Why Us" opens with
  Enter, closes with Escape and returns focus; the FAQ accordion runs on buttons). I submitted the
  empty contact form and inspected the error and toast markup. Toast and inline-error colours were
  measured by computing their contrast, because axe never sees a toast that isn't on screen.

**Baseline (axe, before):**
- `color-contrast`: on every page.
- `heading-order`: `/aff`, `/book/tandem`, `/book/aff`, `/news`.
- `page-has-heading-one`: `/testimonials`.
- Nothing else. June's pass (`62b6c30`) already cleared the ARIA and footer-heading issues, and forms,
  landmarks, image alt, `lang` and titles are all clean.

## PHASE 1 — Blockers

- **Brand primary contrast (all pages) → OWNER DECISION, not changed.** Every axe contrast failure is
  the one brand colour, `--primary: oklch(0.62 0.18 240)` = `#008fe6`:
  - as text on white (nav active state, eyebrows, dates, links, `£210 per jump`): **3.5:1**;
  - as a fill under near-white button text (every primary button, "Book now"): **3.37–3.41:1**;
  - on the light-blue message bubble in the account: below 3:1.

  AA needs 4.5:1 for this text size. **Nearest passing shade for both uses: `oklch(0.545 0.18 240)` =
  `#0078cc`** (4.63:1 as text on white, 4.51:1 under white text). Changing it changes the signed-off
  homepage and every CTA, so it's the owner's call. Options and spec are in DECISIONS
  (`OWNER DECISION — PENDING`). → **Why it matters:** low-vision users can't reliably read the site's
  most important calls to action.
- **The brand `destructive` token is 4.32:1** under white text and about the same as error text on
  white, just short of 4.5 for the 14 px inline errors in the booking forms → same owner decision
  (nearest passing given there).
- **Toasts fail contrast and use off-palette colours.** The success toast ("Message sent!", "Enquiry
  sent!", newsletter "check your inbox") is `bg-green-600` with white text, **3.13:1**. Error and info
  toasts are `bg-red-600`/`bg-blue-600`, raw Tailwind colours that break the palette-only rule. →
  Render all toasts in palette tokens that already pass: navy `secondary` (**14.2:1**) for
  success/info, and the same navy with a `destructive` left rule for errors, so colour isn't the only
  cue. No brand colour changes. → **Why it matters:** the confirmation that a form was sent is
  unreadable for some users.
- **`/testimonials` has no `<h1>`** when a featured testimonial is the hero: the "Testimonials" eyebrow
  is a `<p>`. → Make that eyebrow the `<h1>` with the same classes, so nothing visual changes. → **Why
  it matters:** screen-reader users navigate by headings, and the page has no top-level one.

Review: only the toast and `<h1>` fixes are code changes. The contrast of the brand colour itself is
recorded with its nearest passing shade and left alone, per the run rules.

## PHASE 2 — Important

- **Heading order:** the booking flows' step headings are `<h3>`/`<h4>` straight under the page
  `<h1>` (`livewire/book-tandem`, `book-aff`). News cards are `<h3>` with no `<h2>` (`news/index`).
  The AFF info cards are `<h4>` under the section's `<h2>` (`pages/aff`). → Promote one level each,
  tag only, same classes.
- **Toasts aren't announced.** The toaster has no live region, so success messages and simple-form
  errors never reach a screen reader. → Make the container `role="status"` / `aria-live="polite"`, and
  error toasts `role="alert"`.
- **Booking-field errors aren't tied to their input.** `<x-booking.field>` renders the error with
  `role="alert"`, but the input has no `aria-invalid` and no `aria-describedby`. → Give the error an id,
  and have the form controls pick up the field's error with Blade `@aware` (server-rendered, so
  Livewire re-renders it). All 27 booking fields are fixed at once.
- **No skip-to-content link.** → Add one to the layout, visually hidden until focused, targeting
  `<main id="main">`.

Review: the shared-component fixes (toaster, booking field, layout) land everywhere at once.

## PHASE 3 — Polish

- Dropdown items show focus as a background tint plus colour change rather than a ring. Visible, so
  left as is.
- Reduced motion is already honoured (reveals and photo zoom). Touch targets weren't measured on a
  real device; see `verification/real-device-checks.md`.

## OWNER

- **`OWNER DECISION — PENDING`:** brand primary `#008fe6` and `destructive` contrast. Options and the
  nearest passing shades are in DECISIONS.
