# UI Pages — layout, hierarchy & homepage (pass 3 of 4)

Page-level audit, then the polish. Builds on pass 1 (type/spacing tokens) + pass 2
(button/input/arrow-link components). Brand fixed (navy/blue/white, sharp corners,
photo-led, no new colours/dark mode).

## Step 1 — Audit (current state)

### Cross-cutting: tokens not yet applied at page level (passes 1–2 only touched shared infra)
- **Section rhythm drifts.** Many page sections hand-roll `py-16 lg:py-24` (identical to
  `py-section-sm lg:py-section` — just not tokenised): tandem ×4, aff ×4, coached ×2,
  home services/trust/social. Others drift to non-system values: `py-20 lg:py-28`
  (testimonials, tandem), `py-20 lg:py-32` (home about), `py-28 lg:py-40` (home CTA),
  `py-16 lg:py-20` (home trust), bare `py-20` (home social, newsletter). → no single
  cadence. **Fix:** a 2-step rhythm — standard `py-section`, and a new
  `py-section-lg` token for the dramatic dark/photo feature bands — applied everywhere.
- **Section headings bypass the type scale.** Several hand-roll `text-5xl md:text-7xl` /
  `text-4xl md:text-6xl` / `text-6xl md:text-8xl` instead of `text-h2`/`text-h1`:
  home About (`:82`), Trust (`:108`), Newsletter (`:212`), Contact CTA (`:225`);
  testimonials (`:37`); tandem feature heading (`:121`). → inconsistent heading step
  page-to-page. **Fix:** migrate to `text-h2` (or `text-h1` for the dramatic CTA close).
- **Card titles** use raw `text-2xl/3xl` (news cards, pricing cards, instructor plates).
  News card titles (`text-2xl`) → `text-h3`. The large display titles on photo tiles
  (services `text-4xl/5xl`) and stat numerals sit in the h2–h3 gap and read as deliberate
  display type — left as explicit sizes (noted; not every numeral needs a token).
- **Eyebrow tracking is inconsistent** — `tracking-[0.2em]` / `[0.25em]` / `[0.3em]` /
  `[0.4em]` for the same "eyebrow label" pattern. **Fix:** standardise eyebrows to one
  value (`tracking-[0.25em]`) as sections are touched.

### Homepage — composition (the main target)
Section order: hero → services (photo tiles) → about (navy, 2-col) → trust band → team
rail → **social + news** → testimonials → newsletter band → contact CTA. Mostly strong and
photo-led. The **social + news block is backwards**:
- It's a **50/50 split** with a **placeholder "Instagram" gallery on the dominant left**
  (3×2 photo grid + "Follow @… for jumps") and the **real Latest News squeezed into the
  right half** (two small text cards). The valuable, dynamic content is subordinated to a
  manual gallery dressed up as a feed — and carries the note **"Live Instagram feed
  connects via Meta Graph API — ask to enable."** (a feature that does not exist; flagged
  in `COMPLETENESS-CHECK.md`). → **Rebuild (Step 3):** News dominant left, a compact honest
  "Follow us" social block right; drop the live-feed framing.

### Other pages (hierarchy reads well post-pass-1; mainly rhythm/token tidy)
- **tandem / aff / coached:** clear hero → section-heading → content → enquiry hierarchy.
  Sections hand-roll `py-16 lg:py-24` (tokenise) and a couple of `py-20 lg:py-28` feature
  bands (→ `py-section-lg`); one tandem heading bypasses the scale.
- **testimonials / hall-of-fame:** photo-tile grids read well (built on `<x-site.photo-tile>`);
  a heading + a feature band hand-roll sizes/padding to tokenise.
- **news (index/show):** card titles `text-2xl` → `text-h3`; otherwise consistent.
- **privacy / terms / contact / vouchers / newsletter:** simple, already tidy post-pass-1
  (privacy verified in pass 1; contact form polished in pass 2).

### Summary of work
1. Add `--spacing-section-lg` token; normalise section rhythm to a 2-step system.
2. Migrate divergent section headings → `text-h2`/`text-h1`; news card titles → `text-h3`;
   standardise eyebrow tracking.
3. **Rebuild the homepage social+news block** (News dominant, honest social, no fake feed).
4. Consistency sweep; keep empty states intentional.

## Step 2–4 — implemented

### Step 2 — hierarchy & rhythm (all pages)
- **New token `--spacing-section-lg` (128px)** for dramatic dark/photo feature bands. The
  rhythm is now a clean 2-step system: standard sections `py-section-sm lg:py-section`
  (64/96), feature bands `py-section lg:py-section-lg` (96/128). Replaced the drifting
  `py-20/28/32/40` hand-rolled values across home (about/trust/newsletter/cta), tandem
  (gift band), aff (trust), coached (intro), testimonials (featured band).
- **Section headings migrated to the type scale:** the ones that bypassed it
  (`text-5xl md:text-7xl` etc.) now use `text-h2` (or `text-h1` for the home Contact CTA
  close); their leads use `text-lead` + `max-w-measure`. News card titles → `text-h3`.
- **Eyebrows standardised** to `tracking-[0.25em]` on the sections touched.
- Verified the About / gift / trust bands across the range — consistent heading step,
  readable leads, even cadence; brand look unchanged.

### Step 3 — homepage News + Social rebuild (feat)
- Replaced the backwards 50/50 "Instagram gallery (left) + News (right)" block. Now:
  **Latest News dominates the left 2/3** (text-h2 heading, article cards with date /
  text-h3 headline / lead, `arrow-link` "All news →"); a compact navy **"Follow us"** card
  fills the right 1/3 — real CMS social links (icon + handle + arrow, new tab, hidden when
  the URL is empty) and a small clearly-labelled **"From the dropzone"** curated photo grid
  (the CMS Gallery — curated, NOT a feed).
- **Removed the "Live Instagram feed … ask to enable" framing entirely** (resolves the
  COMPLETENESS-CHECK finding). Stacks News-first then social on mobile (verified 390).
- Owner content: real Instagram/Facebook URLs + curated gallery photos.

### Step 4 — consistency
- Heroes (`<x-site.page-hero>`), section headers (`<x-site.section-heading>` or the
  migrated `text-h2` pattern) and CTAs (`<x-ui.button>`) are now consistent page-to-page;
  no page hand-rolls a divergent heading/rhythm. Empty states (AFF "coming soon", hidden
  calendar) are intentional and unchanged.

### Verified
1440 / 1280 / 1024 / 390 + short-height across home, tandem, aff, coached, testimonials,
news, hall-of-fame. `composer check` green (344). Before/after in `ui-review/p-*`. One new
token (`--spacing-section-lg`).

