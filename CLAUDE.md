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

**Pro Fisher** is a Ukrainian fishing platform — a Laravel 13 + Vue 3 SPA.

### Request flow

Laravel serves one Blade view (`resources/views/app.blade.php`) for all non-API routes. Vue Router handles client-side navigation. All API endpoints live under `/api/*` and return JSON.

### Authentication

Auth uses **Laravel Sanctum in session mode** (cookie-based, not token-based). Before calling `/api/login` or `/api/register`, the frontend calls `initCsrf()` (`resources/js/api/client.js`) which hits `/sanctum/csrf-cookie` to set the CSRF cookie. The axios client reads the CSRF token from the `<meta name="csrf-token">` tag and sends it as `X-CSRF-TOKEN` on every request.

Protected API routes use the `auth:sanctum` middleware.

### Backend (`app/`)

- **Controllers** live in `app/Http/Controllers/Api/`: `AuthController`, `HomeController`, `LakeController`, `CatchController`, `CabinetController`, `FisherController`, `LikeController`, `CommentController`.
- **Form Requests** (`app/Http/Requests/`) handle validation before controllers.
- **API Resources** (`app/Http/Resources/`) shape JSON responses — `LakeResource` conditionally includes relations using `whenLoaded`/`when`.
- **Policies** (`app/Policies/CatchRecordPolicy`) gate `update`/`delete` to the owner only; `CatchController` calls `$this->authorize(...)`.
- Models use PHP 8 **`#[Fillable]`** and **`#[Hidden]`** attributes instead of `$fillable`/`$hidden` properties.
- `CatchRecord` model maps to the `catches` table (explicit `$table`). Has `postLikes`, `catchComments`, `userComments` HasMany relations.
- `Lake` uses slug-based route model binding (`getRouteKeyName` returns `'slug'`).
- Catch photos are stored on the `public` disk under `catches/`, managed in `CatchController` (old photo deleted on update). Avatars stored under `avatars/`.
- `LakeController::buildPermitOptions()` calculates permit prices from `lake->price` with fixed multipliers (1×, 2.33×, 4×, 6.67× for 1/3/7/30 days).
- `catches` table has `type` column (`catch`|`post`, default `catch`) and `location` (nullable string). Fields `lake_id`, `fish_name`, `caught_at` are nullable — required only when `type=catch` (validated in `StoreCatchRequest`).
- `CatchRecordPolicy` uses `(int)` cast on both sides of comparison — MySQL can return `user_id` as string.
- `CabinetController::buildActivity()` returns structured `data: {}` (not a pre-built Ukrainian string). Frontend translates via `t('activity.{type}', item.data)`.
- `CatchResource` returns: `type`, `location`, `likes_count`, `is_liked`, `comments_count`, `is_commented`.
- `HomeController::posts()` sorts by `created_at` DESC (not `caught_at`), eager-loads `lake:id,name,slug,latitude,longitude` for LocationBadge.
- `User` model has `avatarUrl(): Attribute` accessor — converts storage path to full URL (non-http paths get `asset('storage/...')`).

### Internationalisation

Мови: **Українська (UA)** та **Польська (PL)**. Переключення через тоглер UA|PL в `AppHeader`.

- **`resources/js/i18n.js`** — `createI18n({ legacy: false, locale: savedLocale })`. Мова зберігається в `localStorage('locale')`.
- **`setLocale(locale)`** — міняє `i18n.global.locale.value`, `localStorage`, `document.documentElement.lang`.
- **`resources/js/locales/uk.json`** та **`pl.json`** — переклади. Секції: `nav`, `header`, `modal`, `catch`, `post`, `posts`, `map`, `auth`, `common`, `cabinet`, `stats`, `time`, `activity`, `achievements`.
- У компонентах: `const { t, te, locale } = useI18n()`. Для дат: `toLocaleDateString(locale.value === 'pl' ? 'pl-PL' : 'uk-UA')`.
- Назви досягнень перекладаються за `achievement.id` через `te(`achievements.${id}.title`)` з fallback на значення з бекенду.
- Активність: бекенд повертає `data: {}` (структуровані поля), фронтенд формує текст через `t('activity.{type}', item.data)`.

### SEO мета-теги

`@unhead/vue` v3 — ініціалізується в `app.js` як `createUnhead()` з ручним `app.provide(headSymbol, head)` (в v3 `createHead` перейменовано в `createUnhead`). `useHead()` з `computed()` використовується в `LakeDetailPage.vue` та `FisherPage.vue`.

### Frontend (`resources/js/`)

| Layer | Location | Purpose |
|---|---|---|
| API clients | `api/` | Thin wrappers over axios — one file per domain |
| Pinia stores | `stores/` | `auth`, `catches`, `lakes` |
| Pages | `pages/` | Route-level components |
| Components | `components/` | Grouped by domain (`cabinet/`, `catches/`, `lakes/`, `map/`, `layout/`, `posts/`, `shared/`) |
| Router | `router/index.js` | History-mode router; `requiresAuth` and `guest` meta guards |

Route guards in `router/index.js` redirect unauthenticated users to `/login` (with `redirect` query param). Auth is restored on page reload via `authReady` flag — `fetchUser()` called once before first navigation.

**Shared components** (`components/shared/`):
- `UserAvatar.vue` — clickable avatar (own profile → `/cabinet`, other → `/fishers/:id`). Props: `user`, `size` (sm/md/lg).
- `LocationBadge.vue` — semi-transparent blue badge linking to Google Maps. Props: `label`, `url`.
- `ModalDialog.vue` — shared modal wrapper (overlay + header + footer buttons). Props: `show`, `title`, `saving`, `submitLabel`, `savingLabel`. Default slot = form body. Used by `AddCatchModal` and `AddPostModal`.

**Opening modals from anywhere**: navigate to `/cabinet?action=add-catch` or `/cabinet?action=add-post`. `CabinetPage` watches `route.query.action` (immediate) and opens the modal, then clears the query. AppHeader uses this pattern for its "+ Улов" / "+ Пост" buttons.

**Catch detail sidebar**: `components/posts/CatchDetailModal.vue` — fixed right-side panel (`fixed right-4 w-96 z-50`, top `65px`, slide animation). Shows photo, author, fish name/weight, location badge, date, likes/comments count, notes, comments list + input. Used in `PostsPage.vue` when a PostCard is clicked.

### Database

MySQL in production, database `pro_fisher`. Migrations are in `database/migrations/`. `LakeSeeder` seeds 12 Polish lakes with photos (Unsplash URLs), catches, reviews, and one active permit for the demo user (`rybak@example.com` / `password`).

Key tables:
- `catches` — `user_id`, `lake_id` (nullable), `type` (catch|post), `fish_name` (nullable), `weight`, `photo`, `caught_at` (nullable), `notes`, `location` (nullable)
- `post_likes` — `user_id`, `catch_id` (unique together)
- `catch_comments` — `catch_id`, `user_id`, `body`
- `activities` — `user_id`, `type` (catch/following/follower), `data` (JSON)
- `follows` — `follower_id`, `following_id` (unique together)

### Known hardcoded values

`HomeController::stats()` returns `contests_count: 53` as a static value. `CabinetController` returns `ranking: 0` placeholder. Activity feed reads from real `activities` table (last 15 records).
