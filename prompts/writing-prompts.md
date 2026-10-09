# Writing a task prompt

The unit of work in this kit is **one prompt = one branch = one focused task**. The bootstrap sets the
standards, the skills shape code as it is written, the audits verify afterwards — and the prompts are how
the actual features and fixes get built. This file is how to write one well.

Distilled from a build of roughly a hundred prompts, including the ones that went wrong. Where a rule is
here, something failed without it.

---

## Anatomy

```
# NNN — a title that states the defect or the goal, in plain language

One branch, one task. Read `CLAUDE.md` + `DECISIONS.md` (**the relevant prior entries, named**) + NOTES.
[Size signal: "Small." / "Large — expect to split." / dependencies on other prompts.]

`git checkout main && git pull` → `git checkout -b <type>/<slug>`.

> **Why this exists.** What was reported, in the owner's own words where possible.
> **Verified on `origin/main` = `<sha>`**, in a browser / by test: what you measured, and what you
> RULED OUT (the hypothesis you disproved is worth a line — it stops the implementer chasing it).

## Cause / What is actually happening
The evidence. Measured, quoted, file-and-line. Not "it seems that".

## Build
What to do, and the reasoning. Where there is a real choice, say "decide it and record why".

## Rules
The constraints. Especially what must NOT change.

## Tests (required)
Named assertions, including at least one written so it FAILS against current main.

## Finish
`composer check` green. Screenshots if visual. What to record in `DECISIONS.md`.
Push the branch; **do not merge.**
```

---

## The rules that earned their place

### Lead with evidence, not diagnosis

A prompt that says *"the discount form seems to be broken"* produces guesswork. A prompt that says:

> Clicking **Create** on the empty form produces **0 Livewire requests and 0 error messages**. On the same
> form, the secondary button — which bypasses native submission — produces three. Same form, two buttons,
> opposite outcomes.

produces a fix. Measure it, quote it, give file and line. If you cannot produce evidence, the prompt is not
ready — go and get some.

### Verify the premise before you build — and say so when it is wrong

**Roughly one prompt in ten has a premise that is stale, incomplete or outright false.** The author was
working from an older tree, or inferred a mechanism from a docblock without reading the code. Real
examples from one project:

- A prompt to "add the missing conversion action" — both directions already existed and were wired.
- A prompt warning "do not write a second PIN pad" — there were already two, character-identical.
- A prompt asserting a health check could never fail — it worked fine; the author's Composer install had
  silently fallen back to a source checkout that carried files the published package strips.
- A prompt saying "reuse the existing OCR path" — the parser took already-OCR'd *text*, and the only OCR
  was a shell-out to a binary declared in no manifest and installed nowhere.

So the standing instruction to whoever runs the prompt:

- Read the files the prompt names and confirm the defect is still there.
- **Already fixed** → say so and stop. Do not build something redundant to satisfy a prompt.
- **Partly fixed** → build only the remainder, and record which half already existed.
- **Reasoning wrong rather than stale** → say that, with evidence, and do not build it.

Report the discrepancy either way. **Silent compliance and silent deviation are both worse than saying it.**

**A half-wrong premise is normal, not a failure.** Over a long run, the pattern was: the defect is real,
the mechanism the author named is wrong in one detail — "EN's longer strings tip it over" (EN was shorter;
the window height did it), "the handover route works because Livewire is there" (it shared the dead
route). The prompt still earned its fix. Say which half was wrong, in `DECISIONS.md`, and move on.

### Stamp the premise with the commit you verified it on — and say what you ruled out

Every "Why this exists" carries `Verified on origin/main = <sha>`. A finding without its commit cannot be
re-checked, and a session that inherits the prompt weeks later cannot tell whether it is stale. Add the
hypothesis you **disproved** while verifying ("the fee was innocent — reproduced with a clean member"):
it is one line for you and an afternoon saved for the implementer.

### Verify by doing, on the current build

