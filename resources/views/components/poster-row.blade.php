{{--
    Endless, swipeable row of posters or backdrops (logic: resources/js/components/poster-row.js).

    <x-poster-row title="Popular" url="/api/movies/popular" :genres="$genres" :href="route('movie.list')" />
    <x-poster-row title="Continue Watching" :items="$items" />
    <x-poster-row title="More Like This" url="/movies/1/similar" data-key="similarMovie" variant="backdrop" />
--}}
@props([
    'title',
    'url' => null,
    'items' => null,
    'dataKey' => 'movies',
    'type' => 'movie',
    'variant' => 'poster',
    'genres' => [],
    'href' => null,
    'limit' => 30,
])

@php
    $config = array_filter([
        'url' => $url,
        'items' => $items !== null ? collect($items)->values()->all() : null,
        'key' => $dataKey,
        'type' => $type,
        'variant' => $variant,
        'genres' => collect($genres)->all() ?: null,
        'limit' => $limit,
    ], fn ($v) => $v !== null);
    $isBackdrop = $variant === 'backdrop';
    $aspect = $isBackdrop ? 'aspect-video' : 'aspect-[2/3]';
@endphp

<section {{ $attributes->merge(['class' => 'poster-row']) }} data-variant="{{ $variant }}"
         x-data="posterRow(@js($config))" aria-label="{{ $title }}">

    <div class="mb-3 flex items-end justify-between gap-4 md:mb-4">
        <h2 class="section-title">{{ $title }}</h2>
        @if ($href)
            <a href="{{ $href }}" class="group flex shrink-0 items-center gap-0.5 text-sm font-semibold text-gray-400 transition hover:text-brand-300">
                View all <x-icon name="chevron-right" class="size-4 transition group-hover:translate-x-0.5" />
            </a>
        @endif
    </div>

    {{-- Bleeds to the screen edges; padding keeps the first card aligned with the heading --}}
    <div class="group/row relative -mx-4 [--row-pad:1rem] sm:-mx-6 sm:[--row-pad:1.5rem] lg:-mx-10 lg:[--row-pad:2.5rem]">

        {{-- Loading skeleton --}}
        <div x-show="loading" class="poster-row__track !overflow-hidden" aria-hidden="true">
            @for ($i = 0; $i < 8; $i++)
                <div class="poster-row__item">
                    <div class="{{ $aspect }} w-full animate-pulse rounded-xl bg-ink-800"></div>
                    @if ($isBackdrop)
                        <div class="mt-3 h-3.5 w-3/4 animate-pulse rounded bg-ink-800"></div>
                        <div class="mt-2 h-3 w-1/2 animate-pulse rounded bg-ink-800/70"></div>
                    @endif
                </div>
            @endfor
        </div>

        {{-- Error --}}
        <div x-show="error" x-cloak class="mx-[var(--row-pad)] flex flex-col items-center gap-3 rounded-2xl border border-dashed border-white/10 py-10 text-center">
            <p class="text-sm text-gray-400">Couldn’t load {{ strtolower($title) }}.</p>
            <button type="button" @click="load()" class="btn-glass !px-4 !py-2 text-sm">
                <x-icon name="retry" class="size-4" /> Try again
            </button>
        </div>

        {{-- Empty --}}
        <div x-show="!loading && !error && items.length === 0" x-cloak
             class="mx-[var(--row-pad)] rounded-2xl border border-dashed border-white/10 py-10 text-center text-sm text-gray-500">
            Nothing to show here yet.
        </div>

        {{-- Row --}}
        <div x-show="!loading && items.length > 0" x-cloak class="relative">
            <div x-ref="track" class="poster-row__track pb-1" role="list" tabindex="0"
                 aria-label="{{ $title }}, use arrow keys to scroll"
                 @scroll.passive="onScroll()"
                 @keydown.arrow-right.prevent="next()"
                 @keydown.arrow-left.prevent="prev()">
                <template x-for="entry in rendered" :key="entry.key">
                    <div class="poster-row__item" data-item role="listitem" :aria-hidden="entry.clone">
                        <a :href="link(entry.item)" :tabindex="entry.clone ? -1 : 0" class="group/card block rounded-xl focus-visible:outline-offset-4">
                            <div data-media class="relative {{ $aspect }} overflow-hidden rounded-xl bg-ink-800 ring-1 ring-white/5 transition duration-300 group-hover/card:ring-brand-400/60">
                                <img :src="image(entry.item)" :alt="title(entry.item)" loading="lazy" decoding="async"
                                     class="size-full object-cover transition duration-500 ease-out-soft group-hover/card:scale-105">

                                @if ($isBackdrop)
                                    <span class="absolute inset-0 grid place-items-center bg-black/0 transition group-hover/card:bg-black/30">
                                        <span class="grid size-12 scale-75 place-items-center rounded-full bg-brand-600/90 opacity-0 shadow-xl transition group-hover/card:scale-100 group-hover/card:opacity-100">
                                            <x-icon name="play-solid" class="size-5 translate-x-px text-white" />
                                        </span>
                                    </span>
                                @else
                                    <template x-if="rating(entry.item)">
                                        <span class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-black/70 px-1.5 py-0.5 text-[11px] font-bold text-white backdrop-blur">
                                            <x-icon name="star-solid" class="size-3 text-amber-400" />
                                            <span x-text="rating(entry.item)"></span>
                                        </span>
                                    </template>

                                    <div class="hover-only">
                                        <div class="absolute inset-x-0 bottom-0 translate-y-2 bg-gradient-to-t from-black via-black/75 to-transparent p-3 pt-12 opacity-0 transition duration-300 group-hover/card:translate-y-0 group-hover/card:opacity-100">
                                            <p class="line-clamp-2 text-sm font-bold leading-snug text-white" x-text="title(entry.item)"></p>
                                            <p class="mt-1 line-clamp-1 text-xs text-gray-300" x-text="[year(entry.item), genreNames(entry.item)].filter(Boolean).join(' • ')"></p>
                                        </div>
                                    </div>
                                @endif

                                <template x-if="progress(entry.item) !== null">
                                    <div class="absolute inset-x-0 bottom-0 h-1 bg-white/20">
                                        <div class="h-full rounded-r-full bg-brand-400" :style="`width: ${progress(entry.item)}%`"></div>
                                    </div>
                                </template>
                            </div>

                            @if ($isBackdrop)
                                <div class="mt-2.5 px-0.5">
                                    <p class="line-clamp-1 text-sm font-semibold text-white transition group-hover/card:text-brand-300 md:text-[15px]" x-text="title(entry.item)"></p>
                                    <p class="mt-1 flex items-center gap-1.5 text-xs text-gray-400">
                                        <template x-if="rating(entry.item)">
                                            <span class="flex items-center gap-0.5 font-semibold text-amber-400"><x-icon name="star-solid" class="size-3" /><span x-text="rating(entry.item)"></span></span>
                                        </template>
                                        <span class="line-clamp-1" x-text="[year(entry.item), genreNames(entry.item)].filter(Boolean).join(' • ')"></span>
                                    </p>
                                </div>
                            @else
                                <div class="touch-only mt-2 px-0.5">
                                    <p class="line-clamp-1 text-[13px] font-semibold text-gray-200" x-text="title(entry.item)"></p>
                                    <template x-if="progress(entry.item) !== null">
                                        <p class="text-[11px] text-gray-500" x-text="`${progress(entry.item)}% watched`"></p>
                                    </template>
                                </div>
                            @endif
                        </a>
                    </div>
                </template>
            </div>

            {{-- Edge fades + arrows (arrows only on hover-capable screens; touch users swipe) --}}
            <template x-if="canScroll">
                <div>
                    <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 w-[var(--row-pad)] bg-gradient-to-r from-ink-950 to-transparent"></div>
                    <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-0 w-[var(--row-pad)] bg-gradient-to-l from-ink-950 to-transparent"></div>

                    <button type="button" @click="prev()" aria-label="Scroll {{ $title }} left"
                            class="poster-row__arrow absolute left-1 z-10 -translate-y-1/2 lg:left-0.5"
                            :style="`top: ${arrowTop ? arrowTop + 'px' : '50%'}`">
                        <span class="grid size-10 place-items-center rounded-full bg-ink-950/80 text-white opacity-70 shadow-xl ring-1 ring-white/15 backdrop-blur transition duration-200 hover:scale-110 hover:bg-brand-600 hover:opacity-100 group-hover/row:opacity-100 lg:size-11">
                            <x-icon name="chevron-left" class="size-5" />
                        </span>
                    </button>
                    <button type="button" @click="next()" aria-label="Scroll {{ $title }} right"
                            class="poster-row__arrow absolute right-1 z-10 -translate-y-1/2 lg:right-0.5"
                            :style="`top: ${arrowTop ? arrowTop + 'px' : '50%'}`">
                        <span class="grid size-10 place-items-center rounded-full bg-ink-950/80 text-white opacity-70 shadow-xl ring-1 ring-white/15 backdrop-blur transition duration-200 hover:scale-110 hover:bg-brand-600 hover:opacity-100 group-hover/row:opacity-100 lg:size-11">
                            <x-icon name="chevron-right" class="size-5" />
                        </span>
                    </button>
                </div>
            </template>
        </div>
    </div>
</section>
