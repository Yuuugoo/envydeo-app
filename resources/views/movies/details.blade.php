@extends('layouts.main')

@section('title', $movie['title'])

@php
    $year = !empty($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null;
    $runtime = !empty($movie['runtime']) ? floor($movie['runtime'] / 60) . 'h ' . ($movie['runtime'] % 60) . 'm' : null;
    $videos = collect($movie['videos']['results'] ?? [])->where('site', 'YouTube');
    $trailer = $videos->firstWhere('type', 'Trailer') ?? $videos->first();
    $cast = collect($movie['credits']['cast'] ?? [])->take(12);
    $directors = collect($movie['credits']['crew'] ?? [])->where('job', 'Director')->pluck('name')->unique()->take(2);
    $money = fn ($v) => !empty($v) ? '$' . number_format($v) : null;
    $facts = array_filter([
        'Status' => $movie['status'] ?? null,
        'Release date' => !empty($movie['release_date']) ? \Carbon\Carbon::parse($movie['release_date'])->format('M j, Y') : null,
        'Director' => $directors->implode(', ') ?: null,
        'Original language' => !empty($movie['original_language']) ? strtoupper($movie['original_language']) : null,
        'Budget' => $money($movie['budget'] ?? null),
        'Revenue' => $money($movie['revenue'] ?? null),
    ]);
@endphp

@section('content')
{{-- ===================================================================== HERO --}}
<section class="relative isolate overflow-hidden">
    @if (!empty($movie['backdrop_path']))
        <img src="https://image.tmdb.org/t/p/w1280{{ $movie['backdrop_path'] }}" alt="" fetchpriority="high"
             class="absolute inset-x-0 top-0 -z-10 h-[34rem] w-full object-cover object-[center_20%] md:h-[44rem]">
    @endif
    <div class="absolute inset-x-0 top-0 -z-10 h-[34rem] bg-gradient-to-t from-ink-950 via-ink-950/70 to-ink-950/20 md:h-[44rem]"></div>
    <div class="absolute inset-x-0 top-0 -z-10 hidden h-[44rem] bg-gradient-to-r from-ink-950/90 via-ink-950/30 to-transparent md:block"></div>

    <div class="shell pt-40 md:pt-56">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end md:gap-10">
            @if (!empty($movie['poster_path']))
                <img src="https://image.tmdb.org/t/p/w342{{ $movie['poster_path'] }}" alt="{{ $movie['title'] }} poster"
                     class="hidden w-40 shrink-0 rounded-2xl shadow-2xl shadow-black/60 ring-1 ring-white/10 sm:block md:w-56 lg:w-64">
            @endif

            <div class="min-w-0 max-w-3xl">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-brand-200 ring-1 ring-white/15 backdrop-blur">
                    <x-icon name="film" class="size-3.5" /> Movie
                </span>
                <h1 class="mt-4 text-balance text-3xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-4xl lg:text-6xl">{{ $movie['title'] }}</h1>
                @if (!empty($movie['tagline']))
                    <p class="mt-2 text-base italic text-gray-300 md:text-lg">“{{ $movie['tagline'] }}”</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-medium text-gray-300">
                    @if (!empty($movie['vote_average']))
                        <span class="flex items-center gap-1 font-bold text-white"><x-icon name="star-solid" class="size-4 text-amber-400" />{{ number_format($movie['vote_average'], 1) }}</span>
                    @endif
                    @if ($year) <span>{{ $year }}</span> @endif
                    @if ($runtime) <span class="text-gray-500">•</span><span>{{ $runtime }}</span> @endif
                </div>

                @if (!empty($selectedMovieDetails['genres']))
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($selectedMovieDetails['genres'] as $genre)
                            <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-gray-200 ring-1 ring-white/10">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:gap-3" x-data="{ trailerOpen: false }">
                    <a href="#where-to-watch" class="btn-brand col-span-2 sm:col-span-1">
                        <x-icon name="tv" class="size-5" /> Where to watch
                    </a>

                    @if ($trailer)
                        <button type="button" @click="trailerOpen = true" class="btn-glass">
                            <x-icon name="trailer" class="size-5" /> Trailer
                        </button>
                    @endif

                    <livewire:add-watchlist :tmdb-id="$movie['id']" type="movie" />
                    <livewire:social.log-title :tmdb-id="$movie['id']" type="movie" />
                    @auth
                        <button type="button" @click="$dispatch('open-check-in', { tmdbId: {{ $movie['id'] }}, type: 'movie' })" class="btn-glass">
                            <x-icon name="sparkles" class="size-5" /> Watching tonight
                        </button>
                    @endauth

                    @if ($trailer)
                        <x-video-modal show="trailerOpen" src="'https://www.youtube.com/embed/{{ $trailer['key'] }}?autoplay=1'" close="trailerOpen = false" label="Trailer" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================================================================== ABOUT --}}
