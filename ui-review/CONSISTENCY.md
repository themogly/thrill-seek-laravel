# Itemised cross-page consistency audit

Prompt `prompts/003-itemised-consistency-audit.md`: `ui-passes/04-guidelines.md` Step 1b only. Report-only.
Branch `docs/consistency-audit` off `main` = `e7b0921`. That's after the admin (`6f9888f`) and accessibility
(`d5863c1`) audits merged, so this is the real merged state.

## Method

1. **Enumerated from the source first.** I grepped every component tag (`<x-site.section-heading>` ×13,
   `<x-site.section>` ×33, `<x-site.page-hero>` ×30, `<x-ui.button>` ×57, `<x-ui.arrow-link>` ×6,
   `<x-ui.feature-list>` ×3, `<x-site.faq-section>` ×3, `<x-site.instructor-card>` ×4, …) and every
   hand-rolled lookalike: `<h2>`s outside the heading component, inline `tracking-[0.2x em]` eyebrows,
   arrow icons and `→` glyphs, `bg-primary` on non-components, inline check-lists, `<section>` padding,
   `<img>` ratios, and every `£`/price accessor.
2. **Then rendered all of it.** Assets were rebuilt, with reduced motion emulated so reveals don't hide
   anything, and the browser signed out. A script walked all 21 public pages at **1440 and 390** and
   recorded every instance of each element type with its *computed* attributes: font, size, tracking,
   colour, borders, padding, rendered aspect ratio, and whether it came from the shared component. The
   6 signed-in account pages were checked with a customer session.
3. **Looked.** Element crops of every flagged item and its reference, at both widths, are in
   `ui-review/consistency-audit/` (28 JPEGs, ~0.5 MB). The full per-instance tables are at the end of
   this file.

**The homepage is the reference, not a suspect.** Where it differs from the majority it's recorded and
presumed right ("homepage = signed-off reference"), and nothing below proposes a homepage change.

**Reading the tables:** the "same signature as the most common?" column is a raw, mechanical diff
against the most common signature *for that element type across the whole site*. It mixes roles: the
most common "eyebrow" is the 12px trust/role label, not the 14px section eyebrow. Judgement by role is
in the verdicts below. A `≠` in the raw table is not, by itself, a finding.

## Verdicts by element type

| Element type | Instances (1440+390) | Verdict |
|---|---|---|
| Section headings | 35 rows | ✅ The light-section standard (eyebrow + line, `heading-rule`, navy, `text-h2`) is consistent wherever `<x-site.section-heading>` is used. Dark feature bands and CTA bands match the homepage's own treatments. **Odd:** C-1 (coached hand copy), C-2 (AFF trust band inline), C-9 (account/panel heading sizes and colour). **No heading carries an icon** (the June newspaper-icon class is gone). |
| Eyebrows & labels | 53 rows | Section eyebrows (14px/700/0.25em, primary with a line on light, sky-bright on dark) are consistent. Card and role labels (12px/700/0.25em, sky-bright) are consistent. **Odd:** C-3 (news dates 0.20em, news label 0.30em), C-4 (testimonial-grid roles), C-5 (meta labels at 0.20em). |
| Buttons & CTAs | 29 rows | ✅ **Every button renders through `<x-ui.button>`, 0 hand-rolled.** Primary lg/default, outline (adapts navy/white) and link variants are each consistent. Booking CTAs match across Tandem ("Choose a date & book") and AFF ("Choose a course & pay deposit") pay-cards. Coached is enquiry-only by design. **Odd:** C-1 (coached-only intro CTA), C-11 (typed `←` back links), C-12 ("Sending..." vs "Sending…"). |
| Arrow / explore links | 9 rows | ✅ "Meet the team" (tandem/aff/coached), "All news" and the "Read more"/"Explore" cues all come from `<x-ui.arrow-link>` (link or span cue). The homepage "Follow us" social links are hand-rolled: homepage = signed-off reference, already in ui-guidelines' known gaps. |
| Feature / check lists | 7 rows | **Odd:** C-6 (three check-list treatments). `<x-ui.feature-list>` itself is identical on all three product pages. |
| Cards | 29 rows | ✅ Sharp corners and no shadows everywhere. Pay-cards (4px primary top rule, navy) are consistent on Tandem, AFF, Contact, the news course card and Vouchers. Testimonial tiles, instructor cards, FAQ rows, news cards and price cards are each consistent. **Odd:** C-10 (newsletter signup panel framed two ways). |
| Section transitions | 63 rows | ✅ **No section-divider lines anywhere.** The only rules are the 4px primary rules on heroes and dark feature bands, which the rules allow. |
| Section spacing rhythm | (same rows) | ✅ Standard sections are 96/64 (`py-section`/`-sm`); dramatic bands 128/96; heroes 96/64; Hall of Fame compact 64/48 by design. **Odd:** C-13 (two intro wrappers hand-roll `py-16 lg:py-24`, same values, not the tokens). |
| Imagery | 38 rows | ✅ All `object-cover`. Instructor portraits are 1:1 everywhere (product strips and Meet the Team); Hall of Fame 3:4; product cards 1:1; news 16:10. **Odd:** C-8 (feature-split images 16:10 on mobile but ~1.42:1 on desktop, while the admin crops 16:10). The testimonial-photo crop mismatch is already follow-up A-2. |
| Page heroes | 21 rows | ✅ Secondary pages use the compact navy gradient (no photo, 4px rule, `text-h1`); Tandem/AFF/Coached use the photographic CMS hero with scrim; Hall of Fame the compact photo hero; Testimonials the featured-quote hero. All match the rules. The homepage hero has no bottom rule: homepage = signed-off reference. |
| Price displays | 33 rows | Product-driven prices all go through the `Money` presenter (`formatted_price`, `summary_price_label`, `formatted_deposit`, `formatted_amount`) and format consistently: whole pounds `£260`, pence only when present `£24.73`. **Odd:** C-7 (prices typed into CMS copy), C-15 (shop `price_label` is free text). |
| FAQ accordions | 19 rows | ✅ Identical on Tandem, AFF and Coached. Every question is a button with `aria-expanded`; the last item has no rule by design. |
| Discipline chips | 3 rows (Meet the Team) | ✅ Consistent (2px primary border, navy text). They appear only on Meet the Team; the product-page strips deliberately show no chips (`discipline-instructors` docblock). |
| "Meet your team" strips | Tandem / AFF / Coached | ✅ The same `<x-site.discipline-instructors>`, 1:1 portraits and the "Meet the team →" arrow-link on all three. |

