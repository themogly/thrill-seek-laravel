# Run report — unattended run 1 (RUNNING-ORDER steps 1–7)

Started 9 Oct 2026 from `main` = `be9e145` (= `origin/main`). Brief: `prompts/unattended-run-1.md`.
Before branching, Step 0 of 001 stopped three times, and Ben resolved each one: he had me drop a June
untracked-only stash (`stash@{0}` on `ui/logo-svg`, recoverable as `56c8c02` until gc), delete
`.playwright-mcp/` (June browser-tool logs), and run this brief (`prompts/unattended-run-1.md`, which
wasn't on 001's expected-files list).

## 1 — 001 kit sync · `chore/kit-sync-2026-09` · merge `086d593`
- Tests 369 → 369 (`composer check` green before and after; docs only).
- Copied the September kit's prompts/, false-green.md, gates/, email audit and verification/ verbatim
  (all 20 `cmp`-identical), refreshed 8 drifted audit/ui-pass copies (kit-only additions), added 6
  architecture + 4 testing rules and a Workflow section to CLAUDE.md, and added the FPM reload and the
  never-`key:generate` rules to the SETUP.md deploy sequence.
- Gap report: in DECISIONS ("Kit sync to the September 2026 starter kit"). Short version: the Step 0
  stops above; `unattended-run-1.md` committed too; 001 misdescribes `bootstrap/app.php:25` (that's
  `SecurityHeaders`, not a session reader), though the rule holds.
- `OVERNIGHT-DEFAULT — CONFIRM`: motion ambition recorded as **subtle** (as built).
- Owner/Ben notes: the global `frontend-design` skill lags the kit; `admin-design`, `laravel-craft`,
  `web-app-security` aren't installed globally. The `motion` npm package is unused.
- Visual: none (docs only).

## 2 — 002 structural guards · `test/structural-guards` · merge `092536d`
- Tests 369 → 378. `composer check` green; `phpunit.mysql.xml` 378/378 green.
- Six guards (9 tests), each green on real code and **proven red by a planted violation** (one-line
  reds in DECISIONS, "Structural guards…"). No allowlists. No guard went red on real code.
- Findings while building: Alpine reaches every page **two ways** (layout `@livewireScripts` +
  Livewire 4 auto-injection via the footer island). Two in-process traps in the test client
  (Livewire's rendered-scripts flag; the shared session store/guard) were neutralised and recorded.
  The naive session test is a demonstrated false green.
- Gap report: guard 4 widened to the components Livewire views render; CLAUDE.md gained rules
  13 (reserved names) and 14 (Alpine scope) so those guards have a rule to sit beside.
- `OWNER DECISION — PENDING` / `OVERNIGHT-DEFAULT — CONFIRM`: none added.
- Visual: none (tests only).
