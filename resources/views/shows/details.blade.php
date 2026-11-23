@extends('layouts.main')

@section('title', $tvShow['name'])

@php
    $firstYear = !empty($tvShow['first_air_date']) ? substr($tvShow['first_air_date'], 0, 4) : null;
    $lastYear = !empty($tvShow['last_air_date']) ? substr($tvShow['last_air_date'], 0, 4) : null;
    $years = $firstYear ? ($lastYear && $lastYear !== $firstYear ? "{$firstYear}–{$lastYear}" : $firstYear) : null;
    $seasonCount = $tvShow['number_of_seasons'] ?? 0;
    $videos = collect($tvShow['videos']['results'] ?? [])->where('site', 'YouTube');
    $trailer = $videos->firstWhere('type', 'Trailer') ?? $videos->first();
    $cast = collect($tvShow['credits']['cast'] ?? [])->take(12);
    $backdrops = array_slice($tvShow['images']['backdrops'] ?? [], 0, 18);
    $seasonNumbers = array_keys($episodes);
    $facts = array_filter([
        'Status' => $tvShow['status'] ?? null,
        'Network' => collect($tvShow['networks'] ?? [])->pluck('name')->take(2)->implode(', ') ?: null,
        'Created by' => collect($tvShow['created_by'] ?? [])->pluck('name')->take(2)->implode(', ') ?: null,
        'First aired' => !empty($tvShow['first_air_date']) ? \Carbon\Carbon::parse($tvShow['first_air_date'])->format('M j, Y') : null,
        'Episodes' => !empty($tvShow['number_of_episodes']) ? number_format($tvShow['number_of_episodes']) : null,
        'Original language' => !empty($tvShow['original_language']) ? strtoupper($tvShow['original_language']) : null,
    ]);
@endphp

@section('content')
<div x-data="{
        tab: 'episodes',
        season: {{ $seasonNumbers[0] ?? 1 }},
        episodes: @js($episodes),
        playing: null,
        trailerOpen: false,
        lightbox: null,
        tvId: {{ $tvShow['id'] }},
        play(seasonNumber, episodeNumber) {
            this.playing = `https://vidfast.pro/tv/${this.tvId}/${seasonNumber}/${episodeNumber}?nextButton=true&autoNext=true&theme=16A085`;
        },
        airDate(date) {
            return date ? new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'TBA';
        }
     }">

