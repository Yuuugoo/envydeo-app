# Envydeo — Project Structure & Guide

Envydeo is a movie / TV-show discovery site built on the **TALL stack**
(Tailwind CSS 4 + DaisyUI 5, Alpine.js, Laravel 12, Livewire 3), centred on **Movie Night Matcher**.
All catalogue data comes from the **TMDB API**; the local database stores users, OAuth links,
watchlists and Movie Night rooms. Envydeo does **not** stream video: titles link to legal
services via "Where to watch" (TMDB watch providers, data by JustWatch), and trailers are YouTube embeds.

> Branches: `wo-vidfast` (this direction) removed the old third-party `vidfast` player and the watch-progress
> feature that depended on it. `w-vidfast` is a snapshot of the site with the player. Don't reintroduce
> embedded streaming players — `tests/Feature/MovieNightTest.php` asserts pages contain no `vidfast`.

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
    OAuthProviderController.php      Socialite redirect + callback, avatar import via medialibrary
    ProfileController.php, Auth/*    Laravel Breeze (login, register, reset, verify, profile)
  Livewire/
    SearchDropdown.php               navbar live search (TMDB /search/multi, debounced 400ms)
    AddWatchlist.php                 toggle a tmdb_id in the user's watchlist
    AvatarDropdown.php               navbar user menu
  Models/  User (UUID PK, HasMedia 'avatar'), OAuthProvider, Watchlist, MatchRoom/MatchParticipant/MatchVote/MatchResult, MovieNightEvent
  Services/Tmdb.php                  TMDB client + cache (see above)
  View/Components/  AppLayout, GuestLayout
database/migrations/                 users (uuid), cache, jobs, media, o_auth_providers, watchlist, movie night tables (watch_progress dropped)
resources/
  css/app.css                        Tailwind 4 + DaisyUI plugin + @source globs
  js/app.js, bootstrap.js            axios setup (Alpine itself comes from Livewire)
  views/
    layouts/main.blade.php           main layout used by all site pages (@extends + @section('content'))
    layouts/app|guest.blade.php      Breeze component layouts (<x-app-layout>/<x-guest-layout>)
    layouts/nav.blade.php            top nav + DaisyUI mobile drawer; footer.blade.php
    index.blade.php                  HOME for visitors: Movie Night landing (demo deck, moods, most matched)
    home/tonight.blade.php           HOME for signed-in users: Livewire social.tonight-home
    movies/index|details.blade.php   now-playing grid w/ pagination; movie page (where to watch, trailer, tabs)
    shows/index|details.blade.php    trending TV grid; show page (episodes by season, similar, images)
    results.blade.php                search results with media-type / sort filters
    watchlist.blade.php              user's watchlist grid
    livewire/*.blade.php             Livewire component views
    auth/*, profile/*                Breeze views
    components/*                     logo, icon, poster-row, media-card, pagination, video-modal, empty-state + Breeze form parts
routes/web.php                       site + /api routes; routes/auth.php Breeze routes
public/img/logo/                     Envydeo logos; public/build = Vite output (git-ignored)
```

## Routes

| Method | URI | Handler | Notes |
|---|---|---|---|
| GET | `/` | MovieController@index | name `movie.index`. Landing page for visitors, Tonight for signed-in users (see Home page) |
| GET | `/movies?page=` | MovieController@list | `movie.list`, TMDB now_playing |
| GET | `/movies/{id}` | MovieController@show | `movie.show`, numeric id, 404 if not found |
| GET | `/movies/{id}/similar`, `/recommended`, `/images` | MovieController | JSON for the details-page tabs |
| GET | `/api/movies/popular`, `/api/movies/top-rated`, `/api/movies/collage`, `/api/tv/popular` | MovieController | JSON for the home page |
| GET | `/api/movies/genre/{id}` | MovieController@loadMoviesByGenre | 3 discover pages, fetched in parallel |
| GET | `/shows?page=` | ShowsController@list | `show.list` |
| GET | `/shows/{id}` | ShowsController@show | `show.show` |
| GET | `/search?query=&media_type=&sort_by=&page=` | SearchController@results | `search.results` |
| GET | `/watchlist` | WatchlistController@index | redirects guests to login |
| GET | `/login/{provider}`, `/login/{provider}/envydeo/callback` | OAuthProviderController | guest only |
| — | `/login`, `/register`, `/profile`, password/verify routes | Breeze | after auth → `movie.index` |

## Home page: Movie Night first, not a streaming catalogue

**Identity:** Envydeo is about deciding and watching *together tonight*. It is not a streaming library (Netflix) and not a film diary (Letterboxd). The owner chose (Nov 2026) to drop the autoplay backdrop carousel and genre rows for this reason. Don't bring back streaming-style heroes, "Play/Continue watching" language, star ratings or diary framing.

`MovieController@index` serves two pages:
- **Visitors** (and signed-in users without a username) get `index.blade.php`, the landing page:
  - Hero copy plus a **playable demo deck**: `swipeDeck({ demo: true })`. Likes stay in the browser, and after 3 likes it shows "Swipe with friends". The cards come from `/trending/movie/week`, cached 6h (`home:demo-deck`).
  - "How a movie night works", mood presets (`MovieNightThemes`, posting to `/movie-night/quick`), and a "Movie night doesn't end at the match" feature trio.
  - **Most matched this week:** built from anonymous `matched` events, whose `meta.tmdb_id` is recorded in `Room`. It is cached 1h (`home:most-matched`) and needs at least 4 titles; otherwise one popular poster row is shown instead.
- **Signed-in users with a username** get `home/tonight.blade.php`, which holds the Livewire `social.tonight-home`:
  - Quick actions and the friends' live check-in strip.
  - **"You both want to watch":** watchlist overlaps with the people you follow. `startNight()` creates a `source=watchlist` room and sends `MovieNightInvite` to those people; it is rate-limited.
  - Your open rooms, a short "Lately with friends" slice of the feed, and one popular row.
- Nav order is Tonight/Home, Movie Night, Friends (`/feed`), Movies, TV Shows. The mobile tab bar is Tonight, Movie Night, +, Friends, Profile.

## Movie Night Matcher (`/movie-night`), the signature feature

Friends swipe the same deck of movies, each on their own device. The first movie **everyone** likes is the match.

- **Code:**
  - `MovieNightController`: landing page, join-by-code, room page.
  - Livewire `MovieNight\CreateRoom`: the setup form.
  - Livewire `MovieNight\Room`: lobby, voting, closest picks and the match screen. It polls every 2s; no websockets.
  - `resources/js/components/swipe-deck.js`: Alpine drag/tap/keyboard deck. Votes go to `Room::vote()`, which is `#[Renderless]`.
- **Data:**
  - `match_rooms` holds the filters and the dealt deck as JSON. Status is `lobby`, `voting` or `matched`; rooms expire after 24h and are pruned daily (`routes/console.php`).
  - `match_participants`: guests are allowed. Identity is a secret token kept in the session, and only its sha256 is stored.
  - `match_votes`
- **Decks:** `App\Services\MovieNightDealer` builds them from TMDB `/discover/movie` (genres, runtime, rating, region, streaming services) and provides "where to watch". Streaming data comes from JustWatch via TMDB, so keep the "data by JustWatch" credit.
- **Rules (covered in `tests/Feature/MovieNightTest.php`):**
  - A match needs a like from every participant.
  - Only the host can start, pick, deal more cards, or start a new round.
  - Votes for movies outside the deck, or from non-participants, are ignored.
- **Test database:** tests run on in-memory SQLite (`phpunit.xml`) and never touch the dev database.

### Growth features
- **Room creation:** `App\Services\MovieNightRooms` is the one place rooms are created and decks are dealt. The create form, "Decide for me", themes, Watchlist Showdown and "Us" rooms all go through it.
- **"Decide for me":** `POST /movie-night/quick` with an optional `theme`. It makes a solo room that is already voting, and the first like is "Tonight's pick".
- **Themes:** `App\Support\MovieNightThemes` holds whitelisted TMDB discover presets. Only these keys are accepted.
- **Watchlist Showdown:** `source=watchlist`. When the host starts, the deck is rebuilt from the watchlists of every signed-in participant, with titles saved by more people first.
- **"Us" rooms:** `is_persistent`, no expiry, a maximum of 2 people, and an account is required to join.
  - A match always needs 2 likes.
  - Matches collect in `match_results` and appear in the "Our matches" tab.
  - The deck refills automatically.
  - Logged-in users are recognised by account across devices (`MatchRoom::participantFor`). A leftover session token is never honoured for a different account.
- **Sharing images:** `App\Services\MovieNightCards` draws them with GD and the bundled Figtree TTF in `resources/fonts`. The font is a Latin subset, so the images contain no ★ or emoji. Images are cached in `storage/app/private/movie-night-cards`.
  - `/{code}/preview.png`: 1200×630 Open Graph invite or match image. `show.blade.php` pushes the `og:` meta tags.
  - `/{code}/card.png`: 1080×1350 story card.
  - Do not call `imageantialias()`: GD then ignores alpha and gradients turn into solid bands.
- **QR code:** `bacon/bacon-qr-code` renders an inline SVG in the lobby, and in "Us" rooms while waiting for the partner.
- **Stats:** `movie_night_events` stores anonymous events with no names, emails, IPs or user ids. `/movie-night/stats` is gated by `view-movie-night-stats`, meaning emails listed in the `ADMIN_EMAILS` env var (`config/envydeo.php`).

## Social layer (followers, Tonight check-ins, diary, feed)

- **Privacy rules live in one place:** `App\Services\SocialGraph`, registered as a `scoped` singleton and memoised per request.
  - Follows are Instagram-style. A follow of a private account is `pending` until it is accepted.
  - **Friends** are mutual accepted follows.
  - A block hides both people from each other and deletes follows in both directions.
  - In tests, call `$this->app->forgetScopedInstances()` after changing follows or blocks directly in the DB.
- **Username:** required for social pages through the `username` middleware (`EnsureUsername` → `/welcome`). Profiles live at `/@{username}`. `User::USERNAME_RULES` and `RESERVED_USERNAMES` hold the rules.
- **Tonight (our take on Locket):** `CheckIn` holds a title, an optional photo and a caption, and expires after 24h.
  - Check-ins are **friends-only**. Photos are re-encoded by `App\Services\CheckInPhotos` with GD, which strips EXIF/GPS and caps them at 1440px.
  - Photos are stored on the private `local` disk and served only through `/check-ins/{id}/photo` to friends or moderators.
  - Reactions come from `CheckInReaction::REACTIONS`. "I'm in" (🙋) also adds the movie to the reactor's watchlist.
  - The composer is the global Livewire `social.check-in-composer`. Open it by dispatching `open-check-in`.
- **Verdicts (not star ratings):** each `DiaryEntry` has a `verdict` from `DiaryEntry::VERDICTS` (loved 😍 / good 👍 / meh 😐 / skip 👎), an optional `best_with_friends` tag (🍿), a short "take" (the `review` column), a spoiler flag and a watched date.
  - The UI says "I watched this". `<x-verdict>` renders the pill, and `watchedWhen()` gives "tonight", "last night" or "on Monday".
  - `ReviewLike` is shown as "🤝 Same" (agree).
  - Title pages show "% recommend" (loved + good) with a breakdown bar and friends' verdicts.
  - The 2026_11_24_120000 migration converted the old 1–10 ratings to verdicts.
  - Profiles show Watched / Loved / Movie Night matches, an "Always down to watch" list of 4 picks (`top_films`), and a poster grid with verdict badges.
- **Feed:** `App\Services\Feed` provides activities (people you follow plus yourself; `friends` visibility only for mutuals), the Tonight strip and suggestions. `Activity::record()` writes the activity rows.
- **Movie & show pages:** `social.log-title`, a "Watching tonight" button and the lazy `social.title-social` component, which holds:
  - "The verdict": Envydeo verdicts first, then TMDB, plus OMDb's IMDb/RT/Metacritic when `OMDB_API_KEY` is set.
  - Links to Letterboxd, IMDb and RT.
  - Friends who want to watch the title, with a "Start a movie night" invite.
  - "Takes" tabs.
- **Notifications:** Laravel database notifications plus web push (`laravel-notification-channels/webpush`). All of them extend `App\Notifications\SocialNotification`.
  - VAPID keys are in `.env`, generated with `php artisan webpush:vapid`. On Windows, first set `OPENSSL_CONF=C:\php83\extras\ssl\openssl.cnf`.
  - Push needs HTTPS in production. On iPhone it only works from the installed PWA.
- **Safety:** block, report (`Report`, rate-limited) and `/admin/reports` behind the `moderate` gate, which uses the same `ADMIN_EMAILS` as stats.
- Tests are in `tests/Feature/SocialTest.php`.

## UI system (Tailwind 4 + DaisyUI 5)

- **Theme and tokens:** `resources/css/app.css` defines a single dark DaisyUI theme `envydeo` (`data-theme` on `<html>`). Tokens are `brand-*` (logo green), `ink-*` (surfaces) and the Figtree font. Utilities: `shell` (page container), `page-top` (clears the fixed nav), `section-title`, `btn-brand`, `btn-glass`, `no-scrollbar`.
- **Alpine:** it comes from Livewire 3. `resources/js/app.js` only registers `Alpine.data(...)` components on `alpine:init`. Never import or start a second Alpine.
- **Blade components:**
  - `<x-logo>`: SVG eye/play mark plus the "envy**deo**" wordmark. `public/favicon.svg` uses the same mark.
  - `<x-icon name="…">`: inline Heroicons; names ending in `-solid` are filled.
  - `<x-poster-row>`: the **endless** swipeable row (`resources/js/components/poster-row.js`). It renders 3 copies and silently re-centres after each scroll, so the arrows and swipes never hit an end. It has `poster` and `backdrop` variants.
  - `<x-media-card>`: grid card for movies, TV, search and watchlist.
  - `<x-pagination>`, `<x-video-modal>` (teleported iframe modal), `<x-empty-state>`.
- **Layouts:** `layouts/main` (`@extends`) and `layouts/app` / `layouts/guest` (Breeze components) share `layouts/partials/head`. The nav is `layouts/nav.blade.php`: fixed and transparent, turning solid on scroll, with a drawer below `lg`.
- **Responsive targets:** verified at 360, 390, 768, 1024 and 1440px with no horizontal overflow.

## "Where to watch" country & watchlist

- **Country:** `App\Services\ViewerRegion::resolve()` decides it. Never hard-code `US`. The order is:
  1. The `region` cookie, set by the picker in `<x-where-to-watch :regions>` via `POST /region` (`auto` clears it).
  2. `CF-IPCountry`, only when the request came through a trusted proxy.
  3. An **offline** GeoIP lookup of the IP: DB-IP Lite at `storage/app/geoip/dbip-country-lite.mmdb`, read with `maxmind-db/reader`. IPs are never sent anywhere or stored.
  4. The browser language.
  5. US.
- **Countries offered:** the list comes from TMDB `/watch/providers/regions` (about 140, cached a week). Movie Night rooms still use the shorter `MovieNightDealer::REGIONS` via `resolveForMovieNight()`.
- **GeoIP database:** download it with `php artisan envydeo:geoip`, which is scheduled monthly. Run it once on every new server. The credit "IP Geolocation by DB-IP" in the footer is required by the CC BY license.
- **Watchlist:** it holds movies **and** TV, with `media_type` set to `movie` or `tv`. Use `Watchlist::movies()` wherever only movies make sense (Movie Night decks, "movie night with them"). `<livewire:add-watchlist :tmdb-id type="tv">`.

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