## ⚠️ Odd ones out

Each one is scoped as its own small branch. Ben numbers them into `RUNNING-ORDER.md`.

- **C-1 · Coached intro heading is a hand copy of `<x-site.section-heading>`.**
  - Where: `resources/views/pages/coached.blade.php:14-18`.
  - Differs: it renders the same as Tandem/AFF's intro (`tandem.blade.php` via the component), but the
    markup is duplicated. Its eyebrow is a **price** ("From £60 per session", `CoachedPageSettings
    price_eyebrow`) where the other intros use a category label ("The jump", "The course"). It's also the
    only product intro with its own CTA (`coached.blade.php:23`, "Book a session" → `#enquiry`).
  - Follow-up: render it through `<x-site.section-heading>`, and ask the owner whether the price eyebrow
    and the intro CTA are intended (if they are, give Tandem/AFF the same, or move the price into the
    lead).
- **C-2 · AFF "You're in safe hands" trust band builds its heading inline.**
  - Where: `aff.blade.php:36-40`.
  - Differs: eyebrow + line, **no `heading-rule`**, white. It matches the homepage trust band
    (`home.blade.php:115-119`, homepage = signed-off reference), but both are inline copies.
  - Follow-up: a `rule` option on `<x-site.section-heading>` (or one trust-band component) used by AFF.
    The homepage is untouched.
- **C-3 · /news index labels drift from the homepage's news cards.**
  - Where: `news/index.blade.php:29` and `news/index.blade.php:24`.
  - Differs: article dates are `tracking-[0.2em]` where the homepage's cards are `[0.25em]`
    (`home.blade.php:136`, homepage = signed-off reference). The image label "G-Force News" is
    `tracking-[0.3em]`, the only 0.30em label on the site.
  - Follow-up: one shared news-card date/label style (0.25em) used by `/news`.
- **C-4 · Testimonial-grid role labels.**
  - Where: `testimonials.blade.php:83`.
  - Differs: weight 400, `0.2em`, `white/70`. Every other role label (featured testimonial `:45`,
    `instructor-card.blade.php:28`) is 700, `0.25em`, sky-bright.
  - Follow-up: use the shared role-label style in the photo-tile caption.
- **C-5 · Small meta labels at `0.2em`.**
  - Where: news byline (`news/show.blade.php:37`) and AFF course duration (`aff.blade.php:89`).
  - Differs: the site's label standard is `0.25em`. ui-guidelines calls `[0.2em]` intentional for *stat*
    labels, but these are meta labels.
  - Follow-up: decide one meta-label tracking and record it in ui-guidelines. Low.
- **C-6 · Three check-list treatments.**
  - `<x-ui.feature-list>` (20px icon, 12px gap, top-aligned, primary) on the three product pages.
  - The Tandem pay-card list is inline (`tandem.blade.php:104-106`: 16px, sky-bright, centred, 8px gap).
  - `<x-site.price-card>` features (`price-card.blade.php:7`: 16px, primary, centred).
  - The AFF pay-card has no reassurance list, while Tandem's does.
  - Follow-up: a compact `size`/`tone` on `<x-ui.feature-list>`, used by the pay-card and price-card.
    Decide whether the AFF pay-card gets the same three bullets.
- **C-7 · Prices typed into CMS copy, so they drift from the product.** The Money-driven prices are
  consistent, but these are hand-typed:
  - Tandem FAQ answers: weight bands £20/£40/£60, camera £140/£100, rebooking £50, sponsorship £260
    (`FaqSeeder`).
  - AFF FAQ: £1,750/£600, £125 membership, £5 kit.
  - The Tandem hero subtitle "from £260" (`TandemPageSettings hero_subtitle`).
  - The Coached "From £60 per session" eyebrow.
  - AFF repeat-jump "£210/£140 per jump" (price-card feature text).
  - Terms (£24.73, £50).

  Change a product price and these keep the old one. Follow-up: price placeholders in CMS text (e.g.
  `{{ price:tandem }}` rendered via `Money`), or at least an owner warning in the Help guide. Includes
  owner content.
- **C-8 · Feature-split images change ratio at desktop.**
  - Where: `components/site/feature-split.blade.php:21`.
  - Differs: `aspect-[16/10]` on mobile, but a fixed `md:h-[24rem] lg:h-[30rem]` on desktop (~1.42:1 at
    1440), while the admin crops these uploads to 16:10. Desktop crops the sides of the owner's chosen
    framing.
  - Follow-up: keep 16:10 at every width, or crop to the desktop frame. Same class as A-2.
- **C-9 · Panel / account section headings.**
  - Account headings are **ink** 24px (`account/dashboard.blade.php:15…`, `bookings/*`). "Send a reply"
    is 20px (`account/messages/show.blade.php:36`). "Sign in" is 30px ink (`account/login.blade.php:23`).
  - The public form-panel headings are **navy** 24px (`contact-form.blade.php:8`,
    `newsletter.blade.php:16`).
  - Follow-up: one panel-heading treatment (navy, `text-2xl`) shared by the account and public panels.
- **C-10 · Newsletter signup panel framed two ways.**
  - Where: `contact.blade.php:28` (`border-border`) and `newsletter.blade.php:15` (`border-secondary`,
    navy). Both wrap the same `<livewire:newsletter-signup variant="card">`.
  - Follow-up: one signup-panel frame.
- **C-11 · Back links type a `←` into a link button.**
  - Where: `news/show.blade.php:70`, `account/bookings/show.blade.php:94`,
    `account/messages/show.blade.php:48`.
  - Consistent with each other, but there's no shared back control; `<x-ui.arrow-link>` only points
    forward.
  - Follow-up: a `back` direction on `<x-ui.arrow-link>`. Low.
- **C-12 · Loading label.**
  - "Sending..." (three dots) in `tandem-enquiry-form.blade.php:37`, `contact-form.blade.php:22` and
    `aff-enquiry-form.blade.php:21`, against "Sending…" (ellipsis) in
    `coached-enquiry-form.blade.php:51`.
  - Follow-up: standardise on "Sending…". Trivial.
