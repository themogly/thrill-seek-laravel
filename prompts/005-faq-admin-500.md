# 005 — The admin FAQ list crashes whenever a FAQ exists (URGENT)

**Urgent: the owner can't manage FAQs at all.** One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the
002 structural-guards entry) and `audits/reports/completeness-check.md` #1. **Size: small.**

`git checkout main && git pull` → `git checkout -b fix/faq-scope-collision`.

> **Why this exists.** The completeness gate (unattended run 1) loaded `/admin/faqs` as the owner and got a
> 500. **Verified on `origin/main` = `7e01bfe` by chat-Claude reading the code (not run):**
> `App\Models\Faq::scopeForPage(Builder $query, FaqPage $page)` (`app/Models/Faq.php:61`) has the same name
> as the query builder's `forPage($page, $perPage)`. Filament's paginator calls `forPage(1, 10)`, which is
> routed to the scope, and that throws `TypeError … FaqPage, int given`. The only caller is
> `SiteContent.php:102`.
>
> **Ruled out:** a Filament bug. `Faq::query()->paginate(10)` throws on its own with the 19 seeded FAQs.
> The gate also scanned every model and found this is the only scope that collides with a builder method.
>
> **Why the suite is green:** `FaqAdminTest::test_admin_can_list_and_create_faqs` lists an *empty* table, and
> the paginator never calls `forPage()` with no rows. That's a false green: the test doesn't share the real
> state (`false-green.md`).
>
> Confirm all of this on current `main` first (004 may have landed). Already fixed → say so and stop.

## Build

1. Rename the scope, e.g. `scopeOnPage`. Decide the name, record why, and update the one caller.
2. Make `FaqAdminTest` list the table **with FAQs present**. Run it against current `main` first and see it go
   red.
3. **Structural guard:** a test that reflects every model in `app/Models`, collects its `scope*` methods, and
   fails if any scope name (lcfirst, without `scope`) is a public method on `Illuminate\Database\Eloquent\Builder`
   or `Illuminate\Database\Query\Builder`. Prove it: plant a `scopeWhere`-style collision, see it red, remove
   it.

## Rules

- No change to which FAQs appear on which public page. `SiteContent::faqs()` output must be identical:
  assert it for each `FaqPage` case, before and after.
- No change to the FAQ cache keys or their busting.

## Tests

- The re-pointed `FaqAdminTest` (red on current `main`).
- The scope-collision guard (proven by its planted violation).
- The `SiteContent::faqs()` equality check per page.

## Finish

`composer check` green. DECISIONS entry. Push the branch. **Do not merge** (unless on an authorised
unattended run). Gap report if you deviated.
