<footer class="mt-16 border-t border-white/5 bg-ink-900/60">
    <div class="shell grid gap-10 py-12 md:grid-cols-[1.4fr_1fr_1fr]">
        <div class="max-w-sm">
            <a href="{{ route('movie.index') }}" aria-label="Envydeo home" class="inline-block">
                <x-logo />
            </a>
            <p class="mt-4 text-sm leading-relaxed text-gray-400">
                Discover what's trending, keep a watchlist, and pick up right where you left off.
            </p>
        </div>

        <nav aria-label="Browse">
            <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Browse</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('movie.index') }}" class="text-gray-300 transition hover:text-brand-300">Home</a></li>
                <li><a href="{{ route('movie.list') }}" class="text-gray-300 transition hover:text-brand-300">Movies</a></li>
                <li><a href="{{ route('show.list') }}" class="text-gray-300 transition hover:text-brand-300">TV Shows</a></li>
            </ul>
        </nav>

        <nav aria-label="Account">
            <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Account</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @auth
                    <li><a href="{{ route('watchlist.index') }}" class="text-gray-300 transition hover:text-brand-300">Watchlist</a></li>
                    <li><a href="{{ route('profile.edit') }}" class="text-gray-300 transition hover:text-brand-300">Profile</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="text-gray-300 transition hover:text-brand-300">Sign in</a></li>
                    <li><a href="{{ route('register') }}" class="text-gray-300 transition hover:text-brand-300">Create account</a></li>
                @endauth
            </ul>
        </nav>
    </div>

    <div class="border-t border-white/5">
        <div class="shell flex flex-col gap-2 py-6 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} Envydeo. All rights reserved.</p>
            <p>
                Movie &amp; TV data from
                <a href="https://www.themoviedb.org/" target="_blank" rel="noopener" class="font-semibold text-gray-400 hover:text-brand-300">TMDB</a>.
                This product uses the TMDB API but is not endorsed or certified by TMDB.
            </p>
        </div>
    </div>
</footer>