{{-- ===================================================================== HERO --}}
<section class="relative isolate overflow-hidden">
    @if (!empty($tvShow['backdrop_path']))
        <img src="https://image.tmdb.org/t/p/w1280{{ $tvShow['backdrop_path'] }}" alt="" fetchpriority="high"
             class="absolute inset-x-0 top-0 -z-10 h-[34rem] w-full object-cover object-[center_20%] md:h-[44rem]">
    @endif
    <div class="absolute inset-x-0 top-0 -z-10 h-[34rem] bg-gradient-to-t from-ink-950 via-ink-950/70 to-ink-950/20 md:h-[44rem]"></div>
    <div class="absolute inset-x-0 top-0 -z-10 hidden h-[44rem] bg-gradient-to-r from-ink-950/90 via-ink-950/30 to-transparent md:block"></div>

    <div class="shell pt-40 md:pt-56">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end md:gap-10">
            @if (!empty($tvShow['poster_path']))
                <img src="https://image.tmdb.org/t/p/w342{{ $tvShow['poster_path'] }}" alt="{{ $tvShow['name'] }} poster"
                     class="hidden w-40 shrink-0 rounded-2xl shadow-2xl shadow-black/60 ring-1 ring-white/10 sm:block md:w-56 lg:w-64">
            @endif

            <div class="min-w-0 max-w-3xl">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-brand-200 ring-1 ring-white/15 backdrop-blur">
                    <x-icon name="tv" class="size-3.5" /> TV Series
                </span>
                <h1 class="mt-4 text-balance text-3xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-4xl lg:text-6xl">{{ $tvShow['name'] }}</h1>
                @if (!empty($tvShow['tagline']))
                    <p class="mt-2 text-base italic text-gray-300 md:text-lg">“{{ $tvShow['tagline'] }}”</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-medium text-gray-300">
                    @if (!empty($tvShow['vote_average']))
                        <span class="flex items-center gap-1 font-bold text-white"><x-icon name="star-solid" class="size-4 text-amber-400" />{{ number_format($tvShow['vote_average'], 1) }}</span>
                    @endif
                    @if ($years) <span>{{ $years }}</span> @endif
                    @if ($seasonCount) <span class="text-gray-500">•</span><span>{{ $seasonCount }} {{ \Illuminate\Support\Str::plural('Season', $seasonCount) }}</span> @endif
                </div>

                @if (!empty($selectedTVDetails['genres']))
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($selectedTVDetails['genres'] as $genre)
                            <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-gray-200 ring-1 ring-white/10">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:gap-3">
                    @if (!empty($seasonNumbers))
                        <button type="button" @click="play({{ $seasonNumbers[0] }}, 1)" class="btn-brand col-span-2 sm:col-span-1">
                            <x-icon name="play-solid" class="size-5" /> Play S{{ $seasonNumbers[0] }} E1
                        </button>
                    @endif
                    @if ($trailer)
                        <button type="button" @click="trailerOpen = true" class="btn-glass">
                            <x-icon name="trailer" class="size-5" /> Trailer
                        </button>
                    @endif
                    <a href="#episodes" @click="tab = 'episodes'" class="btn-glass">
                        <x-icon name="tv" class="size-5" /> Episodes
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<x-video-modal show="playing" src="playing" close="playing = null" label="Episode player" />
@if ($trailer)
    <x-video-modal show="trailerOpen" src="'https://www.youtube.com/embed/{{ $trailer['key'] }}?autoplay=1'" close="trailerOpen = false" label="Trailer" />
@endif

{{-- ===================================================================== ABOUT --}}
<div class="shell mt-12 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-14">
    <div class="min-w-0 space-y-12">
        <section>
            <h2 class="section-title">Storyline</h2>
            <p class="mt-4 max-w-3xl text-base leading-relaxed text-gray-300">{{ $tvShow['overview'] ?: 'No overview available.' }}</p>
        </section>

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

