# Design review — audit notes (design/visual-polish)

Screenshots: `before/<page>-<width>.png` (full page) and `-hero.png` (viewport).
Tooling note: the Playwright MCP server was not reachable from this session, so the
loop is driven by `design-review/shoot.mjs` (Playwright via Node) — same evidence,
plus console-error/failed-request capture on every shot.

## Frontend ↔ backend mismatch list

| # | Mismatch | Plan |
|---|---|---|
| M1 | Tandem "Pay £260 with Stripe" card is a placeholder toast; admin manages availability slots no customer can see | Public multi-step tandem booking flow (`/book/tandem`): slot picker → details → Stripe Checkout |
| M2 | AFF "Pay deposit" card is a placeholder toast; no course dates exist anywhere | New CourseDate domain (admin CRUD + enrolment view) + public course list on AFF page + deposit booking flow (`/book/aff`) |
| M3 | Newsletter forms (home + contact) are fake — toast only, nothing stored | NewsletterSubscriber model + Livewire form + admin list |
| M4 | Coached page has no enquiry path with coaching context (just a link to generic contact) | Coaching enquiry form on /coached with discipline/experience fields feeding the existing enquiry inbox |
| M5 | Gift vouchers exist in admin with zero public mention | "Gift a jump" section on the tandem page (CMS-driven copy) pointing at contact |
| M6 | Payment success/cancelled pages are generic; no booking context | Success page resolves the Checkout session → shows booking reference + what-happens-next; cancelled page links back into the flow |
| M7 | 404 page is unbranded (no header/footer, plain text) | Branded 404 using the site layout, hero treatment and useful links |
| M8 | Course communications/documents (Round 2 Part C6) | Deliberately deferred unless time allows — recorded in SUMMARY.md |
| FAQs | No FAQ content exists in the design OR the backend | Not a mismatch — both sides empty; out of scope (logged R1) |

## Visual problems by page (from before/ screenshots)

**Global**
- G1: `page-hero` highlight word (light blue) sits on the light top of the gradient —
  poor contrast on every subpage (tandem "SKYDIVE", contact "US", payment "COMPLETE").
- G2: Subpage heroes are flat gradient bands — no photography anywhere except home.
  For a skydiving brand the interior pages feel like a utility site.
- G3: Hero CTAs are small at 1440px (lost in the hero); primary action doesn't dominate.
- G4: Hero eyebrow text (light blue, tracking-wide) low contrast on sky imagery.
- G5: Card/border-radius/shadow language is broadly consistent (good) — keep.

**Home** — strongest page. G3/G4 apply. Team cards are initial-letter circles (no
photos seeded) — acceptable. Newsletter form fake (M3).

**Tandem** — G1/G2; pricing tables clean; pay-card → M1; add voucher mention (M5).

**AFF** — G1/G2; pay-card → M2; no course dates (M2); investment cards fine.

**Coached** — G1/G2; single section page, no enquiry (M4).

**Contact** — hero band too tall on mobile relative to content; otherwise solid.

**Payment success/cancelled** — sparse, floating check icon, huge empty hero (M6).

**404** — unbranded (M7).

**Booking flows** — do not exist yet; designed mobile-first from scratch (M1/M2).
