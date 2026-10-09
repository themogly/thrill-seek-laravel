# 001 — Bring the repo up to the September 2026 starter kit (docs and CLAUDE.md rules only)

One branch, one task. Read `CLAUDE.md`, `SETUP.md` and `DECISIONS.md` first, especially the bootstrap and
architecture entries and the June audit close-outs. Read `RUNNING-ORDER.md` at the repo root too.
**Size: medium, docs only.** No app code, views, config or tests change in this branch.

## Step 0 — state check, before branching

`git fetch --all`, then report:
- whether local `main` equals `origin/main`, which GitHub shows at `be9e145` on 24 June 2026;
- `git status`;
- `git stash list`;
- `git branch -a`, with `git log --oneline main..<branch>` for every branch.

On 9 Oct 2026 chat-Claude read `.git/refs` on this Mac: local `main` = `origin/main` = `be9e145`, and there are
no other local branches. Confirm it.

**Expected untracked files.** Chat-Claude placed exactly these, and `git status` should show only them:
- `RUNNING-ORDER.md`;
- `prompts/001-kit-sync.md`, `prompts/002-structural-guards.md`, `prompts/003-itemised-consistency-audit.md`;
- `prompts/unattended-run-1.md`.

They get committed on this branch. **Any other modification, untracked file, stash or unmerged branch: STOP
here.** List it and do nothing else. The kit's rule is merge before you move on.

Clean apart from those? Then `git checkout main && git pull` → `git checkout -b chore/kit-sync-2026-09`. The
untracked files come with you.

> **Why this exists.** Ben rewrote the starter kit in September 2026. G-Force was bootstrapped from the June kit
> on 5 June, so the repo is missing the new layers:
> - `prompts/`, `false-green.md` and `gates/`;
> - `audits/email-audit.md`;
> - `verification/` (real-device checks, tester triage and the pre-launch checklist);
> - from `CLAUDE.md`: the four new architecture rules and the prompt-discipline rules;
> - from `SETUP.md`: the newer deploy-sequence steps.
>
> **Premise unverified on your machine; checked by chat-Claude against the public GitHub repo at `be9e145`
> (9 Oct 2026):**
> - `CLAUDE.md` has 0 matches for `svh`, `StartSession`, `one writer`, `picture of a gate`, `premise`,
>   `gap report`, `OVERNIGHT-DEFAULT` and `false-green`;
> - `SETUP.md` has 0 matches for `FPM` and `key:generate`;
> - `gates/`, `prompts/` and `verification/` don't exist;
> - `audits/` has no `email-audit.md`.
>
> **Ruled out: this is not a code fix.** None of the four new rules is broken today:
> - 0 `@vite` / `<script>` and 0 `x-if` under `resources/views/livewire/`;
> - session middleware is appended to the `web` group (`bootstrap/app.php:25`);
> - the only viewport-height use is `min-h-screen` (`layouts/app.blade.php:66`), which the `svh` rule allows
>   as a `min-` cap.
>
> If you find otherwise, say so. Don't fix it here. The guards are prompt 002.

## Build

**1. Confirm the kit.** The source is `~/Sites/starter-kit/` (no underscore). Its `README.md` must open with *"What changed in
this revision (September 2026)"*. If it doesn't, stop: it's the wrong copy.

**2. Copy verbatim. The kit is the source of truth: don't paraphrase, don't regenerate.**
- `prompts/README.md`, `prompts/writing-prompts.md`, `prompts/running-order.md` → `prompts/`
- `false-green.md` → repo root, beside `prompts/`
- `gates/README.md`, `gates/completeness-check.md`, `gates/cms-field-usage-check.md`,
  `gates/pre-staging-gate.md` → `gates/`
- `audits/email-audit.md` (new)
- `verification/real-device-checks.md` and `verification/tester-feedback-triage.md` → `verification/`
- `verification/pre-launch-checklist.md` → `verification/CHECKLIST.md`, with one line added at the top:
  `> NOT YET TAILORED to G-Force — see RUNNING-ORDER.md step 9.` Tailoring is a separate prompt.

**3. Refresh the copies that drifted.** On `be9e145` these differ from the kit:
- `audits/README.md`, `accessibility-audit.md`, `admin-audit.md`, `code-style-audit.md`, `design-audit.md`,
  `security-audit.md`;
- `ui-passes/README.md` and `ui-passes/04-guidelines.md`.

