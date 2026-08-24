# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

All 5 phases are done — 90/90 Pest tests + 9/9 Vitest tests passing. The full Operari flow and the Admin dashboard (with real seeded data, filters, chart table-view toggle) were both driven end-to-end in a real headless-Chromium browser with no console errors. There is no Fase 6 planned; further work is enhancement/maintenance on top of a complete spec, not phase-gated the way 1-5 were — still apply the golden rule (explain → wait for approval → implement) for anything nontrivial.

**Known real gap, partially closed, more to come:** there are still no Admin Vue pages to manage Section/Question/User/OrderFabrication/Equipment — only `Admin/ProjectIndex.vue` exists so far (built one resource at a time, per the user's explicit "pas a pas" request; don't build the rest speculatively, wait to be asked). The JSON `/api/*` endpoints for all of them already exist and work; only the UI is missing. When asked to build the next one, follow `ProjectIndex.vue`'s pattern: a table + a modal add/edit form, reusing the existing `/api/*` CRUD.

**Data model change: Project ↔ Section is many-to-many, not "all sections apply to every project."** The user clarified that Sections (and their Questions) are fixed global templates ("sempre son les mateixes"), but each Project only uses a subset of them (their example: a project with 3 of the available sections). This added a `project_section` pivot table + `Project::sections()` / `Section::projects()` (`belongsToMany`). Consequently `Operari\EquipmentController@show` now loads sections via `$equipment->project->sections()...` instead of `Section::all()` — an operari only ever sees the sections assigned to their equipment's project, not every section in the system. `Admin\ProjectController@store/update` accept `section_ids` and `sync()` them. When building the Section/Question admin pages next, remember Questions still belong only to a Section (not duplicated per project) — the per-project scoping happens one level up, at the Project↔Section pivot.

**Data model change: Project's `family` and `description` are no longer free-text strings — they're growable lookup catalogs.** The user clarified Família is a single choice per project (`Family` model, `projects.family_id` FK, required) and Descripció is multiple tags combined (`DescriptionTag` model, `project_description_tag` pivot — table name explicit on both `belongsToMany()` calls since Laravel's alphabetical-default `description_tag_project` doesn't match). Both are meant to grow over time ("s'ha de fer un formulari per anar afegint"), not a fixed hardcoded list — `Admin/ProjectIndex.vue`'s form has an inline "+" add-new control for each, backed by `Admin\FamilyController`/`Admin\DescriptionTagController` (`index`/`store`/`destroy` only, at `/api/families` and `/api/description-tags`). `FamilyController@destroy` blocks deleting a family still in use by a project (422, not a DB constraint crash); `DescriptionTagController@destroy` just detaches silently since the pivot cascades. The real known catalog values the user gave (`FamilySeeder`, `DescriptionTagSeeder`) are partial lists ("no tota la llista") — expect more to be added ad hoc through the UI, don't treat them as exhaustive. `ProjectFactory`/`FamilyFactory`/`DescriptionTagFactory` generate synthetic unique values (not the real catalog words) specifically so tests creating many records in a loop don't exhaust a 5-item faker pool and hit the DB unique constraint.

**Description tags are order-sensitive, not just a set.** The user's real examples ("AH17DV2 TS USB") are a specific meaningful sequence, and a plain checkbox list doesn't scale as the catalog grows ("si és checkbox així serà molt llarg"). So `project_description_tag` also carries an `order` column; `Project::descriptionTags()` is `withPivot('order')->orderByPivot('order')`; `Admin\ProjectController` has a private `syncDescriptionTags()` that maps the incoming `description_tag_ids` array's position to the pivot `order` (array order *is* the source of truth — the frontend must send them in the intended display order, not alphabetically). `ProjectIndex.vue`'s Descripció field is a search-to-add autocomplete (typing filters `descriptionTags` client-side) plus an ordered `<ol>` of chips with ↑/↓/× controls — not checkboxes like Sections, which stay checkboxes since that list is small and bounded.

