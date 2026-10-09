# 015 — Consistency small fixes: C-3, C-4, C-12, C-13

One branch, one task: bring four small odd ones out onto the shared style. One commit each. Read `CLAUDE.md`,
`ui-guidelines.md` and `ui-review/CONSISTENCY.md` (C-3, C-4, C-12, C-13). **Size: small.**

`git checkout main && git pull` → `git checkout -b ui/consistency-small-fixes`.

> **Why this exists.** The itemised consistency audit (003) flagged them. Ben agreed they're worth doing before
> launch. Locations are from that report, so confirm each one on current `main` first.

## Build

- **C-3, `/news` labels:** article dates are `tracking-[0.2em]` (`news/index.blade.php:29`) where the
  homepage's news cards are `0.25em` (`home.blade.php:136`, the signed-off reference). The "G-Force News"
  image label is `0.3em` (`:24`), the only one on the site. Use one shared news date/label style at `0.25em`,
  and change `/news` only.
- **C-4, testimonial-grid role labels** (`testimonials.blade.php:83`): weight 400, `0.2em`, `white/70`.
  Every other role label is 700, `0.25em`, sky-bright (`instructor-card.blade.php:28`). Use the shared
  role-label style.
- **C-12, loading label:** "Sending..." in `tandem-enquiry-form`, `contact-form` and `aff-enquiry-form`
  against "Sending…" in `coached-enquiry-form`. Standardise on "Sending…". If the label is defined in more
  than one place, define it in one.
- **C-13, section padding:** `tandem.blade.php:21` and `aff.blade.php:25` hand-roll `py-16 lg:py-24`. Swap to
  `py-section-sm lg:py-section`, which has the same values, so there's no visual change.

## Rules

- **The homepage is the reference and doesn't change.** Screenshot before and after at 1440 and 390;
  pixel-identical.
- Reuse existing shared styles or components. If one doesn't exist, make it a shared one, not an inline
  copy.
- C-13 must be pixel-identical on `/tandem` and `/aff`.

## Tests

No new behaviour tests; this is styling plus one string. If the "Sending…" label becomes shared, a test
asserting every enquiry form renders it is enough. `composer check` green.

## Finish

Before and after crops of `/news`, `/testimonials` and one form at 1440 and 390 (cropped JPEGs) in
`ui-review/`. Update `ui-guidelines.md` "known gaps" (remove the four). DECISIONS entry. Push the branch. **Do
not merge** (unless on an authorised unattended run).