<div class="shell mt-12 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-14">
    <div class="min-w-0 space-y-12">
        <section>
            <h2 class="section-title">Storyline</h2>
            <p class="mt-4 max-w-3xl text-base leading-relaxed text-gray-300">{{ $movie['overview'] ?: 'No overview available.' }}</p>
        </section>

        <x-where-to-watch :data="$whereToWatch" :region="$region" :regions="$regions" />

        <livewire:social.title-social :tmdb-id="$movie['id']" type="movie" :title="$movie['title']"
                                      :imdb-id="$movie['imdb_id'] ?? null" :tmdb-rating="(float) ($movie['vote_average'] ?? 0) ?: null" />

        @if ($cast->isNotEmpty())
            <section>
                <h2 class="section-title">Top cast</h2>
                <ul class="no-scrollbar -mx-4 mt-5 flex snap-x gap-4 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:px-6 lg:mx-0 lg:px-0">
                    @foreach ($cast as $person)
                        <li class="w-24 shrink-0 snap-start text-center md:w-28">
                            @if (!empty($person['profile_path']))
                                <img src="https://image.tmdb.org/t/p/w185{{ $person['profile_path'] }}" alt="{{ $person['name'] }}" loading="lazy"
                                     class="mx-auto size-20 rounded-full object-cover ring-2 ring-white/10 md:size-24">
                            @else
                                <span class="mx-auto grid size-20 place-items-center rounded-full bg-ink-800 text-gray-600 ring-2 ring-white/10 md:size-24">
                                    <x-icon name="user" class="size-8" />
                                </span>
                            @endif
                            <p class="mt-2 line-clamp-2 text-sm font-semibold leading-tight text-white">{{ $person['name'] }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-gray-400">{{ $person['character'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    @if ($facts)
        <aside class="h-fit rounded-2xl bg-ink-900/70 p-6 ring-1 ring-white/5">
            <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Details</h2>
            <dl class="mt-4 space-y-4">
                @foreach ($facts as $label => $value)
                    <div>
                        <dt class="text-xs text-gray-500">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-gray-100">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </aside>
    @endif
</div>

{{-- ===================================================================== MORE --}}
<div class="shell mt-16"
     x-data="{
        tab: 'more',
        images: [],
        imagesLoaded: false,
        lightbox: null,
        async showImages() {
            this.tab = 'images';
            if (this.imagesLoaded) return;
            try {
                const res = await fetch('/movies/{{ $movie['id'] }}/images');
                const data = await res.json();
                this.images = (data.backdrops || []).slice(0, 18);
            } catch (e) { console.error(e); }
            this.imagesLoaded = true;
        }
     }">
    <div class="mb-8 flex gap-1 border-b border-white/10" role="tablist">
        <button type="button" role="tab" @click="tab = 'more'" :aria-selected="tab === 'more'"
                class="-mb-px border-b-2 px-4 py-3 text-sm font-semibold transition"
                :class="tab === 'more' ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white'">More like this</button>
        <button type="button" role="tab" @click="showImages()" :aria-selected="tab === 'images'"
                class="-mb-px border-b-2 px-4 py-3 text-sm font-semibold transition"
                :class="tab === 'images' ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white'">Images</button>
    </div>

    <div x-show="tab === 'more'" class="space-y-12">
        <x-poster-row title="More Like This" url="/movies/{{ $movie['id'] }}/similar" data-key="similarMovie" variant="backdrop" />
        <x-poster-row title="You Might Also Like" url="/movies/{{ $movie['id'] }}/recommended" data-key="recommendedMovie" variant="backdrop" />
    </div>

    <div x-show="tab === 'images'" x-cloak>
        <div x-show="!imagesLoaded" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 6; $i++) <div class="aspect-video animate-pulse rounded-xl bg-ink-800"></div> @endfor
        </div>
        <p x-show="imagesLoaded && !images.length" class="py-10 text-center text-sm text-gray-500">No images available.</p>
        <div x-show="imagesLoaded" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="img in images" :key="img.file_path">
                <button type="button" @click="lightbox = img.file_path" class="group overflow-hidden rounded-xl ring-1 ring-white/5">
                    <img :src="`https://image.tmdb.org/t/p/w780${img.file_path}`" alt="Still from {{ $movie['title'] }}" loading="lazy"
                         class="aspect-video w-full object-cover transition duration-500 group-hover:scale-105">
                </button>
            </template>
        </div>

        <template x-teleport="body">
            <div x-show="lightbox" x-cloak x-transition.opacity @click.self="lightbox = null" @keydown.escape.window="lightbox = null"
                 class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm">
                <button type="button" @click="lightbox = null" class="absolute right-4 top-4 grid size-10 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Close image">
                    <x-icon name="x" class="size-5" />
                </button>
                <img :src="lightbox ? `https://image.tmdb.org/t/p/w1280${lightbox}` : null" alt="" class="max-h-[85svh] w-auto max-w-full rounded-xl shadow-2xl">
            </div>
        </template>
    </div>
</div>
@endsection