**Post-launch refinement (after the original 5 phases):** the user clarified the real Operari workflow after seeing it running — OF numbers are globally unique (format like `2026/01/0000123`, never repeated, not just unique per project) and an operari searches directly by OF number (not by project first). This changed: `order_fabrications.number` is now a plain global-unique column (was `unique(['project_id','number'])`); `Operari\ProjectController` (project-first search) was deleted and replaced by `Operari\OrderFabricationController@index` (`GET /operari/api/order-fabrications?q=`, returns OF rows with `project` eager-loaded); `Operari\EquipmentController@index`'s response shape changed from a bare array to `{ order_fabrication, equipment }` so `EquipmentList.vue` can show the project/OF header the user asked for ("ha de sortir el projecte que és"). `ProjectSelector.vue` is now a single OF-search box, not a two-step project→OF picker.

Fase 3's `/api/*` routes live in `routes/api.php` but are loaded from `routes/web.php` via `Route::middleware(['auth', EnsureAdminOrQc::class])->prefix('api')->name('api.')->group(base_path('routes/api.php'))` — they run through the session-based `web` middleware stack, not Laravel's stateless `api` group, since auth here is sessions, not tokens. Fase 4 mirrors this pattern for Operari: `routes/operari-api.php`, loaded the same way but with `EnsureOperari::class` and prefix `operari/api`.

Fase 4 also had to extend Fase 3: the user decided OF and Equipment (serial numbers) must be pre-created by Admin/QC, not invented on the fly by an Operari — so `Admin\OrderFabricationController` and `Admin\EquipmentController` (+ their Store/Update requests) were added to `/api/*` alongside Project/Section/Question/User. Equipment's `status`/`checked_at` are never settable from that Admin CRUD — they're system-managed, only ever changed by the Operari check flow (`Operari\EquipmentController@storePhotos`, which computes `status` from whether the equipment has defects / non-empty observations, then stamps `checked_at`).

Photo uploads use a dedicated `photos` filesystem disk (`config/filesystems.php`, root `storage/app/photos`, `serve => false` since these are private QC photos, not public assets) — not Laravel's default `local` disk, whose root is `storage/app/private` in this Laravel version, which doesn't match the spec's `storage/app/photos/` path.

Frontend API calls from Vue go through `resources/js/api.js`, a small `fetch()` wrapper (not axios — avoided adding it as a dependency) that reads the `XSRF-TOKEN` cookie and attaches it as `X-XSRF-TOKEN` so POST/PATCH/PUT/DELETE requests pass Laravel's CSRF check.

**Navigation is a first-class concern the user twice caught missing — treat it as a requirement, not polish, on every new page.** `/` (`Welcome.vue`) is a real chooser (two buttons: Operari login, Admin/QC login), not a static placeholder. Every authenticated Operari page (`ProjectSelector`, `EquipmentList`, `FormCheck`) uses `Components/OperariNav.vue` (contextual "← Enrere" + always-present "Tancar sessió"); both login pages have a plain "← Enrere" `Link` to `/` (no logout there — not authenticated yet). `Admin/ProjectIndex.vue` has the same "← Enrere" back to `/admin/dashboard`, and the Dashboard links forward to it. When adding a new page reachable only by clicking through the app, always add its way back out — don't wait to be asked twice.

Fase 5's dashboard follows the `dataviz` skill's method: nominal-category bar charts (defects by tipo, by responsibility, defect-rate by section) are single-series so every bar uses the same categorical slot-1 blue (`#2a78d6` light / `#3987e5` dark) — per that skill, a single-series nominal chart never needs the multi-hue categorical rotation, that's reserved for genuinely distinct overlapping series. `ChartDefects.vue` is one generic reusable bar-chart component (props: `title`, `items`) driving all three charts, each with a table-view toggle for accessibility. `Admin\PhotoController@show` streams a photo through the private `photos` disk and returns 404 (not a 500) if the file is missing from disk — caught by an actual browser test where seeded demo `Photo` rows had no backing file.

Auth design actually implemented in Fase 2 (for reference, since it refines what the phase list below only sketches): a single `web` guard against the `users` table serves both login forms — Admin/QC via `email`, Operari via `username` — because a user only ever has one of the two set, so `Auth::attempt()` naturally can't cross-match; each controller still re-checks `role` after a successful attempt as defense in depth. Guests are redirected to the login form matching the area they hit (`operari.login` for `/operari*`, `login` otherwise) via `redirectGuestsTo` in `bootstrap/app.php`.

