# 028 — The AFF page scrolls sideways on phones (the pay card is wider than the screen)

One branch, one task. Read `CLAUDE.md`, `ui-guidelines.md` (buttons) and `false-green.md`. **Size: small.**

`git checkout main && git pull` → `git checkout -b fix/aff-mobile-overflow`.

> **Why this exists.** Chat-Claude walked the site on 9 Oct 2026 in Ben's Chrome, loading every sitemap page in
> a frame of exactly 375, 390, 768, 820 and 1024 CSS px, so the site's own breakpoints applied.
>
> **Measured, not read: `/aff` at 375 and at 390 has a document width of 451px**, so the whole page scrolls
> sideways. It's the only page and width with the problem: all 16 pages × 5 widths were checked.
> - The "Reserve your spot / Secure your place" pay card renders **435px** wide inside a **358px** grid column.
>   Its text is cut off at the right edge ("reserve your place with a depo…").
> - The AFF enquiry form below it, in the same grid, is stretched to 435px too.
>
> **Cause, measured in the page:**
> - The shared button has `whitespace-nowrap` (`components/ui/button.blade.php`, base classes).
> - "CHOOSE A COURSE & PAY DEPOSIT" at the large size, with 40px side padding and `tracking-widest`, is
>   **371px** and can't wrap.
> - The card adds `p-8` (64px), giving 435px.
> - Its grid item (`div.reveal`) has the default `min-width: auto`, so it grows to its content instead of
>   shrinking.
>
> **Ruled out:** the enquiry form itself. It's only wide because it shares the grid track with the card.
>
> **Why nothing caught it:**
> - the suite renders no layout;
> - axe doesn't check overflow;
> - the 016 screenshots of `aff-price-card-390` cropped the card, not the page edge.
>
> That's `false-green.md`'s "the instrument looked at the wrong thing".

## Build

1. **Long button labels may wrap on narrow screens.** Prefer fixing it in the shared button, e.g. `whitespace-nowrap` only from `sm:` up and
   `text-balance` / centred text below it, or a full-width button on mobile, so every long CTA site-wide is
   covered, not just this one. Decide, record why, and check the other long labels ("Choose a date & book",
   "Book a session", "Learn to skydive") still look right at 390 and at desktop.
2. Grid children on content pages shrink (`min-w-0`) where they hold cards, so one wide child can't stretch its
   track again.
3. **Structural guard (the more valuable half):** a browser test that loads every URL in `/sitemap.xml`
   (plus `/book/tandem`, `/book/aff`, `/vouchers`, `/account/login`) at **375×667** and asserts
   `document.documentElement.scrollWidth <= clientWidth`. On failure it names the widest element whose right
   edge passes the viewport and isn't inside an `overflow-x` container. Red on current `main` (`/aff`). Prove it
   with a planted wide element too. Use the project's existing Playwright harness; if the suite-collection test
   from 002 needs the directory added, add it.

## Rules

- **The homepage is signed off:** pixel-identical at 1440 and 390. If the button change touches homepage CTAs,
  their rendering must not change at those widths (the labels there are short).
- No copy changes. "CHOOSE A COURSE & PAY DEPOSIT" stays as it is.
- Desktop rendering of every button unchanged at 1024 and up.

## Tests

- The overflow guard (red on `main`, proven with a planted element).
- Existing button tests are untouched, or re-pointed with a reason.

## Finish

`composer check` green. Before and after crops of the `/aff` pay card and enquiry form at 375 and 390, plus
the homepage hero at 390 (unchanged), as cropped JPEGs. DECISIONS entry. Push the branch. **Do not merge**
(unless on an authorised unattended run).
