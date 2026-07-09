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

- **Controllers** live in `app/Http/Controllers/Api/`: `AuthController`, `HomeController`, `LakeController`, `CatchController`, `CabinetController`, `FisherController`, `LikeController`, `CommentController`, `AdminController`.
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
- `CabinetController::buildActivity()` returns structured `data: {}` (not a pre-built Ukrainian string). Frontend translates via `t('activity.{type}', item.data)`. For `following`/`follower` types `data` includes `id` (user id) — `ActivityFeed.vue` splits the translated message and renders the name as a `<router-link to="/fishers/{id}">`.
- `CatchResource` returns: `type`, `location`, `likes_count`, `is_liked`, `comments_count`, `is_commented`.
- `HomeController::posts()` sorts by `created_at` DESC (not `caught_at`), eager-loads `lake:id,name,slug,latitude,longitude` for LocationBadge.
- `User` model has `avatarUrl(): Attribute` accessor — converts storage path to full URL (non-http paths get `asset('storage/...')`).
- `FisherController::show()` returns profile (no `location`/`joined_at`), stats (incl. `total_likes`), achievements, `is_following`. `FisherController::posts()` — `GET /api/fishers/{user}/posts?page=&type=post|catch` — paginated (12/page) catches+posts with `meta: {current_page, last_page, total}`; used by FisherPage infinite scroll.
- **Admin panel**: `AdminController` behind `admin` middleware, prefix `/api/admin`. Endpoints: `stats` (users/catches/comments/likes/lakes counts), `users` + block/unblock, `catches` + delete, and full lake CRUD:
  - `lakes` — paginated list, primary photo eager-loaded.
  - `storeLake` — validates lake fields + up to 10 photos ≤5MB, unique slug via `Str::slug`, photos on `public` disk under `lakes/`, first photo `is_primary`.
  - `showLake` — full lake data + all photos, for the edit form.
  - `updateLake` — **POST, not PUT** (PHP does not parse multipart bodies on PUT). Regenerates the slug only when the name changes, deletes photos listed in `deleted_photo_ids[]` (files too), appends new ones, guarantees a primary photo survives.
  - `deleteLake` — nulls `catches.lake_id` first so user content survives the FK cascade, then deletes photo files and the lake.
  - Routes bind `{lake:id}` explicitly because `Lake::getRouteKeyName()` returns `'slug'`.
- `LakePhoto` model — `lake_photos` table: `lake_id`, `path`, `is_primary`, `sort_order`.

### Internationalisation

Мови: **Українська (UA)** та **Польська (PL)**. Переключення через тоглер UA|PL в `AppHeader`.

- **`resources/js/i18n.js`** — `createI18n({ legacy: false, locale: savedLocale })`. Мова зберігається в `localStorage('locale')`.
- **`setLocale(locale)`** — міняє `i18n.global.locale.value`, `localStorage`, `document.documentElement.lang`.
- **`resources/js/locales/uk.json`** та **`pl.json`** — переклади. Секції: `nav`, `header`, `modal`, `catch`, `post`, `posts`, `map`, `auth`, `common`, `cabinet`, `stats`, `time`, `activity`, `achievements`, `fisher`.
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

**App layout (`App.vue`)**: root div is `flex flex-col` with `height: 100dvh` and `overflow-hidden`; `<main>` has `min-h-0 flex-1 overflow-y-auto` — content scrolls inside main, not the body. Mobile bottom nav is a **static flex child** (`shrink-0 md:hidden`) at the bottom of the column — no `position: fixed` (fixed positioning was unreliable on mobile). Nav items with icons defined directly in App.vue. `AppSidebar.vue` is desktop-only (`hidden md:flex`, fixed left, expands on hover). Full-height pages (MapPage) use `h-full`, not `calc(100vh-...)`.

**FisherPage** (`/fishers/:id`): profile card (avatar, name, @handle, bio, follow/unfollow, 6 stat items) + tab bar (Публікації / Пости / Улови / Досягнення) + 4-column card grid (2 on mobile) with like/comment counts. Infinite scroll via `IntersectionObserver` on a sentinel div → `fetchFisherPosts(id, {page, type})`. Card click opens `CatchDetailModal`. Own id redirects to `/cabinet`.

**Admin panel** (`/admin`, `pages/admin/`): `AdminPage.vue` with tabs (dashboard stats / users / content / lakes), `AddLakeModal.vue` — lake create/edit form with Leaflet map coordinate picker (click sets lat/lng marker) and multi-photo upload (drag-and-drop, previews via `URL.createObjectURL`, FormData multipart submit). Admin link shown in nav only for `user.is_admin`.

**Lake address → coordinates** (`AddLakeModal.vue`): three ways to set `latitude`/`longitude`, all sharing `placeMarker(lat, lng)`:
- Click the Leaflet map.
- Type an address + press Enter or the "Знайти" button → `geocodeAddress()` calls **Nominatim** (`nominatim.openstreetmap.org/search`, free OSM geocoder, ~1 req/sec limit). Returns WGS84 decimal degrees — the same system Google Maps uses, so the `?q=lat,lng` links in `LocationBadge`/`PostCard` work unchanged.
- Focus the address input → a dropdown appears with **"Моє місцезнаходження"** (mobile-first). `useMyLocation()` calls `navigator.geolocation.getCurrentPosition` (`enableHighAccuracy: true`), fills the coordinate inputs, moves the marker, then `reverseGeocode()` hits Nominatim's `/reverse` endpoint to fill the address text. Guards for missing API and non-HTTPS (`window.isSecureContext`) — **geolocation is blocked over plain HTTP except on localhost**. Permission-denied / timeout errors map to Ukrainian messages. The dropdown closes on `pointerdown` outside (listener registered in `onMounted`, removed in `onUnmounted`).

**Lake draft persistence**: in create mode `AddLakeModal` watches `form` deeply and mirrors it to `localStorage('admin_lake_draft')`, restoring on open and clearing after a successful create. Photos are not persisted (`File` objects can't be serialised).

**PostsPage filters**: "Всі" resets both `activeFilter` and `typeFilter`; activating Пости/Улови sets `activeFilter = 'none'` (removes green from "Всі"); deactivating restores `'all'`.

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
- `lake_photos` — `lake_id`, `path`, `is_primary`, `sort_order`

### Known hardcoded values

`HomeController::stats()` returns `contests_count: 53` as a static value. `CabinetController` returns `ranking: 0` placeholder. Activity feed reads from real `activities` table (last 15 records).
