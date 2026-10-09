# False greens — the catalogue

A false green is a check that passes while the thing it claims to prove is broken. Every entry below was
found in a real project **after** the suite, the harness or the reviewer had said it was fine. They are
grouped by *why* the check could not see the defect, because the group tells you what guard closes the
class. Copy this file beside `prompts/` in every repo; add to it whenever a green turns out to be false.

The single rule under all of them: **a check proves only what it actually exercised, in the environment it
actually ran in.** Before trusting a green, ask *what did this touch, and where did it run?*

---

## A. The check exercised the wrong thing

**1. The method, not the path.** A test called the component's `commit()` and passed; the button that
real users press dispatched `$wire.commit`, which Livewire resolves to its own `$commit` alias before any
component method — so the click never reached the method at all. *Guard:* browser tests press the control;
a structural test parses the vendored alias map and fails on any public method that shadows one.

**2. Counting nothing.** `assertSee('Saved')` passed while the flash rendered **twice**. `assertSee` proves
presence, never count. *Guard:* assert the count of the element, and announce-once (`aria-live`) exactly
once.

**3. The sample of one.** A geometry harness measured one control and printed ALL PASS for the screen.
*Guard:* iterate every interactive element; print the count measured beside the verdict.

**4. Testing the picture, not the gate.** A restyled blocking state was asserted by its markup; the server
still accepted the bypassed write. *Guard:* every gate has a test that posts around the screen and is
refused.

**5. Verified by reading.** A reviewer judged a control "does nothing" from a code read; a merge since had
made it a button, and pressing it opened two PIN pads. *Guard:* a UI verdict requires the control pressed
on the build under review. "The code says" is a hypothesis.

## B. The check ran somewhere the defect does not exist

**6. In-process state vs the real request.** A middleware read the session; its tests seeded the session
in-process before `$this->get()`, so it saw the value. On a real request it ran **before** `StartSession`
and read `null` every time — the feature never fired in production. *Guard:* session-reading middleware
registers on the `web` group after `StartSession` (a structural test over the registration), and redirect
tests set state through a *prior request*, not `withSession()`.

**7. The runtime that was never shipped.** A component's Alpine directives were correct and inside an
`x-data` root — and the page loaded no Alpine. Dead markup; a pad that could not capture; a route no one
could complete. *Guard:* a browser assertion that `window.Alpine` exists on every page that renders
`x-data`, and a structural test tying each such view to a layout that ships the runtime.

**8. The font the app never renders.** Snapshot harnesses on `file://` pages could not load the self-hosted
woff2, so every text measurement used the runner's fallback font — green on a Mac, 3px red on Linux, and
neither number the app's. *Guard:* snapshots load the real font; harnesses assert `document.fonts.check()`
and count faces *in error* before measuring (`fonts.ready` resolves on failure too).

**9. The browser with no toolbar.** A shell sized `100vh` pinned the commit under Android Chrome's
address bar; headless has no toolbar, so `100vh` equalled the visible height and every geometry harness
passed truthfully. *Guard:* `svh` for non-scrolling shells, a structural ban on `h-screen`/`100vh` heights,
and the device's real viewport in the harness matrix — plus a photo from the device recorded as evidence
Playwright cannot produce.

**10. The snapshot with no JavaScript.** A static-page harness reported a step 44px shorter than reality
(a trigger hidden until its module reveals it) and another 160px taller (a canvas at its intrinsic ratio
before `init()` sized it). *Guard:* measure through the runtime, as the components do; if a snapshot must
be static, assert it shows what the live page shows before trusting its numbers.

**11. The deploy gap.** A tester's "doesn't work" list was half fixes that had merged weeks earlier and
never reached the server. *Guard:* triage against the build the tester saw (`git log -1` on the server, a
version string in the footer) before touching anything.

