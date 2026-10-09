# Tester feedback triage — the template

A tester's list is the best input a project gets and the easiest to mis-triage. Use this for every round,
and publish the result back to the tester in their language, not the project's.

## Before triaging a single item

1. **Which build were they on?** `git log -1` on the server (or the footer version string). Compare with
   `main`. Every item is judged against the build they saw; the verdict says what the current build does.
2. **Which device, browser, orientation, URL?** Half of UI reports are viewport-specific (labels hidden
   below a breakpoint, toolbars, keyboards). Ask for a screenshot with the clock visible.
3. **Reproduce on the current build in a real browser** — by pressing the control, not by reading the
   file. A verdict of "already works" needs the evidence you clicked; see `false-green.md` #5.

## The verdicts (pick exactly one per item)

| Verdict | Meaning | Must include |
|---|---|---|
| **Bug — being fixed** | Reproduced on current `main` | The prompt number that fixes it |
| **Already works** | Verified on current `main` by doing it | What you did, where; why they may not have seen it |
| **Fixed — not yet deployed** | Fixed on `main` after the build they tested | The merge; "retest after deploy" |
| **Needs your input** | Ambiguous, or a screenshot is needed | The exact question |
| **Owner to decide** | A policy or design choice, not a defect | The options, one line each |
| **Good idea — later** | Valid, not now | Where it is recorded |
| **Please retest** | Not reproducible on `main`; may be data/environment | What to note if it recurs (time, member number) |

## The report

One table: `#` · their note (verbatim) · verdict · what's going on (two or three plain sentences, no
prompt numbers unless the reader wants them) — then **what happens next** (deploy, fixes in order,
decisions pending, what you need from them) and a **retest checklist** for after the next deploy, one
line per behaviour, phrased as what they should see.

## Rules that earned their place

- **Their "doesn't work" is data; the build is context.** Never answer "it works" without saying on which
  build, and never assume theirs is `main`.
- **A wrong "already works" costs more than a wrong "bug".** When unsure, reproduce; when you cannot
  reproduce, say so and ask for the repro, do not declare it fine.
- **Separate what they saw from what they want.** "Category field should be removed" is a screenshot of
  an empty select next to a similar field — the fix may be hide-when-empty, not removal.
- **Their proposals go to the owner as decisions**, with your recommendation, not silently built or
  silently dropped.
- **Round two corrects round one.** Expect a tester to push back; when they are right, say so in the
  report — it is what makes the next round honest.
