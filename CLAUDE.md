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

**Google OAuth** (`laravel/socialite`, `GoogleAuthController`): routes live in **`routes/web.php`** (NOT `/api`) — `GET /auth/google/redirect` and `GET /auth/google/callback` — because a full-page redirect from Google is not a stateful-frontend request, so under the `api` group it would get no session and `Auth::login` could not persist. The callback finds by `google_id`, else **links by email** to an existing account (Google emails are verified → safe), else creates a user (`password` nullable, `email_verified_at` set, avatar from Google). Blocked users bounce to `/login?error=blocked`. Config in `config/services.google` (`GOOGLE_CLIENT_ID`/`SECRET`/`REDIRECT_URI`). `users.google_id` column added; `password` made nullable. Frontend: `components/shared/GoogleSignInButton.vue` (a plain `<a href="/auth/google/redirect">`) on Login/Register pages. Redirect URI must be `<APP_URL>/auth/google/callback` exactly. Only works over HTTPS (localhost excepted) — needs Google Cloud Console credentials.

### Backend (`app/`)

- **Controllers** live in `app/Http/Controllers/Api/`: `AuthController`, `GoogleAuthController`, `HomeController`, `LakeController`, `CatchController`, `CabinetController`, `FisherController`, `FishHuntController`, `LikeController`, `CommentController`, `AdminController`. Outside `Api/`: `SpaController` (serves the SPA shell + SEO), `SitemapController`.
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
- `HomeController::posts()` — `GET /api/posts?page=&type=post|catch&filter=liked|commented` — **paginated (12/page)**, sorts by `created_at` DESC, returns `{data, meta:{current_page,last_page,total}}`. Filters are applied **server-side** (not in the browser) because with infinite scroll the client only holds a slice — a client-side filter would hide matches on unfetched pages. `type=catch` also matches `type IS NULL` (legacy rows). `liked`/`commented` filters ignored for guests. Eager-loads `user`, `lake` for LocationBadge.
- `FishHuntController` (auth): `GET /api/fish-hunt` returns `{found, total}`; `POST /api/fish-hunt/find` atomically increments (`increment()`), caps at 10, sets `completed_at` on the 10th. Progress stored in `user_achievements` under key `fish_hunt` (see Fish hunt section).
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
- **`resources/js/locales/uk.json`** та **`pl.json`** — переклади. Секції: `nav`, `header`, `modal`, `catch`, `post`, `posts`, `map`, `auth`, `common`, `cabinet`, `stats`, `time`, `activity`, `achievements`, `fisher`, `friends`, `fish`, `raffle`. Login/Register pages hardcode Polish strings (not i18n).
- У компонентах: `const { t, te, locale } = useI18n()`. Для дат: `toLocaleDateString(locale.value === 'pl' ? 'pl-PL' : 'uk-UA')`.
- Назви досягнень перекладаються за `achievement.id` через `te(`achievements.${id}.title`)` з fallback на значення з бекенду.
- Активність: бекенд повертає `data: {}` (структуровані поля), фронтенд формує текст через `t('activity.{type}', item.data)`.

### SEO (server-side, PHP)

The server has no Node, so no SSR/prerender. Instead **`SpaController`** renders per-route SEO in PHP on every request (fresh from the DB — new lakes covered instantly). This is the real win for social crawlers (Telegram, FB, X) and JS-weak engines (Bing, Yandex) that read only raw HTML; `@unhead/vue` alone only helps Googlebot.

- `SpaController::__invoke()` serves `app.blade.php` with a `$meta` array (title, description, canonical, robots index/noindex, OG, Twitter). Public/indexable routes: **`/`, `/map`, `/lakes/{slug}`** only. Everything auth-gated (`/posts`, `/fishers`, `/cabinet`, `/admin`, login/register) → `noindex`.
- Lake pages also emit **JSON-LD** (`TouristAttraction` + `AggregateRating` from reviews) and a **`<noscript>`** block with real content (name, region, fish, price, rating, **recent catches**, reviews) so non-JS crawlers index actual text. This is why posts are surfaced on lake pages rather than as thousands of thin standalone URLs.
- `SitemapController` + `/sitemap.xml` (registered in `web.php` before the SPA catch-all) — live from DB: home, map, all lakes. `public/robots.txt` disallows private routes and references the sitemap.
- `web.php`: `Route::get('/{any}', SpaController::class)->where('any', '^(?!api|sanctum|storage|up|sitemap\.xml).*$')`, plus `/` and the Google OAuth + sitemap routes registered before it.
- Canonical/OG URLs come from `APP_URL` — must be the real HTTPS domain on the server.
- Client-side `@unhead/vue` v3 (`createUnhead()` in `app.js`, `useHead()` in `LakeDetailPage`/`FisherPage`) still runs for SPA nav (tab title updates); on lake pages this duplicates the server OG tags in the DOM for JS users, which is harmless (server tags are first, crawlers use those).

