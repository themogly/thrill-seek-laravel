# 017 — Feature-split images ignore the admin crop on desktop

One branch, one task. Read `CLAUDE.md`, `ui-guidelines.md` (imagery), `DECISIONS.md` (the June image
crop/ratio entry, `c80dd6f`) and `ui-review/CONSISTENCY.md` C-8. **Size: small. VISUAL: Ben looks before
merging.**

`git checkout main && git pull` → `git checkout -b ui/feature-split-ratio`.

> **Why this exists.** Consistency audit C-8, agreed by Ben to fix before launch.
>
> **Verified on `origin/main` = `7e01bfe` by code read:**
> - `components/site/feature-split.blade.php:21` is `aspect-[16/10]` on mobile but
>   `md:aspect-auto md:h-[24rem] lg:h-[30rem]` on desktop. At 1440 that's about 1.42:1.
> - The admin's locked-ratio crop tool crops these uploads to 16:10, so on desktop the owner's chosen
>   framing loses its sides.
> - Used only on `/tandem`, `/aff` and `/coached`, **not the homepage**.

## Build

Pick one and record why:
- (a) keep 16:10 at every width, letting the image's height follow the column width; or
- (b) keep the desktop frame and change the admin crop for these fields to match it, re-cropping nothing that
  already exists.

My view is (a): the crop tool is the owner's control, so the layout should honour it. But (a) changes how tall
the split sections are on desktop, so check the text column still balances at 1024, 1280 and 1440. If the
text column then runs much taller than the image, say so with screenshots; don't patch it with magic numbers.

Keep `srcset`, the width/height attributes and lazy-loading correct for the new ratio (no layout shift).

## Rules

- The homepage doesn't use this component. Confirm it with a grep, and confirm it's unchanged with a
  screenshot.
- No change to the crop tool's other fields.

## Tests

- The rendered `<img>` keeps a 16:10 box at md and lg widths (a structural check of the classes, or a
  Playwright measurement at 1440). Red on current `main`.

## Finish

`composer check` green. Before and after crops of each product page's split sections at 1024, 1280, 1440 and
390 (cropped JPEGs) in `ui-review/feature-split-ratio/`. DECISIONS entry. Push the branch. **Do not merge, on
any run.** Ben looks first.
