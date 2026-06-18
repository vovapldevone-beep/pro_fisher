# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Start all dev services concurrently (Laravel server, queue worker, Pail log viewer, Vite)
composer run dev

# Run tests
composer run test

# Run a single test
php artisan test --filter TestName

# Initial project setup (install deps, key generate, migrate, npm build)
composer run setup

# Lint PHP with Laravel Pint
./vendor/bin/pint

# Run migrations
php artisan migrate

# Seed the database (lakes, users, catches, reviews, permits)
php artisan db:seed
```

## Architecture

**Pro Fisher** is a Polish fishing platform — a Laravel 13 + Vue 3 SPA.

### Request flow

Laravel serves one Blade view (`resources/views/app.blade.php`) for all non-API routes. Vue Router handles client-side navigation. All API endpoints live under `/api/*` and return JSON.

### Authentication

Auth uses **Laravel Sanctum in session mode** (cookie-based, not token-based). Before calling `/api/login` or `/api/register`, the frontend calls `initCsrf()` (`resources/js/api/client.js`) which hits `/sanctum/csrf-cookie` to set the CSRF cookie. The axios client reads the CSRF token from the `<meta name="csrf-token">` tag and sends it as `X-CSRF-TOKEN` on every request.

Protected API routes use the `auth:sanctum` middleware.

### Backend (`app/`)

- **Controllers** live in `app/Http/Controllers/Api/` — one file per domain: `AuthController`, `HomeController`, `LakeController`, `CatchController`, `CabinetController`.
- **Form Requests** (`app/Http/Requests/`) handle validation before controllers.
- **API Resources** (`app/Http/Resources/`) shape JSON responses — `LakeResource` conditionally includes relations using `whenLoaded`/`when`.
- **Policies** (`app/Policies/CatchRecordPolicy`) gate `update`/`delete` to the owner only; `CatchController` calls `$this->authorize(...)`.
- Models use PHP 8 **`#[Fillable]`** and **`#[Hidden]`** attributes instead of `$fillable`/`$hidden` properties.
- `CatchRecord` model maps to the `catches` table (explicit `$table`).
- `Lake` uses slug-based route model binding (`getRouteKeyName` returns `'slug'`).
- Catch photos are stored on the `public` disk under `catches/`, managed in `CatchController` (old photo deleted on update).
- `LakeController::buildPermitOptions()` calculates permit prices from `lake->price` with fixed multipliers (1×, 2.33×, 4×, 6.67× for 1/3/7/30 days).

### Frontend (`resources/js/`)

| Layer | Location | Purpose |
|---|---|---|
| API clients | `api/` | Thin wrappers over axios — one file per domain |
| Pinia stores | `stores/` | `auth`, `catches`, `lakes` |
| Pages | `pages/` | Route-level components |
| Components | `components/` | Grouped by domain (`cabinet/`, `catches/`, `lakes/`, `map/`, `layout/`) |
| Router | `router/index.js` | History-mode router; `requiresAuth` and `guest` meta guards |

Route guards in `router/index.js` redirect unauthenticated users to `/login` (with `redirect` query param) and redirect authenticated users away from guest-only routes.

### Database

SQLite in development (`database/database.sqlite`). Migrations are in `database/migrations/`. `LakeSeeder` seeds 12 Polish lakes with photos (Unsplash URLs), catches, reviews, and one active permit for the demo user (`rybak@example.com` / `password`).

### Known hardcoded values

`HomeController::stats()` returns `contests_count: 53` as a static value. `CabinetController` returns hardcoded `followers_count: 126`, `ranking: 126`, and fake activity entries (comment/follower notifications). These are placeholder UI data.