### Google Analytics

`config('services.google_analytics.id')` from `GOOGLE_ANALYTICS_ID` env. The gtag.js snippet renders in `app.blade.php` `<head>` **only when set** — leave empty locally so test clicks don't pollute stats. SPA page-views are covered by GA4's enhanced measurement (history events), so no manual `page_view`.

### Frontend (`resources/js/`)

| Layer | Location | Purpose |
|---|---|---|
| API clients | `api/` | Thin wrappers over axios — one file per domain |
| Pinia stores | `stores/` | `auth`, `catches`, `lakes`, `fish` |
| Pages | `pages/` | Route-level components |
| Components | `components/` | Grouped by domain (`cabinet/`, `catches/`, `lakes/`, `map/`, `layout/`, `posts/`, `shared/`, `fish/`) |
| Composables | `composables/` | `useScrollLock`, `useHideOnScroll`, `useInfiniteScroll`, `useGeolocation` |
| Router | `router/index.js` | History-mode router; `requiresAuth` and `guest` meta guards |

Route guards in `router/index.js` redirect unauthenticated users to `/login` (with `redirect` query param). Auth is restored on page reload via `authReady` flag — `fetchUser()` called once before first navigation. **After login the home page is `/posts`** — the guard redirects an authenticated user hitting `/` (or `guest` pages) to `/posts`; Login/Register also push there. The "Спільнота" nav item (`/`) is commented out in `AppSidebar.vue` and the App.vue mobile nav.

**Composables** (all in `composables/`): `useScrollLock(isLocked)` freezes `<main>` (the app scrolls there, not `<body>`). `useHideOnScroll(mainRef)` hides the header on scroll-down / reveals on scroll-up; ignores the iOS rubber-band overscroll region and a bottom dead-zone to stop header flap. `useInfiniteScroll(sentinelRef, {loading, hasMore, onLoad})` re-attaches its `IntersectionObserver` when the sentinel remounts (tab switches) and keeps firing while the sentinel stays visible; `root` is `<main>`. Note the pagination invariant: `page` holds the *next* page, so `hasMore` uses `page <= lastPage` (inclusive) or the final page never loads. `useGeolocation()` wraps `navigator.geolocation` + Nominatim reverse-geocode (HTTPS-only guard).

**App layout (`App.vue`)**: root div is `flex flex-col overscroll-none` with `height: 100dvh` and `overflow-hidden`; `<main>` (ref `mainEl`) has `min-h-0 flex-1 overflow-y-auto overscroll-y-none` — content scrolls inside main, not the body; `overscroll-*none` kills the iOS rubber-band (which otherwise made the header flap). Mobile bottom nav is a **static flex child** (`shrink-0 md:hidden`) at the bottom of the column — no `position: fixed`. `AppSidebar.vue` is desktop-only (`hidden md:flex`, fixed left, expands on hover). Full-height pages (MapPage) use `h-full`.