- **C-13 · Hand-rolled section padding.**
  - Where: `tandem.blade.php:21` and `aff.blade.php:25`, `py-16 lg:py-24`.
  - The values equal `py-section-sm lg:py-section`, but they aren't the tokens, so a token change would
    miss them.
  - Follow-up: swap to the tokens (no visual change).
- **C-14 · (record only) Homepage "Follow us" social links.** Hand-rolled arrow links
  (`home.blade.php:159,166`). Homepage = signed-off reference, already listed in ui-guidelines. No
  change proposed.
- **C-15 · Shop prices are free text.**
  - Where: `ShopItem::price_label`, rendered at `shop.blade.php:22`.
  - It bypasses the Money presenter; no items render today.
  - Follow-up: structured `price_pence` + `Money`, with `price_label` kept only as an optional note.

**Count:** 15 items. 14 are actionable; C-14 is record-only.

## Per-instance tables (evidence)

Computed on the real pages, signed out, reduced motion, rebuilt assets, at 1440 and 390. "where" = the
index of the `<main>` child section.

### Section headings (35 instances)

Most common raw signature: `left · navy · eyebrow yes+line · rule yes · icon no · section-heading`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s1 | THREE WAYS TO FLY | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| / | s2 | ESTABLISHED 2017. BUILT ON EXPERIE | 68px / 36px | left · white · eyebrow yes+line · rule yes · icon no · inline | ≠ |
| / | s3 | TRUSTED. CERTIFIED. EXPERIENCED. | 68px / 36px | left · white · eyebrow yes+line · rule no · icon no · inline | ≠ |
| / | s4 | LATEST NEWS | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| / | s4 | FOLLOW US | 30px / 24px | left · white · eyebrow no · rule no · icon no · inline | ≠ |
| / | s5 | VOICES FROM THE SKY | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| / | s6 | STAY IN THE LOOP | 68px / 36px | centre · white · eyebrow no · rule no · icon no · inline | ≠ |
| / | s7 | READY TO JUMP? | 88px / 44px | centre · white · eyebrow no · rule no · icon no · inline | ≠ |
| /tandem | s1 | 15,000FT OF PURE ADRENALINE | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /tandem | s2 | WHAT IT COSTS | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /tandem | s3 | YOUR TANDEM INSTRUCTORS | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /tandem | s4 | FREQUENTLY ASKED QUESTIONS | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /tandem | s6 | GIVE THE JUMP OF A LIFETIME | 68px / 36px | centre · white · eyebrow no · rule no · icon no · inline | ≠ |
| /aff | s1 | FROM YOUR FIRST JUMP TO A LICENCE | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /aff | s2 | YOU'RE IN SAFE HANDS | 68px / 36px | left · white · eyebrow yes+line · rule no · icon no · inline | ≠ |
| /aff | s3 | INVESTMENT | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /aff | s4 | PICK YOUR WEEK IN THE SUN | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /aff | s5 | TRAIN IN THE SUN | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /aff | s6 | YOUR AFF INSTRUCTORS | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /aff | s7 | FREQUENTLY ASKED QUESTIONS | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /coached | s1 | FLY BETTER. FLY SMARTER. | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · inline | ≠ |
| /coached | s2 | YOUR COACHES | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /coached | s3 | FREQUENTLY ASKED QUESTIONS | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /coached | s4 | TELL US WHERE YOU’RE AT | 68px / 36px | left · navy · eyebrow yes+line · rule yes · icon no · section-heading | ✓ |
| /book/tandem | s1 | NO DATES ONLINE RIGHT NOW | 24px / 24px | centre · navy · eyebrow no · rule no · icon no · inline | ≠ |
| /book/aff | s1 | NEW COURSE DATES COMING SOON | 24px / 24px | centre · navy · eyebrow no · rule no · icon no · inline | ≠ |
| /contact | s1 | SEND A MESSAGE | 24px / 24px | left · navy · eyebrow no · rule no · icon no · inline | ≠ |
| /meet-the-team | s1 | JOBY CHADD | 30px / 30px | left · white · eyebrow no · rule no · icon no · inline | ≠ |
| /meet-the-team | s1 | RICKY | 30px / 30px | left · white · eyebrow no · rule no · icon no · inline | ≠ |
| /meet-the-team | s1 | LUCY DAVIES | 30px / 30px | left · white · eyebrow no · rule no · icon no · inline | ≠ |
| /news | s1 | NEW AFF COURSE IN SEVILLE | 30px / 24px | left · navy · eyebrow yes · rule no · icon no · inline | ≠ |
| /news | s1 | WELCOME TO THE 2026 SEASON | 30px / 24px | left · navy · eyebrow yes · rule no · icon no · inline | ≠ |
| /news/new-aff-course-in-seville | s1 | 20–24 JULY 2026 | 30px / 30px | left · white · eyebrow yes · rule no · icon no · inline | ≠ |
| /newsletter | s1 | JOIN THE LIST | 24px / 24px | left · navy · eyebrow no · rule no · icon no · inline | ≠ |
| /account/login | s1 | SIGN IN | 30px / 30px | left · ink · eyebrow no · rule no · icon no · inline | ≠ |

### Eyebrows & labels (53 instances)

