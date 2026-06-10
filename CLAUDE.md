# Project: thrill-seek-laravel

## What this is
A Laravel app: a Blade/Tailwind frontend ported from a Lovable React app, now being made
content-manageable via a Filament admin panel.
- Source app (reference only, do not modify): `../thrill-seek-co` (Vite + React + TS + Tailwind + shadcn/ui).
- This app: Laravel 12 + Blade + Tailwind + Alpine.js + Livewire, with **Filament v5** for the admin panel.
- The frontend port is DONE and verified. Do not redesign it — preserve the existing look and animations.

## Current phase: make content manageable (CMS)
Goal: turn hardcoded Blade content into database-backed records editable through a Filament admin panel at `/admin`. Do not change the visual design while doing this.

### Step 1 — Content audit (do this first, report before coding)
Go through every page and list which content should be editable vs. stay static. Group editable content into logical models (e.g. tours/activities, testimonials, gallery images, FAQs, hero/section copy, site settings). Propose a schema for each (fields + types) and wait for approval before creating migrations.

### Step 2 — Data layer
For each approved content type: create a migration + Eloquent model. Add an ordering column where display order matters. Seed with the *current* static content so pages look identical after the refactor.

### Step 3 — Filament resources
One Filament resource per model (`make:filament-resource X --generate`). Configure forms (correct field types, image uploads via FileUpload, rich text where needed), table columns, and reordering. Keep the admin UX simple and labelled for a non-technical editor.

### Step 4 — Refactor Blade to be dynamic
Replace hardcoded content in the Blade views with data from the models (controllers pass the records; views loop over them). The rendered output must match the current static pages exactly. Preserve all Tailwind classes and animations.

## Constraints
- Do not alter the frontend design, layout, or animations — only swap static content for dynamic data.
- Filament bundles Livewire/Alpine/Tailwind; don't reconfigure the existing frontend build to accommodate it.
- Keep auth limited to the admin panel; no public-facing accounts unless asked.

## Workflow expectations
- Before each step, produce a short plan and wait for approval.
- Work one content type at a time end-to-end (model -> resource -> Blade refactor) so each is reviewable.
- After each content type, confirm the public page still looks pixel-identical to before.
