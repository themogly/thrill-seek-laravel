# 014 — Prices typed into CMS copy go stale the moment a product price changes

One branch, one task. Read `CLAUDE.md` (money is integer pence, rendered through the `Money` presenter;
"one reader per figure"), `DECISIONS.md` and `ui-review/CONSISTENCY.md` C-7. **Size: medium.**

`git checkout main && git pull` → `git checkout -b feat/price-tokens-in-copy`.

> **Why this exists.** Consistency audit C-7, which Ben agreed is worth fixing before launch. Every
> product-driven price goes through `Money` and is consistent. These are typed by hand into CMS text, so
> changing a product price leaves them saying the old one:
> - **Tandem FAQ answers:** weight bands £20/£40/£60, camera £140/£100, rebooking £50, sponsorship £260
>   (`FaqSeeder`).
> - **AFF FAQ:** £1,750/£600, £125 membership, £5 kit.
> - **Tandem hero subtitle** "from £260" (`TandemPageSettings::hero_subtitle`).
> - **Coached eyebrow** "From £60 per session".
> - **AFF repeat-jump price card** "£210/£140 per jump".
> - **Terms:** £24.73, £50.
>
> **Ruled out:** making every one of them a token. Only prices that *are* a product's price or deposit can
> come from a product. Weight surcharges, the £125 membership, kit hire and the repeat-jump prices may not
> exist as data at all. Find out which do.

## Build

1. **Inventory first.** Write a table in DECISIONS with one row per hand-typed price found in CMS text, seeders
   and settings: where it is, its value, and whether a model value it should equal exists (`Product` price or
   deposit, an add-on, …).
2. **Tokens for the ones that exist.** A small, safe token syntax in CMS text, e.g. `{price:tandem}` and
   `{deposit:aff}`, rendered through `Money` from the product's current value. Decide the syntax and record
   why. Requirements:
   - an unknown token renders visibly in the admin preview but **never** shows raw on the public site. Decide
     the public fallback and record it;
   - it works wherever those fields render (page, FAQ accordion, the FAQPage JSON-LD). The JSON-LD answer must
     carry the rendered price, not the token;
   - it's resolved at render time, and the cached `SiteContent` arrays stay plain arrays.
3. **Replace the matching literals** in the seeders and settings migrations with tokens, so a fresh install
   is right. Existing databases are owner content: give Ben the list.
4. **The ones with no backing data** (surcharges, membership, kit): don't invent models. List them as owner
   content, and add a Help guide note: "these prices are typed; update them when they change".
5. Help guide: how to use the tokens.

## Rules

- No change to how any product price is stored, charged or displayed elsewhere.
- No new money columns. If a price "should" be data, escalate it (`OWNER DECISION — PENDING`).
- The Coached eyebrow wording is part of an open owner question (C-1). Tokenise the price only.

## Tests

- Changing the Tandem product price changes the FAQ answer, the hero subtitle and the FAQPage JSON-LD.
  Red on current `main`.
- An unknown token never reaches public HTML.
- The token output equals the product page's displayed price (one reader per figure).

## Finish

`composer check` green. DECISIONS entry with the inventory. Push the branch. **Do not merge** (unless on an
authorised unattended run).
