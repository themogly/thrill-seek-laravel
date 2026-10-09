# Running order — the template

When more than two or three prompts are queued, the sequence itself needs writing down. Otherwise the order
gets re-decided ad hoc, dependencies get discovered late, and a session picks up a prompt whose text it does
not have.

Copy this into the project as `RUNNING-ORDER.md`, keep it current, and hand it to a session **before** the
prompts.

---

```markdown
# Running order — from `<commit>`

Sequencing instruction, not a branch. It says which prompt to take next and why that position.

**Merged:** <ranges>. **`main` at `<sha>`**, clean, `composer check` green.
**Built, awaiting merge:** <prompt> — `<branch>` at `<sha>`.
**Dropped / withdrawn:** <numbers> — see the notes at the end.

## The order

| # | prompt | why here |
|---|---|---|
| 1 | **NNN** — short name | The reason for this position, not a description of the work. |

## Hard constraints

Not preferences. Violating one means rework.

- **A before B** — because <the actual conflict or dependency>.
- Everything else in the queue is independent.

## Protocol

**No prompt text, no work.** If you are asked to do a numbered prompt and do not have its full text in
front of you, **stop and ask for it**. Never reconstruct one from this file, a design note, `DECISIONS.md`
or a WIP branch, however much material appears to be available. Ask for every prompt you do not have, in
one go, before starting.

**One branch, one task.** If a prompt contains two unrelated fixes, split it and say so.

**Ask before merging** — except on an authorised unattended run, where merging each branch on green is
*required*: prompts that all append to `DECISIONS.md` and shared translation files cannot be built beside
each other without colliding.

**Tests:** <which suite runs locally, and where production-parity is proven>.

**Rebuild assets before any browser measurement.** A gitignored build directory silently measures an old
commit.

**Stop at a clean point rather than half-finish a branch.** A half-built branch is worse than an unstarted
one — especially one carrying a security boundary.

## Verify the premise before you build

<the four-line rule from writing-prompts.md, plus this project's own examples as they accumulate —
concrete past misses teach it better than the abstract rule>

## Standing caveats

<Anything known to produce wrong findings in this project: environments that cannot reproduce production,
measurements that need a rebuild first, documents whose numbers have since been superseded.>

## Out of band

<Prompts that exist but are not queued: withdrawn by the owner, waiting on a decision, or gated on
something outside the codebase. Say WHY, so a future session does not helpfully pick them up.>

## Numbers, supersession and urgency

- **A number is never reused.** When a prompt is replaced, the replacement gets the next number and the old one is listed under *Withdrawn* by number and title — a session resuming from an older copy of this file must not be able to build the dead one. Renumber by re-issuing files, never by editing a number in chat.
- **Every prompt is a file.** Prompts that exist only in a chat transcript are lost on the first context compaction. Deliver files; list them here by filename.
- **Urgent goes to the front, and the reason is written here.** A guard that never fires, a route a real person cannot complete — those jump the queue; the running order says why so nobody "tidies" it back into numeric order.
- **New evidence mid-queue.** When verification of a merged prompt finds it did not do what it claimed (a green suite, a dead feature), the fix is a new prompt inserted ahead of everything that builds on the claim — and the entry names the merged prompt it corrects.

## Withdrawn prompts — do not build

<Any prompt whose premise turned out to be false, with the evidence. Recording the withdrawal is what stops
it being rediscovered and rebuilt.>
```

---

## Notes on keeping it useful

**The "why here" column is the point.** A list of prompt numbers is a queue; a list with reasons is
something a session can argue with when it finds the reason no longer holds — which happens, and is the
behaviour you want.

**Record withdrawals, not just deletions.** A prompt that was written and then found to be wrong should stay
in the file with its evidence. Otherwise the next session rediscovers the "problem" and helpfully fixes it.
The same goes for features the owner has decided against: *"written, and withdrawn by the owner — do not
pick this up"* saves an argument later.

**Put the caveats where they will be read.** If a document the prompts reference has numbers in it that
were later found wrong, say so here rather than trusting anyone to remember.

**Update it after every merge**, along with the commit. A running order describing a tree that no longer
exists is worse than none, because it is trusted.

---

## Unattended runs

Merging on green becomes required rather than optional, and the safety moves from permission prompts into
the instruction. State plainly:

- The exact prompts in scope, and that nothing else is.
- That merging each on green before the next is required, and why.
- The stop conditions — **a false premise, a structural decision the prompt has not already made, a red
  suite you did not cause, or a branch you cannot finish.** Stop, leave `main` clean, write a report.
- That landing one branch properly beats three half-built.

An unattended run will need permission prompts disabled to proceed at all, which removes the natural
checkpoints. That is a real trade and worth being honest about: the mitigation is the stop conditions and
the fact that the prompts carry the decisions, not a dialog box.
