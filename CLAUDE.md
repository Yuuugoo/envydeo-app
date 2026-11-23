# Envydeo — Project Structure & Guide

Envydeo is a movie / TV-show browsing and streaming site built on the **TALL stack**
(Tailwind CSS 4 + DaisyUI 5, Alpine.js, Laravel 12, Livewire 3). All catalogue data comes
from the **TMDB API**; the local database only stores users, OAuth links, watchlists and
watch progress. Playback is an embedded third-party player (`vidfast.pro`), and trailers are YouTube embeds.

## Tech stack

| Layer | What |
|---|---|
| Backend | PHP ≥ 8.2, Laravel 12, Livewire 3, Laravel Breeze (auth scaffolding), Socialite (Google/GitHub OAuth), spatie/laravel-medialibrary (user avatars) |
| Frontend | Blade views, Alpine.js (bundled with Livewire 3), Tailwind CSS 4 via `@tailwindcss/vite`, DaisyUI 5 |
| Build | Vite 6 (`resources/css/app.css`, `resources/js/app.js` → `public/build`) |
| Data | TMDB REST API v3 (bearer token `TMDB_TOKEN`); MySQL in prod, SQLite locally |
| Cache / sessions / queue | `CACHE_STORE` (file locally), `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` |

## Request flow

```
Browser ──► routes/web.php ──► Controller ──► App\Services\Tmdb ──► TMDB API
                                   │                 └─ cached per URL (Cache facade)
                                   └─► Blade view (+ Livewire components, Alpine x-data)
Home page sections lazy-load JSON from /api/* endpoints via fetch() + IntersectionObserver.
```

## TMDB access — ALWAYS go through `App\Services\Tmdb`

`app/Services/Tmdb.php` is the only place that talks to TMDB.

- `get($path, $query = [], $ttl)` — one cached request. Returns decoded JSON, or `[]` on failure (failures are never cached).
- `many([$key => [$path, $query], ...], $ttl)` — cache hits are read from the cache. Misses are sent **concurrently** through `Http::pool` (in batches of 20). Results come back keyed like the input.
- `movieGenres()` / `tvGenres()` — `Collection` of id ⇒ name, cached for a week.
- TTL constants (seconds): `MINUTES_10`, `HOUR`, `HOURS_6`, `DAY`, `WEEK`.
- Requests use a 5s connect timeout, a 10s timeout and 2 retries.

Rules: never call `Http::...themoviedb.org` directly. Batch independent calls with `many()`.
Remember that Laravel cache TTLs are in **seconds**.

## Directory map

```
app/
  Enums/OAuthProviderEnum.php        github | google (route-bound in /login/{provider})
  Http/Controllers/
    MovieController.php              home page, /movies list + details, all /api/* JSON endpoints
    ShowsController.php              /shows list + details (seasons/episodes fetched in parallel)
    SearchController.php             /search results page (movie + tv search in parallel, sort/filter)
    WatchlistController.php          /watchlist (auth) — movie details for saved tmdb_ids
    WatchProgressController.php      JSON CRUD for playback progress (auth)
    OAuthProviderController.php      Socialite redirect + callback, avatar import via medialibrary
    ProfileController.php, Auth/*    Laravel Breeze (login, register, reset, verify, profile)
  Livewire/
    SearchDropdown.php               navbar live search (TMDB /search/multi, debounced 400ms)
    AddWatchlist.php                 toggle a tmdb_id in the user's watchlist
    AvatarDropdown.php               navbar user menu
  Models/  User (UUID PK, HasMedia 'avatar'), OAuthProvider, Watchlist, WatchProgress
  Services/Tmdb.php                  TMDB client + cache (see above)
  View/Components/  MovieCard, ShowCard, AppLayout, GuestLayout
database/migrations/                 users (uuid), cache, jobs, media, o_auth_providers, watchlist, watch_progress
resources/
  css/app.css                        Tailwind 4 + DaisyUI plugin + @source globs
  js/app.js, bootstrap.js            axios setup (Alpine itself comes from Livewire)
  views/
    layouts/main.blade.php           main layout used by all site pages (@extends + @section('content'))
    layouts/app|guest.blade.php      Breeze component layouts (<x-app-layout>/<x-guest-layout>)
    layouts/nav.blade.php            top nav + DaisyUI mobile drawer; footer.blade.php
    index.blade.php                  HOME: hero carousel, continue watching, popular, top rated, genre rows
    movies/index|details.blade.php   now-playing grid w/ pagination; movie page (player, trailer, tabs)
    shows/index|details.blade.php    trending TV grid; show page (episodes by season, similar, images)
    results.blade.php                search results with media-type / sort filters
    watchlist.blade.php              user's watchlist grid
    components/movie-card|show-card  poster cards used by grids
    livewire/*.blade.php             Livewire component views
    auth/*, profile/*                Breeze views
    details.blade.php, dashboard.blade.php, layouts/navigation.blade.php   UNUSED leftovers
routes/web.php                       site + /api routes; routes/auth.php Breeze routes
public/img/logo/                     Envydeo logos; public/build = Vite output (git-ignored)
```