A control is verified when it has been **pressed** on the build under review and the result observed.
"The template says it is a span" was true on the tree the reviewer read; the merge since had made it a
button, and it opened two PIN pads. If your evidence is a code read, say so — and treat the verdict as a
hypothesis until someone clicks it. (`false-green.md` #5.)

### When the prompt's rules and its tests disagree, the tests are the spec

A prompt said *"no change to `settleOrder`"* and, four sections later, required a voucher-only order to
settle — which that method refused outright. Rules are written to protect; tests are written to prove the
outcome. When they collide, the outcome wins: name the contradiction, take the route the architecture
already names (in that case the writer the sibling screen used), and record it. Never quietly weaken the
test to fit the rule, and never quietly bend the rule without saying so.

### When the prompt's own rules contradict each other, name it and take the stronger constraint

Prompts get written in one pass and rules accumulate. One said *"do not touch this file"* four paragraphs
after saying *"do not duplicate the rule that lives in it"* — and the rule lived in that file. There was no
way to satisfy both.

The right move is not to pick silently. Name the conflict, work out which constraint costs more to violate,
take that one, and record it. In that case duplication was the more expensive violation, so the shared
value was extracted and the protected file's *behaviour* — not its bytes — was asserted unchanged.

### A prompt's recommendation does not outrank a recorded decision

If a prompt tells you to do something that reverses a decision already in `DECISIONS.md`, that is usually a
sign the prompt's author did not know the decision existed. Build it if you like — but measure it, and if
existing tests encode the old decision, read what those tests say before re-pointing them. One prompt
recommended honouring a browser language hint; three tests carried a comment explaining why that exact
question had been decided the other way for that exact audience. Building it and removing it on the
measurement was the right sequence.

### Escalate the decisions that are not yours

Some questions look like defects and are actually the owner's to answer: whether an unauthenticated form
may accept identity documents, whether staff may create accounts, how long to retain data for people who
never became customers. An agent that answers these silently — in either direction — has made a policy
decision on the owner's behalf.

**Write the escalation into `DECISIONS.md` with the options and the spec for each**, then stop. When the
owner answers, the prompt writes itself and the reasoning is already recorded.

Mark agent-chosen defaults so they can be found later:

```php
// users.create is deliberately NOT here: adding an account is the owner's decision …
// A site that wants staff to create accounts grants it back.
// See DECISIONS (OVERNIGHT-DEFAULT — CONFIRM).
```

That marker is worth its weight. It is how a human finds every place an agent guessed.

### Ask for a structural guard, not a comment

The most valuable half of a bug-fix prompt is usually not the fix. When you find one instance of a class of
bug, ask for a test that walks the whole class:

- *"A test that finds every model with an object cast and asserts its edit page handles the fill"* — catches
  the next one nobody revisits.
- *"Assert by enumeration that exactly one PIN pad exists in the codebase"* — the duplicate cannot come back.
- *"Assert every upload on the private disk carries the shared size limit"* — a new field added without one
  fails the suite.
- *"Assert the actions column cannot grow past the viewport"* — the next feature that adds a row action
  cannot silently break the table.

Prefer that to a comment telling people to remember. Say so explicitly in the prompt: **prove the guard
guards** — make it fail on purpose before trusting it.

### Say what must NOT change

Half of a good prompt is its Rules section, and most of that is prohibitions. Name the single writers, the
resolvers, the encryption path, the audit trail. *"No change to X"* is the cheapest way to keep a
presentation fix from becoming a data-integrity incident.

The sharpest version: **a gate must not become a picture of a gate.** When a prompt restyles something that
blocks an action, require a test that the server still refuses when the new screen is bypassed.

### Require at least one test that fails against current main

If every test in the branch passes before the change, the branch has not been shown to do anything. Name
the one that must go red first: *"Write it so it fails against current `main` — that is the regression
test."*

### One source per figure

If a number appears on two screens, it comes from one resolver, and the test asserts them equal to each
other rather than to a hard-coded expectation. A second read model is how a customer is told they have £12 of
credit left and then refused at checkout.

### Push, do not merge

End every prompt with it. The human reviews, then merges. The exception is an authorised unattended run —
and then merging each branch on green becomes *required*, not optional, because prompts that all append to
`DECISIONS.md` and shared translation files cannot be built beside each other without colliding.

### A prompt is a file on disk, with a number that is never reused

A session compacted its context and lost two prompts that existed only in chat; it correctly refused to
reconstruct them from memory. Every prompt is a numbered file delivered as a file. When a prompt is
superseded, the running order **kills the old number by name** ("204 withdrawn — 205 replaces it") rather
than reusing it, so a session resuming from an older list cannot build the dead one.

### Urgent first, and say why

When one queued prompt fixes something that stops a real person (an applicant who cannot complete the
form; a guard that never fires), it goes to the front and the prompt says so in its first line. A queue
built in the order the findings arrived is not a priority.

### Report-only audits stay report-only

An audit prompt that "fixes a few things while it is there" produces unreviewable diffs. Report, propose
each follow-up as a numbered prompt with a one-line scope, and change nothing — including dependency
bumps, which get their own branch and their own gate.

### The tester's build is the premise

Before a tester's report becomes prompts, establish which commit they tested on. One round's "doesn't
work" list was half fixes merged weeks earlier and never deployed; triaging them against `main` would
have produced prompts for defects that no longer existed. See `verification/tester-feedback-triage.md`.

---

## Size and splitting

**If a prompt has three jobs, it has three prompts.** One that bundled "standardise this pattern", "apply
it across five screens" and "redesign this screen" could not be landed in a single run — the session built
the shared pieces, hit its limits and stopped, correctly, with nothing merged. Split on the seam where the
*decisions* differ: each prompt should have one thing to record in `DECISIONS.md`.

Signals a prompt is too big: more than one "decide it and record why"; a Rules section protecting more than
about six things; a Tests section over roughly a dozen assertions; two unrelated screens.

Give the implementer permission to land less rather than half: *"If context runs short, wire fewer screens
completely rather than all of them partially, and say which are done."*

---

## When the build deviates: the gap report

If a branch ends up differing from its prompt — and good ones often do — require a **gap report** before
merge, in three parts:

1. What the prompt required that you did **not** do.
2. What you did that the prompt **forbids**.
3. What you did that the prompt **does not mention at all**.

The third is where the value is. It surfaces the scope creep and the incidental fixes that would otherwise
land unreviewed, and it is where the best findings of a build tend to show up.

---

## Measurement is only as good as the tree it was taken on

Findings reported from a working copy have a specific failure mode: the environment, not the code. Four
separate false alarms in one project, all environmental —

- a stale `vendor/` from an old install,
- a Composer **source** checkout carrying files the published **dist** archive strips,
- a stale asset build, so a utility class was missing and every control depending on it measured wrong,
- a page reconstructed for measurement with a stylesheet inlined that corrupted the cascade.

So, before reporting anything measured:

- **Dependencies** — confirm the install matches the lockfile, and the install *method* matches the
  project's (`preferred-install`). A source checkout is not what production has.
- **Assets** — rebuild before any browser measurement. A gitignored build directory silently measures an
  old commit.
- **The page** — measure the real page with its real layout parameters, not a harness approximation.

And write the caveat into the report: *this number is only as good as the build it was taken on.*

### … and only as good as the browser it was taken in

The second family, found a month later: the build was right and the **instrument** was wrong.

- Snapshot pages on `file://` could not load the app's web font, so every text measurement was in the
  runner's fallback — green on one machine, red on another, neither the app's number.
- Headless browsers have no collapsing toolbar, so `100vh` equalled the visible height and a shell that
  hid its commit button under Android Chrome's address bar passed every geometry harness.
- A middleware's tests seeded the session in-process; the real request loaded it later than the
  middleware ran. Green suite, feature that never fired.
- A no-JavaScript snapshot measured a step 44px short (a trigger hidden until its module reveals it).

So, for anything measured in a browser: load the real font and assert it loaded; measure at the device's
own reported viewport, toolbar showing; set state through real requests; measure through the runtime.
`false-green.md` catalogues each with its guard; `verification/real-device-checks.md` is what a headless
browser cannot see and a real tablet can.

---

## A prompt is not a spec

The best prompts in that project read like a colleague explaining what they found and why it matters, then
saying what they would do and where they are unsure. They argue. They admit when a call could go either
way. They say *"my view is X — decide it and record the reasoning."*

The worst read like tickets: a list of changes with no evidence and no reasoning, which produce exactly
what they describe and nothing better. If the implementer cannot tell **why** a rule is there, they cannot
tell when it does not apply — and that is when they either break something or build the wrong thing
obediently.