{{-- ===================================================================== TABS --}}
<div id="episodes" class="shell mt-16 scroll-mt-24">
    <div class="no-scrollbar mb-8 flex gap-1 overflow-x-auto border-b border-white/10" role="tablist">
        @foreach (['episodes' => 'Episodes', 'more' => 'More like this', 'images' => 'Images'] as $key => $label)
            <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                    class="-mb-px shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition"
                    :class="tab === '{{ $key }}' ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white'">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Episodes --}}
    <section x-show="tab === 'episodes'" aria-label="Episodes">
        @if (count($seasonNumbers) > 0)
            <div class="no-scrollbar -mx-4 mb-6 flex gap-2 overflow-x-auto px-4 sm:-mx-6 sm:px-6 lg:mx-0 lg:flex-wrap lg:px-0" role="radiogroup" aria-label="Season">
                @foreach ($seasonNumbers as $number)
                    <button type="button" role="radio" @click="season = {{ $number }}" :aria-checked="season === {{ $number }}"
                            class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold ring-1 transition"
                            :class="season === {{ $number }} ? 'bg-brand-600 text-white ring-brand-500' : 'text-gray-300 ring-white/10 hover:bg-white/5'">
                        Season {{ $number }}
                    </button>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <template x-for="ep in episodes[season] || []" :key="ep.id">
                    <article class="group flex flex-col gap-4 rounded-2xl bg-ink-900/70 p-3 ring-1 ring-white/5 transition hover:bg-ink-900 hover:ring-white/15 sm:flex-row sm:p-4">
                        <button type="button" @click="play(season, ep.episode_number)"
                                class="relative aspect-video w-full shrink-0 overflow-hidden rounded-xl bg-ink-800 sm:w-52 xl:w-60"
                                :aria-label="`Play episode ${ep.episode_number}: ${ep.name}`">
                            <template x-if="ep.still_path">
                                <img :src="`https://image.tmdb.org/t/p/w300${ep.still_path}`" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
                            </template>
                            <template x-if="!ep.still_path">
                                <span class="grid size-full place-items-center text-gray-600"><x-icon name="tv" class="size-8" /></span>
                            </template>
                            <span class="absolute inset-0 grid place-items-center bg-black/25 transition sm:bg-black/0 sm:group-hover:bg-black/35">
                                <span class="grid size-12 place-items-center rounded-full bg-brand-600/90 text-white shadow-xl transition sm:scale-90 sm:opacity-0 sm:group-hover:scale-100 sm:group-hover:opacity-100">
                                    <x-icon name="play-solid" class="size-5 translate-x-px" />
                                </span>
                            </span>
                            <template x-if="ep.runtime">
                                <span class="absolute bottom-2 right-2 rounded-md bg-black/75 px-1.5 py-0.5 text-[11px] font-bold text-white" x-text="`${ep.runtime}m`"></span>
                            </template>
                        </button>

                        <div class="min-w-0 flex-1 sm:py-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-400" x-text="`Episode ${ep.episode_number}`"></p>
                            <h3 class="mt-1 line-clamp-2 text-base font-bold leading-snug text-white" x-text="ep.name"></h3>
                            <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                                <template x-if="ep.vote_average">
                                    <span class="flex items-center gap-1 font-semibold text-amber-400"><x-icon name="star-solid" class="size-3" /><span x-text="Number(ep.vote_average).toFixed(1)"></span></span>
                                </template>
                                <span x-text="airDate(ep.air_date)"></span>
                            </p>
                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-300" x-text="ep.overview || 'No description available.'"></p>
                        </div>
                    </article>
                </template>
            </div>
        @else
            <x-empty-state icon="tv" title="No episodes yet" message="Episode details for this show aren’t available." />
        @endif
    </section>

    {{-- More like this --}}
    <section x-show="tab === 'more'" x-cloak class="space-y-12">
        <x-poster-row title="More Like This" :items="$similarTV" type="tv" variant="backdrop" :genres="$genres" />
        <x-poster-row title="You Might Also Like" :items="$recommendedTV" type="tv" variant="backdrop" :genres="$genres" />
    </section>

    {{-- Images --}}
    <section x-show="tab === 'images'" x-cloak aria-label="Images">
        @if (count($backdrops))
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($backdrops as $image)
                    <button type="button" @click="lightbox = '{{ $image['file_path'] }}'" class="group overflow-hidden rounded-xl ring-1 ring-white/5">
                        <img src="https://image.tmdb.org/t/p/w780{{ $image['file_path'] }}" alt="Still from {{ $tvShow['name'] }}" loading="lazy"
                             class="aspect-video w-full object-cover transition duration-500 group-hover:scale-105">
                    </button>
                @endforeach
            </div>
        @else
            <p class="py-10 text-center text-sm text-gray-500">No images available.</p>
        @endif

        <template x-teleport="body">
            <div x-show="lightbox" x-cloak x-transition.opacity @click.self="lightbox = null" @keydown.escape.window="lightbox = null"
                 class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm">
                <button type="button" @click="lightbox = null" class="absolute right-4 top-4 grid size-10 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Close image">
                    <x-icon name="x" class="size-5" />
                </button>
                <img :src="lightbox ? `https://image.tmdb.org/t/p/w1280${lightbox}` : null" alt="" class="max-h-[85svh] w-auto max-w-full rounded-xl shadow-2xl">
            </div>
        </template>
    </section>
</div>
</div>
@endsection
