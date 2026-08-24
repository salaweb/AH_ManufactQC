# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

Fase 1, 2 and 3 are done — 42/42 Pest tests passing. Fase 4 (Frontend Operari) has not started. Implementation proceeds in the 5 phases listed below; do not jump ahead to a later phase until the user has explicitly approved moving on from the current one.

Fase 3's `/api/*` routes live in `routes/api.php` but are loaded from `routes/web.php` via `Route::middleware(['auth', EnsureAdminOrQc::class])->prefix('api')->name('api.')->group(base_path('routes/api.php'))` — they run through the session-based `web` middleware stack, not Laravel's stateless `api` group, since auth here is sessions, not tokens.

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
4. **Frontend Operari** — Flesh out `Operari/Login.vue` and `Operari/ProjectSelector.vue` (already exist as bare placeholders from Fase 2) plus FormCheck, DefectModal, PhotosModal, EquipmentList Vue pages; LanguageSelector/FormField/ButtonGroup components; i18n setup; Vitest specs.
5. **Dashboard QC + i18n complet** — DashboardController + stats/defects/responsibilities/trends endpoints, Admin/Dashboard.vue + chart/stat components, full ca/es translation coverage, Pest + Vitest tests.

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