Most common raw signature: `w700 · 0.25em · sky-bright · line no`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s0 | G-FORCE SKYDIVING | 14px / 14px | w700 · 0.40em · sky-bright · line yes | ≠ |
| / | s1 | WHAT WE DO | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| / | s2 | OUR STORY | 14px / 14px | w700 · 0.25em · sky-bright · line yes | ≠ |
| / | s3 | WHY JUMP WITH US | 14px / 14px | w700 · 0.25em · sky-bright · line yes | ≠ |
| / | s3 | INSTRUCTOR BACKGROUNDS | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| / | s3 | COMBINED EXPERIENCE | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| / | s3 | CERTIFIED INSTRUCTORS | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| / | s3 | PROVEN TRACK RECORD | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| / | s4 | FROM THE DROPZONE | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| / | s4 | 15 JUN 2026 | 12px / 12px | w700 · 0.25em · primary · line no | ≠ |
| / | s4 | 11 JUN 2026 | 12px / 12px | w700 · 0.25em · primary · line no | ≠ |
| / | s4 | RECENT JUMPS | 12px / 12px | w700 · 0.25em · white/50 · line no | ≠ |
| / | s5 | REAL REVIEWS | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /tandem | s1 | THE JUMP | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /tandem | s2 | TRANSPARENT PRICING | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /tandem | s3 | THE TEAM | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /tandem | s3 | CHIEF INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /tandem | s3 | TANDEM INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /tandem | s4 | GOOD TO KNOW | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /tandem | s5 | BOOK ONLINE | 14px / 14px | w700 · 0.25em · sky-bright · line no | ✓ |
| /aff | s1 | THE COURSE | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /aff | s2 | TRAIN WITH CONFIDENCE | 14px / 14px | w700 · 0.25em · sky-bright · line yes | ≠ |
| /aff | s2 | INSTRUCTOR BACKGROUNDS | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /aff | s2 | COMBINED EXPERIENCE | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /aff | s2 | CERTIFIED INSTRUCTORS | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /aff | s2 | PROVEN TRACK RECORD | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /aff | s3 | PRICING | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /aff | s4 | UPCOMING COURSES | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /aff | s5 | WHERE & WHEN | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /aff | s6 | THE TEAM | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /aff | s6 | CHIEF INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /aff | s6 | AFF INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /aff | s7 | GOOD TO KNOW | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /aff | s8 | RESERVE YOUR SPOT | 14px / 14px | w700 · 0.25em · sky-bright · line no | ✓ |
| /coached | s1 | FROM £60 PER SESSION | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /coached | s2 | THE TEAM | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /coached | s2 | CHIEF INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /coached | s2 | AFF INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /coached | s3 | GOOD TO KNOW | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /coached | s4 | GET COACHED | 14px / 14px | w700 · 0.25em · primary · line yes | ≠ |
| /meet-the-team | s1 | CHIEF INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /meet-the-team | s1 | AFF INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /meet-the-team | s1 | TANDEM INSTRUCTOR | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /news | s1 | G-FORCE NEWS | 12px / 12px | w700 · 0.30em · white · line no | ≠ |
| /news | s1 | 15 JUN 2026 | 12px / 12px | w700 · 0.20em · primary · line no | ≠ |
| /news | s1 | 11 JUN 2026 | 12px / 12px | w700 · 0.20em · primary · line no | ≠ |
| /news/new-aff-course-in-seville | s1 | 15 JUNE 2026 · BY THE G-FORCE TEAM | 12px / 12px | w700 · 0.20em · muted · line no | ≠ |
| /news/new-aff-course-in-seville | s1 | LINKED AFF COURSE | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /testimonials | s0 | TESTIMONIALS | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /testimonials | s0 | TANDEM JUMPER | 12px / 12px | w700 · 0.25em · sky-bright · line no | ✓ |
| /testimonials | s1 | AFF GRADUATE | 12px / 12px | w400 · 0.20em · white/70 · line no | ≠ |
| /testimonials | s1 | TANDEM JUMPER | 12px / 12px | w400 · 0.20em · white/70 · line no | ≠ |
| /testimonials | s1 | COACHED SKILLS | 12px / 12px | w400 · 0.20em · white/70 · line no | ≠ |

### Buttons & CTAs (29 instances)

Most common raw signature: `x-ui.button · primary · arrow no`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s0 | BOOK A TANDEM | 56px / 56px | x-ui.button · primary · arrow yes | ≠ |
| / | s0 | LEARN TO SKYDIVE | 56px / 56px | x-ui.button · outline white · arrow no | ≠ |
| / | s6 | SUBSCRIBE | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| / | s7 | CONTACT US | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /tandem | s5 | CHOOSE A DATE & BOOK | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /tandem | s5 | SEND ENQUIRY | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /tandem | s6 | ASK ABOUT GIFT VOUCHERS | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /aff | s4 | GET IN TOUCH | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /aff | s8 | CHOOSE A COURSE & PAY DEPOSIT | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /aff | s8 | SEND ENQUIRY | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /coached | s1 | BOOK A SESSION | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /coached | s4 | GET A COACHING PLAN | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /book/tandem | s1 | GET IN TOUCH | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /book/aff | s1 | ASK ABOUT THE NEXT COURSE | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /contact | s1 | SEND MESSAGE | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /contact | s1 | SUBSCRIBE | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /news/new-aff-course-in-seville | s1 | SEE ALL COURSES | 56px / 56px | x-ui.button · outline white · arrow no | ≠ |
| /news/new-aff-course-in-seville | s1 | ← ALL NEWS | 20px / 20px | x-ui.button · link primary · arrow no | ≠ |
| /newsletter | s1 | SUBSCRIBE | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /payment/cancelled | s1 | CONTACT US | 56px / 56px | x-ui.button · outline ink · arrow no | ≠ |
| /payment/success | s1 | BACK TO THE SITE | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /shop | s1 | BOOK A TANDEM | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /shop | s1 | AFF COURSES | 44px / 44px | x-ui.button · outline ink · arrow no | ≠ |
| /shop | s1 | CONTACT US | 44px / 44px | x-ui.button · outline ink · arrow no | ≠ |
| /vouchers | s1 | BUY FOR £260 — DELIVERED BY EMAIL | 56px / 56px | x-ui.button · primary · arrow no | ✓ |
| /account/login | s1 | EMAIL ME A SIGN-IN LINK | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /this-page-does-not-exist | s1 | BOOK A TANDEM | 44px / 44px | x-ui.button · primary · arrow no | ✓ |
| /this-page-does-not-exist | s1 | AFF COURSES | 44px / 44px | x-ui.button · outline ink · arrow no | ≠ |
| /this-page-does-not-exist | s1 | CONTACT US | 44px / 44px | x-ui.button · outline ink · arrow no | ≠ |

### Arrow / explore links (9 instances)

Most common raw signature: `x-ui.arrow-link · primary`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s1 | EXPLORE | 14px / 14px | x-ui.arrow-link (span cue) · white | ≠ |
| / | s2 | MEET THE TEAM | 14px / 14px | x-ui.arrow-link (span cue) · white | ≠ |
| / | s4 | ALL NEWS | 14px / 14px | x-ui.arrow-link · primary | ✓ |
| / | s4 | @GFORCESKYDIVING | 16px / 16px | HAND-ROLLED · white | ≠ |
| / | s4 | FACEBOOK | 16px / 16px | HAND-ROLLED · white | ≠ |
| /tandem | s3 | MEET THE TEAM | 14px / 14px | x-ui.arrow-link · primary | ✓ |
| /aff | s6 | MEET THE TEAM | 14px / 14px | x-ui.arrow-link · primary | ✓ |
| /coached | s2 | MEET THE TEAM | 14px / 14px | x-ui.arrow-link · primary | ✓ |
| /news | s1 | READ MORE | 14px / 14px | x-ui.arrow-link (span cue) · primary | ≠ |

