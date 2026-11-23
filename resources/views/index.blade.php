@extends('layouts.main')

@php
    $slides = collect($moviesWithDetails)->values();
    $genreRows = [
        28 => 'Action',
        16 => 'Animation',
        35 => 'Comedy',
        878 => 'Science Fiction',
        27 => 'Horror',
        10749 => 'Romance',
        9648 => 'Mystery',
        99 => 'Documentary',
        36 => 'History',
    ];
@endphp

@section('content')

{{-- ===================================================================== HERO --}}
@if ($slides->isNotEmpty())
    <section
        x-data="heroCarousel({{ $slides->count() }})"
        @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)"
        @keydown.arrow-left="prev()" @keydown.arrow-right="next()"
        class="relative isolate h-[80svh] min-h-[34rem] w-full overflow-hidden bg-ink-950 md:h-[88svh] md:max-h-[56rem]"
        aria-roledescription="carousel" aria-label="Featured titles">

        @foreach ($slides as $i => $slide)
            @php
                $isTv = $slide['media_type'] === 'TV';
                $backdrop = 'https://image.tmdb.org/t/p/w1280' . $slide['backdrop_path'];
                $year = !empty($slide['release_date']) ? substr($slide['release_date'], 0, 4) : null;
                $length = $isTv
                    ? (!empty($slide['number_of_seasons']) ? $slide['number_of_seasons'] . ' ' . \Illuminate\Support\Str::plural('Season', $slide['number_of_seasons']) : null)
                    : (!empty($slide['runtime']) ? floor($slide['runtime'] / 60) . 'h ' . ($slide['runtime'] % 60) . 'm' : null);
                $detailsUrl = route($isTv ? 'show.show' : 'movie.show', $slide['id']);
            @endphp

            <div class="absolute inset-0 transition-opacity duration-700 ease-out"
                 :class="current === {{ $i }} ? 'z-10 opacity-100' : 'pointer-events-none z-0 opacity-0'"
                 :aria-hidden="current !== {{ $i }}"
                 aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $slides->count() }}">

                <img @if ($i === 0) src="{{ $backdrop }}" fetchpriority="high" @else x-bind:src="loaded[{{ $i }}] ? '{{ $backdrop }}' : null" @endif
                     alt="" class="absolute inset-0 size-full object-cover object-[center_25%]">

                <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/50 to-ink-950/10"></div>
                <div class="absolute inset-0 hidden bg-gradient-to-r from-ink-950/95 via-ink-950/40 to-transparent md:block"></div>

                <div class="shell relative flex h-full flex-col justify-end pb-24 md:pb-28">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-brand-200 ring-1 ring-white/15 backdrop-blur">
                            <x-icon :name="$isTv ? 'tv' : 'film'" class="size-3.5" />
                            {{ $isTv ? 'TV Series' : 'Movie' }} · Trending
                        </span>

                        <h2 class="mt-4 text-balance text-4xl font-extrabold leading-[1.02] tracking-tight text-white drop-shadow-lg sm:text-5xl lg:text-6xl">
                            {{ $slide['title'] }}
                        </h2>

                        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-medium text-gray-300">
                            @if (!empty($slide['vote_average']))
                                <span class="flex items-center gap-1 font-bold text-white"><x-icon name="star-solid" class="size-4 text-amber-400" />{{ number_format($slide['vote_average'], 1) }}</span>
                            @endif
                            @if ($year) <span>{{ $year }}</span> @endif
                            @if ($length) <span class="text-gray-500">•</span><span>{{ $length }}</span> @endif
                            @if (!empty($slide['genres']))
                                <span class="text-gray-500">•</span>
                                <span>{{ implode(', ', array_slice($slide['genres'], 0, 3)) }}</span>
                            @endif
                        </div>

                        <p class="mt-4 line-clamp-3 max-w-xl text-sm leading-relaxed text-gray-300 md:text-base">
                            {{ $slide['overview'] }}
                        </p>

                        <div class="mt-6 grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:gap-3">
                            <a href="{{ $detailsUrl }}" class="btn-brand col-span-2 sm:col-span-1">
                                <x-icon name="info" class="size-5" /> Details &amp; where to watch
                            </a>

                            @if (!empty($slide['trailer_key']))
                                <button type="button" @click="openVideo('https://www.youtube.com/embed/{{ $slide['trailer_key'] }}?autoplay=1')" class="btn-glass">
                                    <x-icon name="trailer" class="size-5" /> Trailer
                                </button>
                            @endif

                            @unless ($isTv)
                                <livewire:add-watchlist :tmdb-id="$slide['id']" :key="'hero-' . $slide['id']" />
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Controls --}}
        <div class="shell absolute inset-x-0 bottom-7 z-20 flex items-center justify-center gap-4 md:bottom-10 md:justify-end">
            <button type="button" @click="prev()" aria-label="Previous slide"
                    class="hidden size-11 place-items-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 md:grid">
                <x-icon name="chevron-left" class="size-5" />
            </button>
            <div class="flex items-center gap-1.5">
                @foreach ($slides as $i => $slide)
                    <button type="button" @click="go({{ $i }})" aria-label="Go to slide {{ $i + 1 }}"
                            class="h-1.5 rounded-full transition-all duration-300"
                            :class="current === {{ $i }} ? 'w-7 bg-brand-400' : 'w-1.5 bg-white/40 hover:bg-white/70'"></button>
                @endforeach
            </div>
            <button type="button" @click="next()" aria-label="Next slide"
                    class="hidden size-11 place-items-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 md:grid">
                <x-icon name="chevron-right" class="size-5" />
            </button>
        </div>

        <x-video-modal show="video" src="video" close="closeVideo()" />
    </section>
