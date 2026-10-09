# Audits

Report-first review passes. Each is run on demand in a fresh session by pasting its file. They VERIFY the build is sound — the skills (in `../skills/`) make the code right as it's written; these audits confirm nothing slipped.

## How to run one
1. Open a fresh Claude Code session in the project (`claude --dangerously-skip-permissions`, your model of choice).
2. Paste the contents of one audit file.
3. It branches off main, writes a report to `audits/reports/<name>.md` FIRST, then fixes in phases (Critical → Refinement → Polish), and leaves the branch unmerged for your review.

## The audits
- **design-audit.md** — responsive/layout/hierarchy/accessibility/consistency, tested across a RANGE of viewport sizes.
- **accessibility-audit.md** — dedicated WCAG/a11y pass: form labels, ARIA correctness, colour contrast, landmarks/headings, keyboard, focus, alt text. Targets the set Lighthouse/PageSpeed flags. Automated (axe/Lighthouse) + manual.
- **admin-audit.md** — CMS/admin-panel quality: image-shape control, validation the site/checkout depends on, no exposed dangerous/internal fields, no orphaned fields, admin/public theming separation, owner-friendly UX.
- **code-style-audit.md** — Laravel idiom & consistency; enforces (doesn't relitigate) the project's documented decisions.
- **seo-audit.md** — technical + on-page SEO; inventories first, real data only in structured data.
- **email-audit.md** — every email the app sends: a real send behind every "sent" message, sibling paths that send the same mail, the provider key under the name config actually reads, the worker consuming the right queue, retries + after-commit, locale pinned in the worker, and a guard test so it can't drift.
- **security-audit.md** — security + privacy (access control/IDOR, secrets, PII/GDPR, payments, webhooks, headers, private monitoring).

## When to run
Periodically — after every few features, not only at the end. Drift caught early is a one-line fix; caught at the end it's a whole audit round. They are report-first and stay unmerged until you review.

## Principles (all share)
- Report before fixing; gate fixes behind the committed report.
- Short honest report beats a padded one — few items on a clean codebase is a good result, not a failure.
- Separate real defects from OWNER tasks (content, legal copy, infra) — never fabricate content.
- Never undo a documented decision; escalate conflicts to a Discussion section.
- Check gate green before every commit; never commit red; pin behaviour-preserving refactors with a test first.
- **Verify by attack, not by reading.** Request another user's record by id; bypass the screen and post the write; try the back button out of the locked state. A policy class that looks correct is not evidence.
- **Measure on a rebuilt tree.** Rebuild assets before any browser measurement and confirm dependencies match the lockfile — several confident findings have turned out to be the environment rather than the code.
- Where a finding is a class rather than an instance, the fix ships with a test that walks the class — and the test is proven by making it fail on purpose.
- **Audit the measuring instruments too.** A harness that measured one control and reported ALL PASS; contrast scripts that parsed `oklch()` with an `rgb()` regex; snapshot pages measuring the runner's fallback font; a headless browser with no toolbar. Before trusting a number, ask what the instrument touched and where it ran — and prove a guard by making it fail on purpose. `../false-green.md` is the catalogue.
- **Measure at the device's own numbers.** The real viewport (toolbar showing, both orientations), the real font, the real runtime. Where Playwright cannot see (toolbars, keyboards, cameras) the evidence is a photo from the device, recorded as such — never a headless pass standing in for it. See `../verification/real-device-checks.md`.