### Cards (29 instances)

Most common raw signature: `top 2px primary · left 0px · bg none · radius 0px · shadow no`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s4 | 15 JUN 2026 NEW AFF COURSE IN SEVI |  / — | top 2px border · left 2px · bg none · radius 0px · shadow no | ≠ |
| / | s4 | FOLLOW US Follow @gforceskydiving  |  / — | top 0px primary · left 4px · bg oklch(0.12 0.03 250) · radius 0px · shadow no | ≠ |
| /tandem | s4 | IS IT SAFE? DO I NEED ANY EXPERIEN |  / — | top 2px border · left 0px · bg none · radius 0px · shadow no | ≠ |
| /tandem | s5 | BOOK ONLINE BOOK YOUR JUMP NOW Pic |  / — | top 4px primary · left 0px · bg navy · radius 0px · shadow no | ≠ |
| /aff | s3 | AFF COURSE LEVELS 1–8 £1,750 All e |  / — | top 2px primary · left 2px · bg none · radius 0px · shadow no | ≠ |
| /aff | s3 | CONSOLIDATION JUMPS £600 10 jumps  |  / — | top 2px border · left 2px · bg none · radius 0px · shadow no | ≠ |
| /aff | s4 | New course dates are being finalis |  / — | top 2px navy · left 2px · bg none · radius 0px · shadow no | ≠ |
| /aff | s7 | HOW LONG DOES THE AFF COURSE TAKE? |  / — | top 2px border · left 0px · bg none · radius 0px · shadow no | ≠ |
| /aff | s8 | RESERVE YOUR SPOT SECURE YOUR PLAC |  / — | top 4px primary · left 0px · bg navy · radius 0px · shadow no | ≠ |
| /coached | s3 | WHO IS COACHING FOR? Coaching is f |  / — | top 2px border · left 0px · bg none · radius 0px · shadow no | ≠ |
| /book/tandem | s1 | NO DATES ONLINE RIGHT NOW We add j |  / — | top 2px navy · left 2px · bg oklch(1 0 0) · radius 0px · shadow no | ≠ |
| /book/aff | s1 | NEW COURSE DATES COMING SOON Send  |  / — | top 2px navy · left 2px · bg oklch(1 0 0) · radius 0px · shadow no | ≠ |
| /contact | s1 | DIRECT CONTACT +44 (0)7583 155 951 |  / — | top 4px primary · left 0px · bg navy · radius 0px · shadow no | ≠ |
| /contact | s1 | NEWSLETTER Course dates and offers |  / — | top 2px border · left 2px · bg oklch(1 0 0) · radius 0px · shadow no | ≠ |
| /meet-the-team | s1 | TANDEM AFF COACHING Started skydiv |  / — | top 0px border · left 2px · bg none · radius 0px · shadow no | ≠ |
| /meet-the-team | s1 | AFF COACHING Specialist in coachin |  / — | top 0px border · left 2px · bg none · radius 0px · shadow no | ≠ |
| /meet-the-team | s1 | TANDEM Joined G-Force in Portugal  |  / — | top 0px border · left 2px · bg none · radius 0px · shadow no | ≠ |
| /news | s1 | G-FORCE NEWS 15 JUN 2026 NEW AFF C |  / — | top 2px border · left 2px · bg oklch(1 0 0) · radius 0px · shadow no | ≠ |
| /news | s1 | G-FORCE NEWS 11 JUN 2026 WELCOME T |  / — | top 2px border · left 2px · bg oklch(1 0 0) · radius 0px · shadow no | ≠ |
| /news/new-aff-course-in-seville | s1 | LINKED AFF COURSE 20–24 JULY 2026  |  / — | top 4px primary · left 0px · bg navy · radius 0px · shadow no | ≠ |
| /newsletter | s1 | JOIN THE LIST Pop your email in an |  / — | top 2px navy · left 2px · bg oklch(1 0 0) · radius 0px · shadow no | ≠ |
| /testimonials | s1 | DID MY AFF WITH G-FORCE IN SPAIN.  |  / — | top 2px primary · left 0px · bg none · radius 0px · shadow no | ✓ |
| /testimonials | s1 | TANDEM FROM 15,000FT. THE VIEW, TH |  / — | top 2px primary · left 0px · bg none · radius 0px · shadow no | ✓ |
| /testimonials | s1 | JOBY'S 1-TO-1 COACHING TOOK MY FRE |  / — | top 2px primary · left 0px · bg none · radius 0px · shadow no | ✓ |
| /testimonials | s1 | DID A CHARITY TANDEM AND RAISED OV |  / — | top 2px primary · left 0px · bg none · radius 0px · shadow no | ✓ |
| /testimonials | s1 | PROFESSIONAL, FRIENDLY, SAFETY-FIR |  / — | top 2px primary · left 0px · bg none · radius 0px · shadow no | ✓ |
| /testimonials | s1 | THE HANDCAM FOOTAGE IS AMAZING. WA |  / — | top 2px primary · left 0px · bg none · radius 0px · shadow no | ✓ |
| /testimonials | s1 | LUCY IS AN INCREDIBLE COACH. CLEAR |  / — | top 2px primary · left 0px · bg none · radius 0px · shadow no | ✓ |
| /vouchers | s1 | THE GIFT TANDEM SKYDIVE Valid 12 m |  /  | top 4px primary · left 0px · bg navy · radius 0px · shadow no | ≠ |

### Sections (rhythm & transitions) (63 instances)

