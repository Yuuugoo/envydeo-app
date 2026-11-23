{{-- "Us" rooms: every movie both people liked, newest first. --}}
<section wire:key="state-our-matches" class="mt-8" x-data="{ playing: null }">
    @if ($results->isEmpty())
        <x-empty-state icon="heart" title="No matches yet"
                       message="{{ $participants->count() < 2 ? 'Invite your partner, then keep swiping. Movies you both like will appear here.' : 'Keep swiping. Every movie you both like lands here.' }}">
            <button type="button" wire:click="$set('tab', 'swipe')" class="btn-brand">Start swiping</button>
        </x-empty-state>
    @else
        <p class="mb-6 text-sm text-gray-400">
            {{ $results->count() }} {{ \Illuminate\Support\Str::plural('movie', $results->count()) }} you both love
            · {{ $results->whereNull('watched_at')->count() }} still to watch
        </p>
        <ul class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            @foreach ($results as $result)
                @php $movie = $result->movie; @endphp
                <li wire:key="result-{{ $result->id }}" class="{{ $result->watched_at ? 'opacity-60' : '' }}">
                    <div class="group relative aspect-[2/3] overflow-hidden rounded-xl bg-ink-800 ring-1 ring-white/5">
                        @if (!empty($movie['poster_path']))
                            <img src="https://image.tmdb.org/t/p/w342{{ $movie['poster_path'] }}" alt="{{ $movie['title'] ?? '' }}" loading="lazy" class="size-full object-cover">
                        @endif
                        @if ($result->watched_at)
                            <span class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-brand-600 px-1.5 py-0.5 text-[11px] font-bold text-white"><x-icon name="check" class="size-3" /> Watched</span>
                        @elseif ($result->created_at->gt(now()->subDay()))
                            <span class="absolute left-2 top-2 rounded-md bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold text-white">New</span>
                        @endif
                        <button type="button" @click="playing = 'https://vidfast.pro/movie/{{ $result->tmdb_id }}?theme=16A085'"
                                class="absolute inset-0 grid place-items-center bg-black/0 opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100 focus-visible:opacity-100"
                                aria-label="Play {{ $movie['title'] ?? 'movie' }}">
                            <span class="grid size-12 place-items-center rounded-full bg-brand-600/90 text-white shadow-xl"><x-icon name="play-solid" class="size-5 translate-x-px" /></span>
                        </button>
                    </div>
                    <a href="{{ route('movie.show', $result->tmdb_id) }}" class="mt-2.5 line-clamp-1 block text-sm font-semibold text-white hover:text-brand-300">{{ $movie['title'] ?? 'Untitled' }}</a>
                    <p class="text-xs text-gray-500">Matched {{ $result->created_at->diffForHumans() }}</p>
                    <div class="mt-2 flex gap-1.5">
                        <button type="button" wire:click="toggleWatched({{ $result->id }})"
                                class="flex-1 rounded-lg px-2 py-1.5 text-xs font-semibold ring-1 transition {{ $result->watched_at ? 'bg-brand-600/20 text-brand-200 ring-brand-500/40' : 'text-gray-300 ring-white/10 hover:bg-white/5' }}">
                            {{ $result->watched_at ? 'Watched ✓' : 'Mark watched' }}
                        </button>
                        <a href="{{ route('movie-night.card', $room->code) }}?movie={{ $result->tmdb_id }}" target="_blank" rel="noopener"
                           class="grid place-items-center rounded-lg px-2 text-gray-300 ring-1 ring-white/10 transition hover:bg-white/5" aria-label="Share card" title="Share card">
                            <x-icon name="share" class="size-4" />
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <x-video-modal show="playing" src="playing" close="playing = null" label="Movie player" />
</section>