**Header** (`AppHeader.vue`): brand is `<img :src="'/images/logo.png'">` (`:src`, not `src`, so Vite serves it from `public/` instead of bundling) + name **"ProFisher"**. Hides on scroll-down / reveals on scroll-up on mobile via `useHideOnScroll` (the content row's top padding collapses in step; header never hides from `md` up). Language switcher always visible. Shows the fish-hunt counter (see Fish hunt). App.vue renders a floating fish badge (top-right, mobile, Posts page only) when the header is hidden.

**FisherPage** (`/fishers/:id`) and **CabinetPage** (`/cabinet`) share a layout: profile card (avatar, name, @handle, bio, stats) + tab bar (Публікації / Пости / Улови / Досягнення) + PostCard grid, infinite scroll via `useInfiniteScroll` → `fetchFisherPosts(id, {page, type})` (Cabinet passes own id). Card click opens `CatchDetailModal`. FisherPage has follow/unfollow; own id redirects to `/cabinet`. CabinetPage adds an "Активність" mobile-only tab, "Мої друзі" button (`FriendsModal`), and a single "Додати публікацію" button (opens `AddPostModal`; the modal header tabs switch to `AddCatchModal`). `PermitsCard` is commented out (permits feature hidden). The Досягнення tab merges the stateful **fish-hunt achievement** from the fish store into the API achievements (both here and on `AchievementsPage` via `AchievementCard`), with a "Деталі..." link to `/raffle`.

**RafflePage** (`/raffle`, `requiresAuth`): thin wrapper (back button) around **`components/fish/RaffleCard.vue`** — the raffle card itself (hero, participation status / progress bar, how-to steps, prize; reads the fish store). Linked from the header fish counter popover and the fish achievement cards. **`components/fish/RafflePromoModal.vue`** shows the same card in a semi-transparent modal (`z-[90]`, zoom-in, `useScrollLock`) **once per browser session after login** — gated in App.vue via `sessionStorage('raffle_promo_seen')` (cleared on logout so a re-login shows it again) and skipped when the hunt is already completed.

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
- `ModalDialog.vue` — shared modal wrapper (overlay + header + footer buttons). Props: `show`, `title`, `saving`, `submitLabel`, `savingLabel`, plus optional `tabs` (`[{key,label}]`) + `activeTab` — when set, the header becomes two equal tab columns instead of the title and emits `tab` on click. `AddPostModal`/`AddCatchModal` use this: both show "Новий пост | Новий улов" tabs and emit `switch`; parents (PostsPage, CabinetPage) close one modal and open the other. Everywhere a single "+ Додати публікацію" button (`common.addPublication`) opens `AddPostModal` — no separate add-catch button.

**Opening modals from anywhere**: navigate to `/cabinet?action=add-catch` or `/cabinet?action=add-post`. `CabinetPage` watches `route.query.action` (immediate) and opens the modal, then clears the query. AppHeader uses this pattern for its single "+ Додати публікацію" button (`add-post`; `add-catch` still works via URL).

**Catch detail modal**: `components/posts/CatchDetailModal.vue` — **full-screen zoom modal** (Teleport, `fixed inset-0`, springy scale-in), photo on the left (desktop, `object-contain` letterboxed) / details+comments on the right. Owner (`post.user_id === auth id`) gets a three-dot menu (Edit → emits `edit`; Delete → `deleteCatch`). Closes on ×, backdrop, Esc. Uses `useScrollLock`. A **10% chance per open** spawns a hidden fish (see Fish hunt). Opened from PostsPage/CabinetPage/FisherPage when a PostCard is clicked.

**`components/posts/EditCatchModal.vue`**: edits a catch or post (type-aware fields). Uses `LakeSelect` for the place. `UpdateCatchRequest` allows `lake_id`/`location` as `nullable` (a catch needs one *or* the other — same rule as `StoreCatchRequest`, `required_without`).

**`components/shared/LakeSelect.vue`**: a "place" combobox (two `v-model`s: `modelValue`=lake_id, `location`=free text) used in Add/Edit catch modals. Dropdown teleported to body (so the modal's `overflow-y-auto` can't clip it), repositioned on scroll/resize. Options: **"Використовувати GPS"** (always on top), the lakes list, and — as you type — **Nominatim place suggestions** (debounced 350ms, min 3 chars, aborts stale requests) plus a fallback "use as my own place". Keyboard-navigable over the combined list.

**`components/cabinet/FriendsModal.vue`**: `CabinetController::friends()` → `GET /api/cabinet/friends` returns `following` / `followers` (id, name, avatar, badge; followers include `is_following` for a "mutual" tag). Tabs, tap a person → `/fishers/:id`. Opened from a "Мої друзі" button on CabinetPage.

### Fish hunt (easter egg)

"Find 10 hidden fish" game. Progress is per-user in `user_achievements` (key `fish_hunt`), served by `FishHuntController`.

- **`stores/fish.js`**: `found`/`total`/`remaining`/`completed`/`justCompleted`; `fetchProgress()`, `recordFind()` (optimistic, server-capped at 10), `acknowledgeCompletion()`, `reset()`. `justCompleted` fires **only** when the final fish is caught this session (not when a fetch loads an already-done tally) — it gates the fireworks. Loaded on login / cleared on logout (watch in App.vue).
- **`components/fish/HiddenFish.vue`**: a clickable 🐟 (idle wiggle, pop-catch animation). On click sets an inline `z-index:60` so a fish tucked behind a card (`-z-10`) pops fully in front while animating.
- **`components/fish/FireworksOverlay.vue`**: full-screen canvas particle fireworks (`pointer-events-none`, `z-[100]`), auto-hides after ~4.5s; skipped under `prefers-reduced-motion`. Mounted in App.vue, triggered by `justCompleted`.
- **Placement (PostsPage)**: `topUpFish(batch)` drips fish onto posts as they load (initial batch + each infinite-scroll append) with a per-post chance — **no cap**; catching any counts toward 10. When `completed`, generation stops and a watch clears remaining placements. Peek variants tuck behind the card (`-z-10`, wrapper is `relative isolate` so they don't fall behind the page); text variants sit over the card (`z-20`). `CatchDetailModal` also spawns one with 10% chance per open.
- **Header counter** "🐟 found/total" with a hover/tap popover: incomplete → "find all 10…", complete → "you're in the raffle" + "Деталі..." → `/raffle`.

### Database

MySQL in production, database `pro_fisher`. Migrations are in `database/migrations/`. `LakeSeeder` seeds 12 Polish lakes with photos (Unsplash URLs), catches, reviews, and one active permit for the demo user (`rybak@example.com` / `password`).

Key tables:
- `users` — plus `location`, `bio`, `avatar_url`, `badge`, `is_admin`, `is_blocked`, `google_id` (nullable unique), `password` (nullable — OAuth users)
- `catches` — `user_id`, `lake_id` (nullable), `type` (catch|post), `fish_name` (nullable), `weight`, `photo`, `caught_at` (nullable), `notes`, `location` (nullable)
- `post_likes` — `user_id`, `catch_id` (unique together)
- `catch_comments` — `catch_id`, `user_id`, `body`
- `activities` — `user_id`, `catch_id` (nullable, cascade-deletes with the catch), `type` (catch/following/follower), `data` (JSON)
- `follows` — `follower_id`, `following_id` (unique together)
- `lake_photos` — `lake_id`, `path`, `is_primary`, `sort_order`
- `user_achievements` — `user_id`, `key`, `progress`, `completed_at` (unique `user_id,key`). Stateful achievement progress, distinct from the display-only achievements CabinetController computes from stats. Fish hunt = key `fish_hunt`.

### Seeders (all idempotent, safe to re-run on prod)

- `LakeSeeder` — 12 Polish lakes with photos, catches, reviews, one active permit for `rybak@example.com` / `password`.
- `DemoUserSeeder` — 20 filler users on `@profisher.test` (`firstOrCreate` by email).
- `UserContentSeeder` — 2–10 random posts/catches per **demo user only** (skips real accounts and users who already have posts). Photos named `catches/user{id}_{n}.jpg`.
- `PhotoContentSeeder` — creates exactly one publication per photo already in `storage/app/public/catches` (`user{id}_{n}.{ext}`), fully deterministic, **excludes users 9 & 10** (`EXCLUDED_USER_IDS`). Use this (not `UserContentSeeder`) when uploading real photos — the count follows the disk, so records never drift from files.
- `PostLikeSeeder` — 2–15 likes per publication, only **demo users** hand out likes; publications already at ≥2 likes are skipped.

### Deployment

The production server has **no Composer and no Node**. Build everything locally, ship the artifacts. Full runbook in **`DEPLOY.md`** (gitignored). Key points:

- Build locally: `npm run build` (→ `public/build/`) + `composer install --no-dev --optimize-autoloader` (→ `vendor/`, includes `laravel/socialite`).
- Deploy via a dedicated **`deploy` git branch** that force-commits `vendor/` and `public/build/` (both gitignored on working branches); on the server `git fetch && git reset --hard origin/deploy` (never `pull` — history is force-pushed).
- `.env` on server: `APP_ENV=production`, `APP_DEBUG=false`, real HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS=<host>` (host only, no scheme), `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI`, `GOOGLE_ANALYTICS_ID`, `SESSION_DOMAIN=null`. After edits: `php artisan config:cache && route:cache && view:cache`.
- On the server after deploy: `php artisan migrate --force`, `php artisan storage:link` (once). Behind a reverse proxy that terminates SSL, add `$middleware->trustProxies(at: '*')` in `bootstrap/app.php` or session cookies (secure) won't set and login 419/401s.
- **Local dev on `localhost:8000`** must add `SANCTUM_STATEFUL_DOMAINS=localhost:8000` and `APP_URL=http://localhost:8000` — `localhost:8000` is NOT in Sanctum's default stateful list (only `127.0.0.1:8000` and `localhost:3000` are), so the session is ignored otherwise. `composer run dev` runs `php artisan serve --host=0.0.0.0`; `vite.config.js` has `server.host: '0.0.0.0'` + `VITE_HMR_HOST` for phone testing.

### Brand assets

- Favicon: `public/favicon.png` (linked in `app.blade.php` `<head>`).
- Header logo: `public/images/logo.png`, referenced as `:src="'/images/logo.png'"` (dynamic bind so Vite serves from `public/` rather than trying to bundle it). Brand name **"ProFisher"**.
- `public/images/bg.png` — home hero background, also the default OG image.

### Known hardcoded values

`HomeController::stats()` returns `contests_count: 53` as a static value. `CabinetController` returns `ranking: 0` placeholder. Activity feed reads from real `activities` table (last 15 records).