Most common raw signature: `border top 0px · bottom 0px · bg none`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s0 | ONE LIFE. ONE ADVENTURE. LIVE IT. | 0px/0px (inner 0px) / 0px/0px (inner 0px) | border top 0px · bottom 0px · bg none | ✓ |
| / | s1 | THREE WAYS TO FLY | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| / | s2 | ESTABLISHED 2017. BUILT ON EXPERIE | 0px/0px (inner 128px) / 0px/0px (inner 96px) | border top 0px · bottom 0px · bg oklch(0.12 0.03 250) | ≠ |
| / | s3 | TRUSTED. CERTIFIED. EXPERIENCED. | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg navy | ≠ |
| / | s4 | LATEST NEWS | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| / | s5 | VOICES FROM THE SKY | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| / | s6 | STAY IN THE LOOP | 128px/128px / 96px/96px | border top 4px · bottom 4px · bg oklch(0.12 0.03 250) | ≠ |
| / | s7 | READY TO JUMP? | 128px/128px / 96px/96px | border top 0px · bottom 0px · bg none | ✓ |
| /tandem | s0 | TANDEM SKYDIVE | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg oklch(0.12 0.03 250) | ≠ |
| /tandem | s1 | 15,000FT OF PURE ADRENALINE | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| /tandem | s2 | WHAT IT COSTS | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| /tandem | s3 | YOUR TANDEM INSTRUCTORS | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /tandem | s4 | FREQUENTLY ASKED QUESTIONS | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /tandem | s5 | BOOK ONLINE BOOK YOUR JUMP NOW Pic | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /tandem | s6 | GIVE THE JUMP OF A LIFETIME | 128px/128px / 96px/96px | border top 4px · bottom 4px · bg oklch(0.12 0.03 250) | ≠ |
| /aff | s0 | ACCELERATED FREEFALL | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg oklch(0.12 0.03 250) | ≠ |
| /aff | s1 | FROM YOUR FIRST JUMP TO A LICENCE | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| /aff | s2 | YOU'RE IN SAFE HANDS | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg navy | ≠ |
| /aff | s3 | INVESTMENT | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| /aff | s4 | PICK YOUR WEEK IN THE SUN | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /aff | s5 | TRAIN IN THE SUN | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| /aff | s6 | YOUR AFF INSTRUCTORS | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /aff | s7 | FREQUENTLY ASKED QUESTIONS | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /aff | s8 | RESERVE YOUR SPOT SECURE YOUR PLAC | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /coached | s0 | COACHED SKILLS | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg oklch(0.12 0.03 250) | ≠ |
| /coached | s1 | FLY BETTER. FLY SMARTER. | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| /coached | s2 | YOUR COACHES | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /coached | s3 | FREQUENTLY ASKED QUESTIONS | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /coached | s4 | TELL US WHERE YOU’RE AT | 0px/0px (inner 96px) / 0px/0px (inner 64px) | border top 0px · bottom 0px · bg none | ✓ |
| /book/tandem | s0 | BOOK YOUR JUMP | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /book/tandem | s1 | NO DATES ONLINE RIGHT NOW | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /book/aff | s0 | RESERVE YOUR COURSE | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /book/aff | s1 | NEW COURSE DATES COMING SOON | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /contact | s0 | CONTACT US | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /contact | s1 | SEND A MESSAGE | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /hall-of-fame | s0 | HALL OF FAME | 64px/64px / 48px/48px | border top 0px · bottom 4px · bg oklch(0.12 0.03 250) | ≠ |
| /hall-of-fame | s1 | JAMES CARTER A Licence — Spain 202 | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /meet-the-team | s0 | MEET THE TEAM | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /meet-the-team | s1 | JOBY CHADD | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /news | s0 | LATEST NEWS | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /news | s1 | NEW AFF COURSE IN SEVILLE | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /news/new-aff-course-in-seville | s0 | NEW AFF COURSE IN SEVILLE | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /news/new-aff-course-in-seville | s1 | 20–24 JULY 2026 | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /newsletter | s0 | STAY IN THE LOOP | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /newsletter | s1 | JOIN THE LIST | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /payment/cancelled | s0 | PAYMENT CANCELLED | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /payment/cancelled | s1 | Changed your mind or hit a problem | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /payment/success | s0 | PAYMENT COMPLETE | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /payment/success | s1 | We'll be in touch shortly to arran | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /privacy | s0 | PRIVACY POLICY | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /privacy | s1 | Last updated: 9 October 2026 G-For | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /shop | s0 | LOST IN FREEFALL | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /shop | s1 | 404 Here's where most people want  | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /terms | s0 | TERMS & CONDITIONS | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /terms | s1 | By booking with G-Force Skydiving  | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /testimonials | s0 | TESTIMONIALS | 0px/0px (inner 0px) / 0px/0px (inner 0px) | border top 0px · bottom 4px · bg navy | ≠ |
| /testimonials | s1 | DID MY AFF WITH G-FORCE IN SPAIN.  | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /vouchers | s0 | GIVE THE JUMP | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /vouchers | s1 | Vouchers are valid for 12 months,  | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /account/login | s0 | MY ACCOUNT | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /account/login | s1 | SIGN IN | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |
| /this-page-does-not-exist | s0 | LOST IN FREEFALL | 96px/96px / 64px/64px | border top 0px · bottom 4px · bg gradient | ≠ |
| /this-page-does-not-exist | s1 | 404 Here's where most people want  | 96px/96px / 64px/64px | border top 0px · bottom 0px · bg none | ✓ |

### Imagery (38 instances)

