# 002 — Structural guards for the kit's architecture rules (tests only)

One branch, one task. Read `CLAUDE.md` (the architecture rules 001 added), `DECISIONS.md` (the 001 kit-sync
entry), and `false-green.md` (#5 "verified by reading", and the "prove a guard" entries).
**Size: medium. Depends on 001 being merged.** If it isn't on `main`, stop and say so.

`git checkout main && git pull` → `git checkout -b test/structural-guards`.

> **Why this exists.** 001 wrote six rules into `CLAUDE.md`. A rule in a doc is a comment asking people to
> remember. The kit's position is: guard the class with a test, and prove the guard by planting a violation.
>
> **Premise unverified on your machine; chat-Claude checked `be9e145` on GitHub.** On that commit, every rule
> below holds:
> - 0 `@vite` / `<script>` and 0 `x-if` under `resources/views/livewire/`;
> - middleware is appended to the `web` group (`bootstrap/app.php:25`);
> - the only viewport-height class is `min-h-screen` (`layouts/app.blade.php:66`), which is allowed;
> - both phpunit configs list `tests/Unit` and `tests/Feature`, and those are the only two test directories.
>
> So every guard should pass on current `main`. **The "fails against current main" requirement is replaced by
> planting one violation per guard, seeing it go red, and removing it.**
>
> **Ruled out:** fixing anything. If a guard goes red on real code, that's a finding for its own prompt (see
> Rules).

## Build: six guards, each its own test file under `tests/Unit/Architecture/` (or wherever the suite keeps structural tests; follow the existing convention)

1. **Suite collects every test directory.** Read `phpunit.xml` *and* `phpunit.mysql.xml`. Assert that every
   directory directly under `tests/` that contains a `*Test.php` appears in a `<testsuite>` in both.
2. **No Livewire reserved names.** Livewire is `^4.3`. Parse the `$wire` alias map out of the **vendored Livewire
   dist JS**, not a hand-kept list. Fail on any public method of any class in `app/Livewire/` that shadows one
   of those aliases (`commit`, `get`, `set`, `call`, `dispatch`, `watch`, …). The parse must fail loudly if it
   finds zero aliases. A parser that silently finds nothing is a guard that always passes.
3. **Alpine directives sit inside an `x-data` root.**
   - Normalise `@click` → `x-on:click` and `:attr` → `x-bind:attr`.
   - Assert that every Alpine directive in every app view sits inside an element with `x-data`, in the same file
     or in a known parent component.
   - Then a page-level check: every layout that renders `x-data` ships Alpine. G-Force gets it inside Livewire's
     bundle, so find out what a plain Blade page without a Livewire component actually loads.
   - **Decide the scope and record why.** Exclude `resources/views/vendor/` and Filament-published views if
     they cause noise. Any allowlist entry carries a reason.
4. **Nothing loads or inserts DOM inside a Livewire-morphed view.** No `@vite`, no `<script` and no `x-if`
   anywhere under `resources/views/livewire/`.
5. **Session-reading middleware isn't global.** Over `bootstrap/app.php`: anything appended or prepended to the
   *global* stack must not read the session. Find the session readers by scanning `app/Http/Middleware/` for
   `session(`, `->session()` and `Session::`. Assert that each one registers on the `web` group. Grep proves
   presence, not order, so add a request test as well: two real HTTP requests, where the first sets session
   state through the app and the second asserts that a session-reading middleware saw it. This is
   `false-green.md`'s "in-process state vs the real request" shape.
6. **No `100vh` / `h-screen` shells.** Across `resources/views/` and `resources/css/`, fail on `100vh`, `h-screen`
   and `h-[100vh]`. Allow `min-h-screen`, `max-h-screen` and `min-`/`max-height` caps.

## Rules

- **Tests only.** No change to `app/`, `resources/`, `routes/`, `config/` or `bootstrap/`, except where a guard
  needs a reusable test helper under `tests/`.
- **Prove every guard.** For each one: plant a minimal violation in a throwaway file, run it, see it go red,
  delete the file, see it go green. Record the red output (one line each) in the DECISIONS entry. A guard that
  was never seen failing isn't trusted.
- **A guard that's red on real code: don't fix the code, and don't weaken the guard.** Leave that guard out of
  the commit and record the violation (file:line and which rule) in DECISIONS as a finding for a new prompt.
  Ship the guards that are green.
- No allowlists without a written reason beside each entry.
- No change to existing tests.

## Tests

The six guards above, plus the request test in #5. Each one is proven by its planted violation (Rules).
`composer check` green, with the new test count reported. Run `phpunit.mysql.xml` once too. Guard 1 reads that
file, so it must hold under both configs.

If context runs short, land fewer guards completely rather than all six partially, and say which are done.
Order of value: 4, 5, 1, 6, 2, 3.

## Finish

- `composer check` green.
- `DECISIONS.md`: one entry listing each guard, its scope, its allowlist (with reasons), and its planted-violation
  proof.
- Add each guard's test name beside its rule in `CLAUDE.md` (one line each), so the rule points at its
  enforcement.
- Push `test/structural-guards`. **Do not merge.**
- Gap report if you deviated.
