<div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false" class="relative">
    <button type="button"
            @click="open = !open; if (open) $nextTick(() => $refs.input.focus())"
            class="grid size-10 place-items-center rounded-full text-gray-200 transition hover:bg-white/10 hover:text-white"
            :class="open && 'bg-white/10 text-white'"
            aria-label="Search" :aria-expanded="open">
        <x-icon name="search" class="size-5" />
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-x-3 top-[4.25rem] z-50 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-3 sm:w-[24rem]">
        <div class="overflow-hidden rounded-2xl bg-ink-900/95 shadow-2xl shadow-black/50 ring-1 ring-white/10 backdrop-blur-xl">
            <form wire:submit.prevent="searchAll" class="relative border-b border-white/5">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-gray-500" />
                <input x-ref="input"
                       wire:model.live.debounce.400ms="search"
                       type="search"
                       class="w-full bg-transparent py-4 pl-12 pr-12 text-sm text-white placeholder:text-gray-500 focus:outline-none"
                       placeholder="Search movies & TV shows…"
                       aria-label="Search movies and TV shows"
                       autocomplete="off">
                <span wire:loading class="loading loading-spinner loading-sm absolute right-4 top-1/2 -translate-y-1/2 text-brand-400"></span>
            </form>

            @if (strlen($search) >= 2)
                <div class="max-h-[60vh] overflow-y-auto">
                    @if ($searchResults->count() > 0)
                        <ul class="divide-y divide-white/5">
                            @foreach ($searchResults as $result)
                                @php
                                    $isMovie = $result['media_type'] === 'movie';
                                    $title = $isMovie ? ($result['title'] ?? '') : ($result['name'] ?? '');
                                    $year = substr(($isMovie ? $result['release_date'] ?? '' : $result['first_air_date'] ?? ''), 0, 4);
                                @endphp
                                <li>
                                    <a href="{{ route($isMovie ? 'movie.show' : 'show.show', $result['id']) }}"
                                       class="flex items-center gap-3 px-4 py-3 transition hover:bg-white/5">
                                        @if (!empty($result['poster_path']))
                                            <img src="https://image.tmdb.org/t/p/w92{{ $result['poster_path'] }}" alt="" class="h-14 w-10 shrink-0 rounded-md object-cover">
                                        @else
                                            <span class="grid h-14 w-10 shrink-0 place-items-center rounded-md bg-ink-800 text-gray-600">
                                                <x-icon :name="$isMovie ? 'film' : 'tv'" class="size-4" />
                                            </span>
                                        @endif
                                        <span class="min-w-0 flex-1">
                                            <span class="line-clamp-1 text-sm font-semibold text-white">{{ $title }}</span>
                                            <span class="mt-1 flex items-center gap-2 text-xs text-gray-400">
                                                <span class="rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-300">{{ $isMovie ? 'Movie' : 'TV' }}</span>
                                                @if ($year) <span>{{ $year }}</span> @endif
                                                @if (!empty($result['vote_average']))
                                                    <span class="flex items-center gap-0.5 text-amber-400"><x-icon name="star-solid" class="size-3" />{{ number_format($result['vote_average'], 1) }}</span>
                                                @endif
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('search.results', ['query' => $search]) }}"
                           class="block border-t border-white/5 px-4 py-3 text-center text-sm font-semibold text-brand-300 transition hover:bg-white/5">
                            See all results for “{{ $search }}”
                        </a>
                    @else
                        <p class="px-4 py-6 text-center text-sm text-gray-400">No results for “{{ $search }}”</p>
                    @endif
                </div>
            @else
                <p class="px-4 py-4 text-xs text-gray-500">Type at least 2 characters · press Enter to see everything</p>
            @endif
        </div>
    </div>
</div>