## Project overview

AH_ManufactQC is a digital quality-control system for manufacturing inspections. Operators (Operari) answer per-serial-number checklists, log defects with photos; QC/Admin manage the checklist questions and review dashboards/statistics.

Roles:
- **Operari** — mobile/tablet frontend. Logs in with username+password. Answers checklists, marks defects, uploads 5-6 optional photos.
- **Responsable QC** — web backend. Creates questions, views dashboard, reviews defects/statistics.
- **Administrador** — web backend. Full CRUD, manages users and configuration.

Main flow: Operari login → select project/OF/serial number (entered manually, not sequential) → answer QUALITAT questions (Sí/No/Defecte) → defect popup (type, observation, responsibility, actions) if needed, multiple defects allowed per serial → free-text observations (optional) → photo popup (5-6 optional) → save sets `checked_at = NOW()` → serial number list shown color-coded (green/red/orange).

## Golden rule: always ask before executing

Never implement or run code without explicit approval. Procedure for every unit of work:
1. Explain what will be built.
2. Wait for explicit approval (e.g. "Sí, crea...", "Implementa...").
3. Implement and write verification tests alongside it.
4. Confirm the tests pass before considering the work done.

## Tech stack

- Backend: **Laravel 13.x** + Inertia.js (Inertia Laravel adapter v3) — note: the original project brief named Laravel 11, but 13.x is the current stable and was chosen deliberately when scaffolding; keep using 13.x going forward.
- Frontend: Vue 3 + Vite + **Tailwind CSS v3** (not v4 — deliberately downgraded for stability; config lives in `tailwind.config.js` + `postcss.config.js`, classic `@tailwind base/components/utilities` directives in `resources/css/app.css`)
- Database: SQLite (`database/database.sqlite`, `DB_CONNECTION=sqlite` already set in `.env`)
- i18n: vue-i18n (Català + Castellà) — all user-visible text goes through `$t('key')`, keys defined in `resources/lang/ca.json` and `resources/lang/es.json`
- Icons: `@lucide/vue` (the spec named `lucide-vue-next`, which is deprecated upstream in favor of this package — using the maintained one)
- Testing: Pest 4 (backend, `./vendor/bin/pest`), Vitest (frontend) — every feature ships with tests, not optional
- Auth: Laravel sessions (not JWT), multi-role — Admin/QC log in with email, Operari logs in with username
- File storage: local disk at `storage/app/photos/`, not S3

## Code conventions

**PHP**
- Variables: `camelCase`
- Classes: `PascalCase`
- Constants: `SCREAMING_SNAKE_CASE`
- All request validation goes through Form Requests — never validate inline in a controller
- Models use Eloquent relationships (not raw queries) for associations

**Vue/JavaScript**
- Components: `PascalCase` filenames
- Variables and methods: `camelCase`
- All visible text via `$t('key')`, never hardcoded strings

## Required folder structure

```
app/Models/                    Eloquent models
app/Http/Controllers/          Admin/ and Operari/ subfolders
app/Http/Middleware/           Admin/ and Operari/ subfolders for role-guard middleware (e.g. Fase 2's
                                EnsureAdminOrQc / EnsureOperari); NOT "Web/" — that collides with
                                Laravel's own built-in "web" middleware group name, which is a
                                different thing. Inertia's own HandleInertiaRequests stays directly
                                under Middleware/ since it's global, not role-specific
app/Http/Requests/             Form Requests (all validation)
database/migrations/
database/factories/
database/seeders/
resources/js/Pages/            Operari/ and Admin/ Inertia pages
resources/js/Components/       Reusable Vue components
resources/js/__tests__/        Vitest specs, mirrors Pages/Components layout
resources/lang/                ca.json, es.json
tests/Feature/                 Pest feature tests
storage/app/photos/            Uploaded equipment photos
```

## Domain concepts

