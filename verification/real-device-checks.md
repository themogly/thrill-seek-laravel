# Real-device checks — what a laptop, a headless browser and a code read each cannot see

Read this before calling any screen "done", and again before handing a build to a tester.

## Why this file exists

In one week of tablet testing on a project that already had 1,800 tests and forty browser harnesses,
five defects were found that **every automated check had passed**, and the reviewer (an AI reading the
code and driving a desktop-sized headless browser) had called three of them "already works". Each one
was invisible for a specific, nameable reason:

| Defect | Why nothing caught it | Who could have |
|---|---|---|
| The commit button sat under Android Chrome's toolbar | `100vh` is the toolbar-*hidden* height on mobile; headless has no toolbar, so `100vh` equals the visible height there | Only a real phone or tablet |
| Two PIN pads after "change person" | The control had become clickable in a recent merge; the reviewer judged it from a **code read** of an older version | Anyone who **clicked it** on the current build |
| The admin's way back was an unlabelled icon | The label is hidden below the `xl` breakpoint; the reviewer checked at desktop width where labels show | Anyone at the tablet's **portrait width** |
| Geometry harness red on Linux, green on Mac | Snapshot pages loaded no web font; each machine measured its own fallback | A run on a second machine, or a font assertion |
| A "no till → redirect" guard that never fired | Its tests seeded the session in-process; the real app loads the session later than the middleware runs | A test that went through **two real requests**, or one click on the app |

Two families: **verified by reading instead of doing**, and **verified in a browser that is not the
device**. The rest of this file is the antidote to each.

## Rule 1 — Verified by doing, on the current build

- A control is verified when it has been **pressed** on the build being reviewed and the result observed.
  "The code says it's a span" is a hypothesis; the merge after last week's read may have made it a button.
- Before triaging any tester report, establish **which build the tester was on** (`git log -1` on the server,
  or a version string in the footer). Half of one round's "doesn't work" reports were fixes that had merged
  weeks earlier and never deployed. Triage against the build they saw, then say what the current build does.
- Every state, not the happy path: blocked member, no till, no photo, second device, expired session,
  session-with-toolbar. State coverage — not assertions — is where most greens go false.

## Rule 2 — The device's own numbers

Record, per device, with the browser toolbar **showing**, both orientations:

```
window.innerWidth × window.innerHeight        (the CSS viewport the app actually gets)
window.devicePixelRatio                      (1920×1200 panel at DPR 1.5 → 1280×800 CSS)
document.fonts.check('16px Inter')           (is the app's font actually rendering)
'BarcodeDetector' in window                  (camera scanning possible on this browser)
navigator.mediaDevices !== undefined         (HTTPS — camera and mic exist at all)
```

Add those viewports to the harness matrix. A layout "optimised for tablets" measured only at an iPad-shaped
1180×820 is optimised for an iPad.

## Rule 3 — Know what each instrument is blind to

| Instrument | Sees | Blind to |
|---|---|---|
| Unit / feature tests | Logic, permissions, writes | Anything rendered; middleware order vs in-process state; morph behaviour |
| Headless browser (Playwright) | DOM, geometry, JS behaviour, real requests | Mobile toolbar (`100vh`), system fonts vs web fonts on file:// pages, on-screen keyboard, camera, autofill, touch vs hover, DPR, the back gesture |
| Desktop browser, resized | Most layout | Toolbar, keyboard, camera capture, DPR, touch targets under a finger |
| The real device | Everything the user sees | Nothing — but it needs a way to read the console (below) |

When a harness passes, ask which column it lives in and what that column cannot see.

## Rule 4 — The device-only checklist

Run on the real tablet, on the real URL (HTTPS — camera and scanner do not exist on `http://`), after every
deploy that touched the counter:

- [ ] **Toolbar**: every pinned control (commit, charge, next, save) visible with the browser bar showing, both orientations.
- [ ] **On-screen keyboard**: tap the *lowest* input on every form — does it scroll into view above the keyboard, or is it covered?
- [ ] **Camera**: photo capture opens the camera directly (`capture="user"` / `"environment"`), the QR scanner's button is present and scans a phone-screen QR at arm's length (fixed-focus cameras fail here — test with a printed code to separate optics from software).
- [ ] **Autofill**: staff forms offer nothing from the operator's own saved details; applicant forms offer the applicant's.
- [ ] **Touch**: every control ≥ 44×44 CSS px *and* reachable by a finger (not just a mouse); no hover-only affordances.
- [ ] **Orientation**: rotate mid-task; nothing lost, nothing hidden.
- [ ] **Android back gesture**: swiping back from the POS mid-basket — where does it go, and is the basket still there?
- [ ] **Session**: leave the tablet idle past the session lifetime — does it come back to the PIN pad or an email form?
- [ ] **Second device**: two tablets at one sede — open the till on one, watch the other.
- [ ] **Font**: `document.fonts.check` true on the live URL; if the app self-hosts a font, confirm the woff2 actually loads over the network (check the Network tab once).
- [ ] **Deploy gap**: `git log -1` on the server matches what you think you're testing.

## Rule 5 — Read the tablet's console from a laptop

USB-debug the tablet once (Developer options → USB debugging), plug it into a laptop running Chrome, open
`chrome://inspect#devices`, and you get the tablet's DOM, console and network in desktop DevTools — the real
device with a real instrument. Every "it just does nothing" report becomes a stack trace. Keep a cable at
the counter during test weeks.

## Rule 6 — The tester's report is data, the build is context

A tester's list is the most valuable input the project gets and the easiest to mis-triage. Use the triage
template (`verification/tester-feedback-triage.md`): one verdict per item from a fixed set, the build they
were on, and — for every "already works" — the **evidence you clicked**, not the file you read.