## Routes

| Method | URI | Handler | Notes |
|---|---|---|---|
| GET | `/` | MovieController@index | name `movie.index`. Hero carousel cached 6h (`home:carousel`) |
| GET | `/movies?page=` | MovieController@list | `movie.list`, TMDB now_playing |
| GET | `/movies/{id}` | MovieController@show | `movie.show`, numeric id, 404 if not found |
| GET | `/movies/{id}/similar`, `/recommended`, `/images` | MovieController | JSON for the details-page tabs |
| GET | `/api/movies/popular`, `/api/movies/top-rated`, `/api/movies/collage`, `/api/tv/popular` | MovieController | JSON for the home page |
| GET | `/api/movies/genre/{id}` | MovieController@loadMoviesByGenre | 3 discover pages, fetched in parallel |
| GET | `/shows?page=` | ShowsController@list | `show.list` |
| GET | `/shows/{id}` | ShowsController@show | `show.show` |
| GET | `/search?query=&media_type=&sort_by=&page=` | SearchController@results | `search.results` |
| GET | `/watchlist` | WatchlistController@index | redirects guests to login |
| GET/POST/DELETE | `/watch-progress[/{tmdbId}]` | WatchProgressController | auth, JSON |
| GET | `/login/{provider}`, `/login/{provider}/envydeo/callback` | OAuthProviderController | guest only |
| — | `/login`, `/register`, `/profile`, password/verify routes | Breeze | after auth → `movie.index` |

## Home page (index.blade.php)

The hero carousel is rendered on the server from `$moviesWithDetails` and holds 15 trending movies and shows.
"Continue watching" (logged-in users only) comes from `WatchProgress`. The remaining rows are Alpine
components that `fetch()` an `/api/*` endpoint when they scroll into view: popular, top rated, a poster-collage
header, then genre rows (Action 28, Animation 16, Comedy 35, Documentary 99, History 36, Horror 27,
Romance 10749, Mystery 9648, Sci-Fi 878). Each genre row is a copy-pasted block of about 190 lines, which is a
good candidate for a shared Blade component.

## Local development (Windows / XAMPP machine)

- XAMPP's bundled PHP is 7.4, which is too old. Use the portable **PHP 8.3 at `C:\php83\php.exe`**
  (Composer: `C:\php83\composer.bat`). `php.ini` there enables the needed extensions and the curl CA bundle.
- Local `.env` uses `DB_CONNECTION=sqlite` (`database/database.sqlite`) and `CACHE_STORE=file`.
  XAMPP's MariaDB hung on basic queries. The original `.env` is saved as `.env.backup-before-sqlite`.
- Commands (run from the repo root):
  ```
  C:\php83\composer.bat install
  npm install && npm run build        # or: npm run dev  (Vite HMR)
  C:\php83\php.exe artisan migrate
  C:\php83\php.exe artisan serve --host=127.0.0.1 --port=8000   →  http://127.0.0.1:8000
  C:\php83\php.exe artisan test
  C:\php83\php.exe artisan cache:clear   # forces fresh TMDB data
  ```
- `TMDB_TOKEN` must be set in `.env` (TMDB v4 read-access token). The Google and GitHub OAuth keys and redirect URIs are optional locally.

## Conventions / gotchas

- Views build TMDB image URLs as `https://image.tmdb.org/t/p/{size}{path}`. `path` already starts with `/`.
  Use `w342` for posters and `w1280` for backdrops; avoid `original`, which is multi-MB.
- In Blade, use `{{-- --}}` to comment out code. HTML `<!-- -->` comments still execute the Blade/PHP inside them.
- Alpine comes from Livewire 3. The layouts' CDN fallback only runs if `window.Alpine` is missing.
- `routes` with `{id}` use `whereNumber('id')`.
- Branch workflow: develop on `staging`, `main` is production.