**12. The environment that is not production.** A Composer *source* checkout carried files the *dist*
archive strips (a monorepo's extra adapters), so a "not installed" assertion failed; a stale asset build
measured an old commit. *Guard:* confirm the install method matches the project's, rebuild assets before
measuring, and write "only as good as the build it ran on" into every report.

## C. The check watched a signal that does not mean what it says

**13. The dead event name.** A module mounted on `livewire:update` — which the dist never dispatches. It
had never fired anywhere; the feature worked on one page by accident (`DOMContentLoaded`). *Guard:* an
event name a module listens for is verified against the vendored dist (grep it), and the browser harness
proves the listener answers.

**14. Completion is not success.** `document.fonts.ready` resolves when loading *finishes*, including
finishing in error. A thank-you page appeared after a submit the spam guard had silently discarded (too
fast to be human). *Guard:* assert the outcome (a face `loaded`; the record re-read by its own key), never
the signal that the process ended.

**15. The exact fit.** Content measured 506px in a 506px region and passed. Zero margin is a coin toss on
the next font, locale or window. *Guard:* fit assertions carry a minimum headroom; a fit of 0 is a
failure.

## D. The check covered the wrong states

**16. State coverage.** A 44px-floor sweep passed because every snapshot carried a member *with* a photo,
so the photo nag — collapsed to a zero-width column of glyphs, with two under-floor buttons — never
rendered on any measured page. *Guard:* harness states enumerate the conditions that change what renders;
when a fix adds a branch, the harness gains its state.

**17. The shared piece with one consumer.** A concern, partial or helper extracted "so it exists once" was
wired to one of its three intended consumers — five times in one project. *Guard:* a consumers test that
iterates the consumers rather than listing them, so the third call site cannot hand-roll a fourth copy.

**18. Two computations of one figure.** The order preview split the goods total; checkout split goods
plus extras. Both correct, disagreeing on the same screen. *Guard:* one resolver per figure; tests assert two
displays equal *each other*, not a literal.

## E. The check inherited a stale premise

**19. Install-only matrices.** Permissions were seeded idempotently — but only by `app:install`, so every
installed database kept its install-day matrix while the code's declaration moved on. *Guard:* a
`--check` command that diffs the live matrix against the code's, run by the deploy script; revoke
direction is the dangerous one.

**20. Stale remedy copy.** A refusal told an *owner* to "ask a manager". *Guard:* remedies are computed
from the actor's actual permissions, and a test iterates roles × rules.

**21. The prompt whose rules contradict its tests.** "Do not touch `settleOrder`" beside "a voucher-only
order must settle" — which the server refused outright. *Rule:* the tests are the spec; name the
contradiction and record the resolution in `DECISIONS.md`. (See `prompts/writing-prompts.md`.)

**22. A prompt that exists only in chat.** A session lost two prompts mid-compaction and correctly
refused to reconstruct them. *Guard:* every prompt is a file on disk with a number; superseded numbers are
killed explicitly in the running order.

## F. The instrument that lies to itself

**23. Alpine-inserted DOM inside a Livewire morph.** A PIN card in an `x-if` template was cloned across a
morph — the old card survived, a new one was inserted, two pads. *Guard:* no `x-if` (and no `@vite`/module
script — see 13's cousin) inside Livewire-rendered views; `x-show` on always-present markup instead. A
structural test names the file and line.

**24. Shared browser state.** One Playwright `storageState` shared one Laravel session, so the first
context's handover trapped every other; five logins tripped the login throttle. *Guard:* one context per
scenario; the sign-in helper knows the throttle.

**25. Colour parsed from the wrong syntax.** Contrast scripts parsed `oklch(...)`/`oklab(... / .1)` with an
`rgb()` regex and read an alpha as a red channel — 1.10:1 and 2.96:1 for lines that were fine. *Guard:*
paint the colour to a 1×1 canvas and read the pixel back; measure what the browser paints, whatever the
stylesheet wrote.

## G. The email that was only ever promised

**26. The row, not the email.** A test named *"…sends the real invitation and confirms it"* asserted that the
invitation row existed and that the "sent" flag was true. It never faked or asserted mail — and the counter
path it covered had no send at all, while the screen told staff *"Invitation sent"*. *Guard:* every test
whose name says "sends" asserts `Mail::assertQueued` for the recipient; an inventory test pairs every
"sent" string in the UI with the mailable that makes it true (`audits/email-audit.md`).

**27. Two doors, one send.** The same act — approving an application — could happen from the admin panel or
the counter. The panel's *caller* queued the "approved" email after calling the shared action; the counter
called the same action and didn't. Same writer, different emails, depending on the screen. *Guard:* sends
live inside the single action both doors use, never in a caller; the audit walks every sibling path.

**28. Queued is not sent.** "Email queued" is a true statement about Redis, not about an inbox. A runbook
told the operator to set `RESEND_KEY` while `config/services.php` read `RESEND_API_KEY`; with that, every mail
dies in the worker, `tries => 1` means no retry, and the only trace is a `failed_jobs` row nobody reads —
while every screen stays green. *Guard:* a synchronous mail-test command run on the real box, a health row
that flags an empty provider key, per-mailable retries, and a `failed()` hook that lands somewhere the owner
looks.

---

## How to use this file

- **When writing a prompt**, name the shapes the fix must guard against; ask for the guard, and for the
  guard to be proven by planting a violation.
- **When a green surprises you**, find its shape here before arguing with it. If it is new, add it — with
  the guard that would have caught it, not just the story.
- **When triaging a tester's report**, walk shapes 5, 9, 10, 11 and 16 first: doing-not-reading, the
  device, the runtime, the build, the state. For "the email never came", walk 26–28.