| Concept | Definition |
|---------|-----------|
| Project | Number (e.g. 1400C0000.00), family (DB2, DB3), description, global observations |
| OF (OrderFabrication) | Manufacturing order, groups serial numbers |
| Equipment | Serial number, entered manually (not sequential), belongs to a Project + OF |
| Section | A group of questions (QUALITAT, PRODUCCIÓ, etc.), configured dynamically |
| Question | A Sí/No/Defecte question belonging to a Section |
| Defect | tipo + observation + responsibility + actions, tied to an Equipment |
| Photo | Up to 5-6 optional photos per Equipment, taken when marked OK |
| checked_at | Timestamp set when an Equipment is marked OK |

## Implementation phases

Work proceeds strictly in this order; each phase needs its own tests passing and explicit user go-ahead before starting the next.

1. **Setup Inicial** — DONE. Models, migrations, factories, seeders (User, Project, Section, Question, OrderFabrication, Equipment, Answer, Defect, Photo), base Pest tests (`DatabaseSeederTest`, `ModelRelationshipsTest`).
2. **Autenticació multi-rol** — DONE. `Admin\EnsureAdminOrQc` + `Operari\EnsureOperari` middleware, `AuthController` (Admin/QC, email) + `Operari\LoginController` (username), `LoginRequest`/`OperariLoginRequest`, `AuthenticationTest`, `AuthorizationTest`. Also created bare placeholder pages `Auth/Login.vue`, `Operari/Login.vue`, `Admin/Dashboard.vue`, `Operari/ProjectSelector.vue` — functional but unstyled/no i18n, since real UI is Fase 4/5 scope; don't be surprised they already exist when starting those phases, just style/wire them properly instead of recreating.
3. **Backend CRUD (Admin/QC)** — DONE. `Admin\{Project,Section,Question,User}Controller` (index/store/show/update/destroy, JSON), Store+Update Form Request pair for all four resources (not just Project — extended past the original spec for consistency, see "Golden rule" note above), `/api/*` routes (see status note above), one Pest test file per controller. `UserController` nulls out `email` for operari and `username` for admin/qc, and relies on `User`'s `'hashed'` cast to hash passwords (no manual `Hash::make()` needed — it self-detects already-hashed values via `Hash::isHashed()`).
4. **Frontend Operari** — DONE. `Operari/{Login,ProjectSelector,FormCheck,DefectModal,PhotosModal,EquipmentList}.vue`, `Components/{LanguageSelector,FormField,ButtonGroup}.vue`, `resources/lang/{ca,es}.json` + `resources/js/i18n.js` (vue-i18n, locale persisted to `localStorage` under key `ah_manufactqc_locale`), `resources/js/api.js` fetch helper, backend extensions described in the status note above, Vitest specs for `Login`, `FormCheck`, `LanguageSelector`. Navigation: `/operari` (ProjectSelector) → `/operari/order-fabrications/{of}/equipment-list` (EquipmentList, doubles as both the serial-picker and the post-check colored summary) → `/operari/equipment/{equipment}/check` (FormCheck, embeds DefectModal/PhotosModal) → back to EquipmentList on finish.
5. **Dashboard QC + i18n complet** — DONE. `Admin\DashboardController@index` (one endpoint, `stats`/`defects_by_type`/`responsibilities`/`trends`/`recent_photos`, filterable by `project_id`/`from`/`to`), `Admin\PhotoController@show` (serves a photo file, 404 if missing), `Admin/Dashboard.vue` + `StatCard`/`ChartDefects`/`PhotoGrid`/`FilterBar` components (dataviz-skill compliant, see status note above), `dashboard.*` i18n keys in both languages plus an automated ca/es key-parity test (`i18n-parity.spec.js`), `DashboardControllerTest`, `PhotoControllerTest`, `Dashboard.spec.js`.

## Commands

```bash
# Servers
php artisan serve                    # Backend, port 8000
npm run dev                          # Vite frontend

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan tinker

# Tests
./vendor/bin/pest                    # All Pest tests
./vendor/bin/pest tests/Feature      # Feature tests only
./vendor/bin/pest --filter=some_test_name   # Single test
npx vitest run                       # All Vitest tests once
npx vitest                           # Vitest watch mode

# Formatting
./vendor/bin/pint                    # PHP

# Production build
npm run build
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```