@endif

{{-- ===================================================================== ROWS --}}
<div class="relative z-20 {{ $slides->isNotEmpty() ? '-mt-6 md:-mt-10' : 'page-top' }}">
    <div class="shell space-y-10 md:space-y-14">
        <x-poster-row title="Popular Movies" url="/api/movies/popular" :genres="$genres" :href="route('movie.list')" />
        <x-poster-row title="Trending TV Shows" url="/api/tv/popular" data-key="shows" type="tv" :genres="$tvGenres" :href="route('show.list')" />
        <x-poster-row title="Top Rated" url="/api/movies/top-rated" :genres="$genres" />

        {{-- Movie Night promo --}}
        <section class="group relative isolate flex flex-col gap-6 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700/40 via-ink-900 to-fuchsia-900/30 p-6 ring-1 ring-white/10 sm:p-10 md:flex-row md:items-center md:justify-between">
            <div aria-hidden="true" class="absolute -right-16 -top-16 -z-10 size-64 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div class="max-w-xl">
                <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.18em] text-brand-300"><x-icon name="sparkles" class="size-4" /> New · Movie Night</p>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white md:text-4xl">Can’t decide what to watch?</h2>
                <p class="mt-2 text-sm text-gray-300 md:text-base">Swipe solo and your first ♥ is tonight’s pick, or swipe with friends and the first one you all like wins.</p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3">
                <form method="POST" action="{{ route('movie-night.quick') }}">
                    @csrf
                    <button type="submit" class="btn-brand"><x-icon name="sparkles" class="size-5" /> Decide for me</button>
                </form>
                <a href="{{ route('movie-night.index') }}" class="btn-glass"><x-icon name="users" class="size-5" /> With friends</a>
            </div>
        </section>
    </div>

    {{-- Discover by genre --}}
    <section
        x-data="{ posters: [], async init() { try { const r = await fetch('/api/movies/collage'); const d = await r.json(); this.posters = (d.movies || []).map(m => m.poster_path).filter(Boolean).slice(0, 16); } catch (e) {} } }"
        class="shell mt-16 md:mt-20" aria-labelledby="discover-heading">
        <div class="relative isolate overflow-hidden rounded-3xl bg-ink-900 px-6 py-12 ring-1 ring-white/10 sm:px-10 md:py-16">
            <div aria-hidden="true" class="absolute inset-0 -z-10 grid grid-cols-4 gap-1.5 opacity-25 sm:grid-cols-6 lg:grid-cols-8 [mask-image:linear-gradient(to_bottom,black,transparent)]">
                <template x-for="poster in posters" :key="poster">
                    <img :src="`https://image.tmdb.org/t/p/w185${poster}`" alt="" loading="lazy" class="aspect-[2/3] w-full object-cover">
                </template>
            </div>
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-br from-ink-950/80 via-ink-950/60 to-brand-900/40"></div>

            <div class="mx-auto max-w-2xl text-center">
                <h2 id="discover-heading" class="text-3xl font-extrabold tracking-tight text-white md:text-5xl">
                    Discover by <span class="bg-gradient-to-r from-brand-300 to-sky-300 bg-clip-text text-transparent">genre</span>
                </h2>
                <p class="mt-3 text-sm text-gray-300 md:text-lg">
                    From heart-pounding action to spine-chilling horror — jump straight to what you’re in the mood for.
                </p>
            </div>

            <div class="no-scrollbar -mx-6 mt-8 flex gap-2 overflow-x-auto px-6 sm:mx-0 sm:flex-wrap sm:justify-center sm:px-0">
                @foreach ($genreRows as $id => $name)
                    <a href="#genre-{{ $id }}" class="shrink-0 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-gray-100 ring-1 ring-white/10 backdrop-blur transition hover:bg-brand-600 hover:ring-brand-400">
                        {{ $name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <div class="shell mt-12 space-y-10 md:mt-16 md:space-y-14">
        @foreach ($genreRows as $id => $name)
            <x-poster-row id="genre-{{ $id }}" class="scroll-mt-24" :title="$name" :url="'/api/movies/genre/' . $id" :genres="$genres" />
        @endforeach
    </div>
</div>
@endsection