Most common raw signature: `fit cover`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s0 | Skydivers in freefall above  | 1.69 / 0.55 | fit cover | ✓ |
| / | s1 | Tandem Skydive | 1.00 / 1.00 | fit cover | ✓ |
| / | s1 | AFF Course Levels 1–8 | 1.00 / 1.00 | fit cover | ✓ |
| / | s1 | Coached Skills | 1.00 / 1.00 | fit cover | ✓ |
| / | s2 | (decorative) | 0.75 / 0.75 | fit cover | ✓ |
| / | s4 | Instagram post | 1.00 / 1.00 | fit cover | ✓ |
| / | s5 | Sarah M. | 1.00 / 1.00 | fit cover | ✓ |
| / | s5 | Tom R. | 1.00 / 1.00 | fit cover | ✓ |
| / | s7 | (decorative) | 2.98 / 0.95 | fit cover | ✓ |
| /tandem | s0 | (decorative) | 3.97 / 1.50 | fit cover | ✓ |
| /tandem | s1 | Tandem skydive | 1.42 / 1.60 | fit cover | ✓ |
| /tandem | s3 | Joby Chadd | 1.00 / 1.00 | fit cover | ✓ |
| /tandem | s3 | Lucy Davies | 1.00 / 1.00 | fit cover | ✓ |
| /aff | s0 | (decorative) | 3.97 / 1.27 | fit cover | ✓ |
| /aff | s1 | AFF training | 1.42 / 1.60 | fit cover | ✓ |
| /aff | s6 | Joby Chadd | 1.00 / 1.00 | fit cover | ✓ |
| /aff | s6 | Ricky | 1.00 / 1.00 | fit cover | ✓ |
| /coached | s0 | (decorative) | 3.97 / 1.50 | fit cover | ✓ |
| /coached | s1 | Advanced freefly coaching | 1.42 / 1.60 | fit cover | ✓ |
| /coached | s2 | Joby Chadd | 1.00 / 1.00 | fit cover | ✓ |
| /coached | s2 | Ricky | 1.00 / 1.00 | fit cover | ✓ |
| /hall-of-fame | s0 | (decorative) | 5.47 / 1.76 | fit cover | ✓ |
| /hall-of-fame | s1 | James Carter | 0.75 / 0.75 | fit cover | ✓ |
| /hall-of-fame | s1 | Emma Walker | 0.75 / 0.75 | fit cover | ✓ |
| /hall-of-fame | s1 | Mo Hassan | 0.75 / 0.75 | fit cover | ✓ |
| /hall-of-fame | s1 | Sophie Knight | 0.75 / 0.75 | fit cover | ✓ |
| /hall-of-fame | s1 | Liam O'Connor | 0.75 / 0.75 | fit cover | ✓ |
| /hall-of-fame | s1 | Rachel Stone | 0.75 / 0.75 | fit cover | ✓ |
| /hall-of-fame | s1 | Dan Pierce | 0.75 / 0.75 | fit cover | ✓ |
| /hall-of-fame | s1 | Anya Patel | 0.75 / 0.75 | fit cover | ✓ |
| /meet-the-team | s1 | Joby Chadd | 1.00 / 1.00 | fit cover | ✓ |
| /meet-the-team | s1 | Ricky | 1.00 / 1.00 | fit cover | ✓ |
| /meet-the-team | s1 | Lucy Davies | 1.00 / 1.00 | fit cover | ✓ |
| /testimonials | s0 | Sarah M. | 1.00 / 1.00 | fit cover | ✓ |
| /testimonials | s1 | Tom R. | 0.80 / 0.80 | fit cover | ✓ |
| /testimonials | s1 | Priya K. | 0.80 / 0.80 | fit cover | ✓ |
| /testimonials | s1 | Daniel H. | 0.80 / 0.80 | fit cover | ✓ |
| /testimonials | s1 | Alice T. | 0.80 / 0.80 | fit cover | ✓ |

### Price displays (33 instances)

Most common raw signature: `£260 · Bebas Neue · sky-bright`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | s1 | from £260 | 20px / 20px | £260 · Bebas Neue · sky-bright | ✓ |
| / | s1 | £1,750 | 20px / 20px | £1,750 · Bebas Neue · sky-bright | ≠ |
| / | s1 | from £60 | 20px / 20px | £60 · Bebas Neue · sky-bright | ≠ |
| /tandem | s0 | G-Force Buzz Tandems from £260 — t | 19px / 19px | £260 · Barlow · white/90 | ≠ |
| /tandem | s2 | £260 | 30px / 30px | £260 · Bebas Neue · primary | ≠ |
| /tandem | s2 | £140 | 30px / 30px | £140 · Bebas Neue · primary | ≠ |
| /tandem | s2 | £100 | 30px / 30px | £100 · Bebas Neue · primary | ≠ |
| /tandem | s2 | £24.73 | 30px / 30px | £24.73 · Bebas Neue · primary | ≠ |
| /tandem | s2 | £50 | 30px / 30px | £50 · Bebas Neue · primary | ≠ |
| /tandem | s2 | £20 | 30px / 30px | £20 · Bebas Neue · primary | ≠ |
| /tandem | s2 | £40 | 30px / 30px | £40 · Bebas Neue · primary | ≠ |
| /tandem | s2 | £60 | 30px / 30px | £60 · Bebas Neue · primary | ≠ |
| /tandem | s2 | Your sponsorship can cover the £26 | 14px / 14px | £260 · Barlow · ink | ≠ |
| /tandem | s4 | Up to 15 stone there’s no extra ch | 16px / 16px | £20, £40, £60, · Barlow · muted · FAQ (CMS text) | ≠ |
| /tandem | s4 | Yes — an Outside Camera package is | 16px / 16px | £140 £100, · Barlow · muted · FAQ (CMS text) | ≠ |
| /tandem | s4 | Jumps depend on the weather and ru | 16px / 16px | £50 · Barlow · muted · FAQ (CMS text) | ≠ |
| /tandem | s4 | Yes — you can raise sponsorship fo | 16px / 16px | £260 · Barlow · muted · FAQ (CMS text) | ≠ |
| /tandem | s5 | £260 full tandem payment | 14px / 14px | £260 · Barlow · white/90 | ≠ |
| /aff | s3 | £1,750 | 60px / 60px | £1,750 · Bebas Neue · primary | ≠ |
| /aff | s3 | £600 | 60px / 60px | £600 · Bebas Neue · primary | ≠ |
| /aff | s3 | £210 per jump | 20px / 20px | £210 · Bebas Neue · primary | ≠ |
| /aff | s3 | £140 per jump | 20px / 20px | £140 · Bebas Neue · primary | ≠ |
| /aff | s7 | The AFF course (Levels 1–8) is £1, | 16px / 16px | £1,750, £600 · Barlow · muted · FAQ (CMS text) | ≠ |
| /aff | s7 | No — membership isn’t included in  | 16px / 16px | £125 · Barlow · muted · FAQ (CMS text) | ≠ |
| /aff | s7 | Yes — jumpsuit, helmet, goggles an | 16px / 16px | £5 · Barlow · muted · FAQ (CMS text) | ≠ |
| /coached | s1 | From £60 per session | 14px / 14px | £60 · Barlow · primary | ≠ |
| /hall-of-fame | s1 | Charity Tandem — £3,200 raised | 14px / 14px | £3,200 · Barlow · white/90 | ≠ |
| /news/new-aff-course-in-seville | s1 | 8 places left | 16px / 16px | £300 · Barlow · oklab(0.999994 0.0000455678 0.0000200868 / 0.85) | ≠ |
| /terms | s1 | Tandem skydive fees are paid direc | 16px / 16px | £24.73 · Barlow · ink | ≠ |
| /terms | s1 | A £50 rebooking fee applies when y | 16px / 16px | £50 · Barlow · ink | ≠ |
| /testimonials | s1 | Did a charity tandem and raised ov | 16px / 16px | £1,000 · Bebas Neue · white | ≠ |
| /vouchers | s1 | £260 | 36px / 36px | £260 · Bebas Neue · sky-bright | ✓ |
| /vouchers | s1 | Buy for £260 — delivered by email | 16px / 16px | £260 · Barlow · white | ≠ |

