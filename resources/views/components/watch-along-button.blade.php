{{--
    "Watch along" on title pages. Movies start right away; TV asks which episode first.
    <x-watch-along-button :tmdb-id="550" type="movie" />
    <x-watch-along-button :tmdb-id="1399" type="tv" :seasons="8" />
--}}
@props(['tmdbId', 'type' => 'movie', 'seasons' => 1, 'class' => 'btn-glass'])

@auth
    @if ($type === 'tv')
        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = !open" :aria-expanded="open" class="{{ $class }} w-full sm:w-auto">🎬 Watch along</button>
            <form x-show="open" x-cloak x-transition @click.outside="open = false" method="POST" action="{{ route('watch-along.store') }}"
                  class="absolute left-0 top-full z-30 mt-2 w-64 rounded-2xl bg-ink-900 p-4 shadow-2xl ring-1 ring-white/10">
                @csrf
                <input type="hidden" name="tmdb_id" value="{{ $tmdbId }}">
                <input type="hidden" name="type" value="tv">
                <p class="text-sm font-bold text-white">Which episode?</p>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <label class="text-xs text-gray-400">Season
                        <input type="number" name="season" min="1" max="{{ max(1, (int) $seasons) }}" value="1" class="mt-1 w-full rounded-lg bg-ink-800 px-3 py-2 text-sm text-white ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </label>
                    <label class="text-xs text-gray-400">Episode
                        <input type="number" name="episode" min="1" max="500" value="1" class="mt-1 w-full rounded-lg bg-ink-800 px-3 py-2 text-sm text-white ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </label>
                </div>
                <button type="submit" class="btn-brand mt-3 w-full !py-2 text-sm">Start Watch Along</button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('watch-along.store') }}" class="contents">
            @csrf
            <input type="hidden" name="tmdb_id" value="{{ $tmdbId }}">
            <input type="hidden" name="type" value="movie">
            <button type="submit" class="{{ $class }} w-full sm:w-auto" title="Press play together with friends, wherever they are">🎬 Watch along</button>
        </form>
    @endif
@endauth