`seo-audit.md` and `ui-passes/01–03` are identical. **Diff each file before overwriting it.** Chat-Claude's diff
showed only kit additions of 1–6 lines per file. If any repo copy carries a G-Force-specific edit the kit
lacks, don't overwrite that file. List it and leave it.

**4. Skills: report, don't change.** Find where this project's skills live: `.claude/skills/` in the repo (not on
GitHub) or `~/.claude/skills/`. Diff each one against the kit's `skills/*/SKILL.md` and put the result in the
DECISIONS entry. If they're user-global, **don't edit them from this branch**: they affect Ben's other projects,
and that's his call.

**5. CLAUDE.md: add what the bootstrap now writes and this file lacks.** Adapt the wording into the existing
sections. Don't paste the bootstrap block wholesale: this file's 223 lines are G-Force's decisions, and they win.
Before adding each rule, grep for it in other words. If it's already there, don't duplicate it; note which rule
covered it. Candidates, from `bootstrap.md` §1:

- Architecture:
  - state that a component's markup depends on is re-rendered by the mechanism that changes the state;
  - nothing loads or inserts DOM inside a Livewire-morphed view;
  - session-reading middleware goes on `web`, after `StartSession`;
  - shells are sized in `svh` (viewport units only as `min-`/`max-` caps);
  - one writer per fact, one reader per figure;
  - a gate must never become a picture of a gate.
- Testing:
  - fix the instance, then guard the class (prove the guard fails on purpose);
  - assert the suite collects every `tests/*` directory;
  - a measurement is only as good as the tree and build it was taken on;
  - verify state against the code and git, not claims.
- Workflow:
  - verify a prompt's premise before building;
  - a gap report when a branch deviates;
  - escalate owner decisions;
  - mark agent defaults `OVERNIGHT-DEFAULT — CONFIRM`.
- Pointers to `prompts/`, `false-green.md`, `gates/`, `verification/` and `RUNNING-ORDER.md`.
- **Motion ambition.** G-Force never recorded a level. Read what's actually built (Motion One usage, reveals),
  record the matching level in DECISIONS as as-built, and mark it `OVERNIGHT-DEFAULT — CONFIRM`.

**6. SETUP.md deploy sequence.** Reconcile it with `bootstrap.md` §5's nine steps:
- Add the PHP-FPM reload, with the PHP version in the service name.
- State that the deploy script must **never** contain `php artisan key:generate`, `migrate:fresh` or
  `migrate:refresh`.
- The "converge code-declared matrices" step: first find out whether G-Force *has* one (roles, permissions or
  settings seeded at install and expected to change with code). If it has, add the step. If it hasn't, say so in
  DECISIONS and don't add ceremony.
- Add an owner task: check Ploi's generated deploy script for `key:generate` and delete it after the first run.

**7. RUNNING-ORDER.md and the numbered prompts.** They're already in place: `RUNNING-ORDER.md` at the root and
`prompts/00N-*.md` beside the kit's `prompts/` files. Update only the `main at` line in `RUNNING-ORDER.md` to the
sha you actually branched from, then commit them all unchanged otherwise.

**8. DECISIONS.md: one entry.** What was copied, what was refreshed, which rules were added and which were already
covered, the skills diff, and what was deliberately not done, with reasons:
- `bootstrap.md` not re-run (it would rewrite CLAUDE.md);
- ui-passes not re-run (ran in June; the kit says once);
- `add-motion-layer` not adopted.

## Rules

- **No changes** to `app/`, `resources/`, `routes/`, `config/`, `database/`, `tests/`, `composer.*`,
  `package*.json` or `ui-guidelines.md`.
- Don't run any audit or gate in this branch.
- Kit files are copied byte-for-byte. Prove it with `cmp` against `~/Sites/starter-kit/` for every copied file
  and paste the output into the final summary.
- The kit must stay generic. Grep every copied file for other projects' names. If one appears, stop: the kit is
  contaminated, and that's a kit fix, not a G-Force one.

## Tests

None new. This branch is docs only, so the usual "one test that fails against main" doesn't apply.
`composer check` must be exactly as green as it was on `main`, with the same test count. Report both numbers.

## Finish

- `composer check` green.
- The DECISIONS entry.
- Push `chore/kit-sync-2026-09`. **Do not merge.**
- If you deviated from this prompt, write a gap report: what you didn't do, what you did that this forbids, and
  what you did that it never mentions.