### Page heroes (21 instances)

Most common raw signature: `gradient/flat · scrim no · bottom rule 4px · h1 88px`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| / | hero | ONE LIFE. ONE ADVENTURE. LIVE IT. | 851px / 712px | photo · scrim yes · bottom rule 0px · h1 129.6px (390: photo · scrim yes · bottom rule 0px · h1 52px) | ≠ |
| /tandem | hero | TANDEM SKYDIVE | 367px / 264px | photo · scrim yes · bottom rule 4px · h1 129.6px (390: photo · scrim yes · bottom rule 4px · h1 52px) | ≠ |
| /aff | hero | ACCELERATED FREEFALL | 367px / 310px | photo · scrim yes · bottom rule 4px · h1 129.6px (390: photo · scrim yes · bottom rule 4px · h1 52px) | ≠ |
| /coached | hero | COACHED SKILLS | 367px / 264px | photo · scrim yes · bottom rule 4px · h1 129.6px (390: photo · scrim yes · bottom rule 4px · h1 52px) | ≠ |
| /book/tandem | hero | BOOK YOUR JUMP | 331px / 288px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /book/aff | hero | RESERVE YOUR COURSE | 362px / 288px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /contact | hero | CONTACT US | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /hall-of-fame | hero | HALL OF FAME | 267px / 225px | photo · scrim yes · bottom rule 4px · h1 88px (390: photo · scrim yes · bottom rule 4px · h1 44px) | ≠ |
| /meet-the-team | hero | MEET THE TEAM | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /news | hero | LATEST NEWS | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /news/new-aff-course-in-seville | hero | NEW AFF COURSE IN SEVILLE | 331px / 298px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /newsletter | hero | STAY IN THE LOOP | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /payment/cancelled | hero | PAYMENT CANCELLED | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /payment/success | hero | PAYMENT COMPLETE | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /privacy | hero | PRIVACY POLICY | 277px / 172px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /shop | hero | LOST IN FREEFALL | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /terms | hero | TERMS & CONDITIONS | 277px / 172px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /testimonials | hero | TESTIMONIALS | 547px / 477px | photo · scrim yes · bottom rule 4px · h1 12px | ≠ |
| /vouchers | hero | GIVE THE JUMP | 362px / 288px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /account/login | hero | MY ACCOUNT | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |
| /this-page-does-not-exist | hero | LOST IN FREEFALL | 331px / 257px | gradient/flat · scrim no · bottom rule 4px · h1 88px (390: gradient/flat · scrim no · bottom rule 4px · h1 44px) | ✓ |

### Feature / check lists (7 instances)

Most common raw signature: `check · primary · gap 12px · align flex-start · x-ui.feature-list`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| /tandem | s1 | Highest tandem skydive in the UK | 20px / 20px | check · primary · gap 12px · align flex-start · x-ui.feature-list | ✓ |
| /tandem | s5 | £260 full tandem payment | 16px / 16px | check · sky-bright · gap 8px · align center · inline | ≠ |
| /aff | s1 | Full UK ground school: equipment,  | 20px / 20px | check · primary · gap 12px · align flex-start · x-ui.feature-list | ✓ |
| /aff | s3 | All equipment | 16px / 16px | check · primary · gap 8px · align center · inline | ≠ |
| /aff | s3 | 10 jumps for A Licence | 16px / 16px | check · primary · gap 8px · align center · inline | ≠ |
| /coached | s1 | Belly flying & RW | 20px / 20px | check · primary · gap 12px · align flex-start · x-ui.feature-list | ✓ |
| /contact | s1 | +44 (0)7583 155 951 | 20px / 20px | other icon · primary · gap 12px · align center · x-ui.feature-list | ≠ |

### FAQ accordions (19 instances)

Most common raw signature: `ink · button · aria-expanded false`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| /tandem | s4 | IS IT SAFE? DO I NEED ANY EXPERIEN | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /tandem | s4 | ARE THERE WEIGHT AND AGE LIMITS? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /tandem | s4 | WHAT SHOULD I WEAR? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /tandem | s4 | CAN I GET PHOTOS OR VIDEO OF MY JU | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /tandem | s4 | WHAT HAPPENS IF THE WEATHER IS BAD | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /tandem | s4 | HOW LONG DOES THE DAY TAKE? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /tandem | s4 | I HAVE A MEDICAL CONDITION — CAN I | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /tandem | s4 | CAN I JUMP FOR CHARITY? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | HOW LONG DOES THE AFF COURSE TAKE? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | WHAT LICENCE DO I GET? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | ARE THERE PREREQUISITES? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | HOW MUCH DOES IT COST AND IS THERE | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | IS BRITISH SKYDIVING MEMBERSHIP IN | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | WHAT IF I NEED TO REPEAT A LEVEL? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | IS KIT PROVIDED? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /aff | s7 | WHERE DO COURSES RUN, AND WHAT ABO | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /coached | s3 | WHO IS COACHING FOR? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /coached | s3 | WHAT DISCIPLINES DO YOU COACH? | 16px / 16px | ink · button · aria-expanded false | ✓ |
| /coached | s3 | HOW DOES PRICING WORK? | 16px / 16px | ink · button · aria-expanded false | ✓ |

### Discipline chips (3 instances)

Most common raw signature: `border 2px primary · text navy`

| page | where | text | size 1440 / 390 | attributes | same signature as the most common? |
|---|---|---|---|---|---|
| /meet-the-team | s1 | TANDEM | 11.2px / 11.2px | border 2px primary · text navy | ✓ |
| /meet-the-team | s1 | AFF | 11.2px / 11.2px | border 2px primary · text navy | ✓ |
| /meet-the-team | s1 | COACHING | 11.2px / 11.2px | border 2px primary · text navy | ✓ |
