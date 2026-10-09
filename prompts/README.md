# Prompts

The layer the rest of the kit assumes but never described: **how the day-to-day work actually gets
specified.**

The bootstrap sets the standards. The skills shape code as it is written. The audits verify afterwards. In
between all of that, someone has to say *"here is what is wrong, here is the evidence, here is what to do
and what not to touch"* — one prompt at a time, one branch at a time. Getting that right is most of the
difference between a build that stays clean and one that drifts.

## The files

- **`writing-prompts.md`** — the anatomy of a task prompt and the rules that earned their place. Read this
  before writing the first one. It is written for whoever *authors* prompts, whether that is a human or a
  second agent with the repo in front of it.
- **`running-order.md`** — a template for the sequencing document, for when more than a couple of prompts
  are queued. Includes the protocol a session should be handed before the prompts themselves, and the
  unattended-run variant.

## How this fits the other layers

| layer | when it acts | changes code? |
|---|---|---|
| **skills** | as code is written (ambient) | shapes it |
| **CLAUDE.md** | always; wins over skills | is the rules |
| **prompts** | per task | yes — one branch each |
| **audits** | periodically, report-first | yes, after the report |
| **gates** | near launch | never |
| **verification** | at launch, by a human | never |

A prompt outranks a skill for the specific job in front of it, and `CLAUDE.md` outranks both. A prompt that
appears to contradict `CLAUDE.md` or a recorded decision is a sign its author did not know the rule existed
— surface it rather than following it.

## The two habits worth stealing above all others

**Verify the premise before building.** Roughly one prompt in ten describes a defect that is already fixed,
half-fixed, or was never there. Reading the code first catches all of them; obedience catches none. This
costs minutes and has repeatedly saved days.

**Ask for a structural guard, not a comment.** When you fix one instance of a class of bug, add the test
that walks the whole class. It is usually the more valuable half of the branch, and it is the only thing
that stops the same bug arriving again through a door nobody was watching.
