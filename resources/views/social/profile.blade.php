@extends('layouts.main')

@section('title', $owner->name . ' (@' . $owner->username . ')')

@push('meta')
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $owner->name }} ({{ '@' . $owner->username }}) on Envydeo">
    <meta property="og:description" content="{{ $owner->bio ?: 'Films, reviews and movie nights on Envydeo.' }}">
@endpush

@php
    $topFilms = collect($owner->top_films ?? [])->take(4);
    $tabs = array_filter(['diary' => 'Diary', 'watchlist' => 'Watchlist', 'tonight' => $isFriend ? 'Check-ins' : null]);
@endphp

@section('content')
<div class="relative isolate overflow-hidden">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[30rem]">
        @if ($first = $topFilms->first(fn ($f) => !empty($f['backdrop_path'])))
            <img src="https://image.tmdb.org/t/p/w1280{{ $first['backdrop_path'] }}" alt="" class="size-full object-cover opacity-25">
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-ink-950/40 via-ink-950/80 to-ink-950"></div>
    </div>

    <div class="shell page-top pb-8">
        @if (session('status'))
            <p class="mb-6 rounded-2xl bg-brand-600/15 px-4 py-3 text-sm text-brand-200 ring-1 ring-brand-500/30">{{ session('status') }}</p>
        @endif

        {{-- Header --}}
        <header class="flex flex-col gap-6 sm:flex-row sm:items-end">
            <x-avatar :user="$owner" size="xl" ring />
            <div class="min-w-0 flex-1">
                <h1 class="flex flex-wrap items-center gap-2 text-3xl font-extrabold tracking-tight text-white md:text-4xl">
                    {{ $owner->name }}
                    @if ($owner->is_private)
                        <span class="rounded-full bg-white/10 px-2 py-0.5 text-xs font-semibold text-gray-300" title="Private account">🔒 Private</span>
                    @endif
                </h1>
                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-400">
                    <span>{{ '@' . $owner->username }}</span>
                    @if ($relation['friends'])
                        <span class="rounded-full bg-brand-600/20 px-2 py-0.5 text-[11px] font-bold text-brand-200">Friends</span>
                    @elseif ($relation['follows_you'])
                        <span class="rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-semibold text-gray-300">Follows you</span>
                    @endif
                </p>
                @if ($owner->bio)
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-300">{{ $owner->bio }}</p>
                @endif

                <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    @foreach ([['films', 'Films', null], ['reviews', 'Reviews', null], ['followers', 'Followers', 'followers'], ['following', 'Following', 'following']] as [$key, $label, $list])
                        <div>
                            @if ($list && $canView)
                                <a href="{{ route('profile.connections', [$owner->username, $list]) }}" class="group">
                                    <dd class="inline font-bold text-white group-hover:text-brand-300">{{ number_format($stats[$key]) }}</dd>
                                    <dt class="inline text-gray-400 group-hover:text-gray-300">{{ $label }}</dt>
                                </a>
                            @else
                                <dd class="inline font-bold text-white">{{ number_format($stats[$key]) }}</dd>
                                <dt class="inline text-gray-400">{{ $label }}</dt>
                            @endif
                        </div>
                    @endforeach
                </dl>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @if ($relation['self'])
                    <button type="button" x-data @click="$dispatch('open-check-in')" class="btn-brand !px-5 !py-2 text-sm">
                        <x-icon name="sparkles" class="size-4" /> Check in
                    </button>
                @endif
                <livewire:social.follow-button :user="$owner" :key="'follow-' . $owner->id" />
            </div>
        </header>

        @if (!$canView)
            <x-empty-state icon="user" title="This account is private" class="mt-12"
                           message="Follow {{ '@' . $owner->username }} to see their films, reviews and watchlist." />
        @else
            {{-- Top 4 --}}
            @if ($topFilms->isNotEmpty())
                <section class="mt-12" aria-labelledby="top4">
                    <h2 id="top4" class="section-title">Top 4</h2>
                    <ul class="mt-4 grid max-w-2xl grid-cols-4 gap-3 sm:gap-4">
                        @foreach ($topFilms as $film)
                            <li>
                                <a href="{{ \App\Support\TitleSnapshot::url($film) }}" class="group block overflow-hidden rounded-xl ring-1 ring-white/10 transition hover:ring-brand-400/60" title="{{ $film['title'] }}">
                                    <img src="https://image.tmdb.org/t/p/w342{{ $film['poster_path'] }}" alt="{{ $film['title'] }}" class="aspect-[2/3] w-full object-cover transition duration-500 group-hover:scale-105">
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Tabs --}}
            <div x-data="{ tab: 'diary' }" class="mt-12">
                <nav class="flex gap-1 border-b border-white/10" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                                class="-mb-px border-b-2 px-4 py-3 text-sm font-semibold transition"
                                :class="tab === '{{ $key }}' ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white'">{{ $label }}</button>
                    @endforeach
                </nav>

                {{-- Diary --}}
                <section x-show="tab === 'diary'" class="mt-6">
                    @forelse ($diary as $entry)
                        <article class="flex gap-4 border-b border-white/5 py-4">
                            <a href="{{ $entry->titleUrl() }}" class="shrink-0">
                                <img src="https://image.tmdb.org/t/p/w154{{ $entry->movie['poster_path'] ?? '' }}" alt="" class="w-16 rounded-lg ring-1 ring-white/10 sm:w-20">
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ $entry->titleUrl() }}" class="font-bold text-white hover:text-brand-300">{{ $entry->movie['title'] ?? '' }}</a>
                                <span class="text-sm text-gray-500">{{ $entry->movie['year'] ?? '' }}</span>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-gray-400">
                                    @if ($entry->rating) <x-stars :value="$entry->rating" size="size-3.5" /> @endif
                                    <span>Watched {{ $entry->watched_on->format('M j, Y') }}</span>
                                    @if ($entry->likes_count) <span>♥ {{ $entry->likes_count }}</span> @endif
                                </div>
                                @if ($entry->review)
                                    <div x-data="{ reveal: {{ $entry->has_spoilers ? 'false' : 'true' }} }" class="mt-2">
                                        <p class="line-clamp-4 text-sm leading-relaxed text-gray-300 transition" :class="!reveal && 'select-none blur-sm'">{{ $entry->review }}</p>
                                        @if ($entry->has_spoilers)
                                            <button type="button" x-show="!reveal" @click="reveal = true" class="mt-1 text-xs font-semibold text-amber-300">Contains spoilers · show</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <x-empty-state icon="film" title="No films logged yet" message="{{ $relation['self'] ? 'Rate a movie from its page and it shows up here.' : 'Nothing here yet.' }}" />
                    @endforelse
                </section>

                {{-- Watchlist --}}
                <section x-show="tab === 'watchlist'" x-cloak class="mt-6">
                    @if ($watchlist->isNotEmpty())
                        <div class="grid grid-cols-3 gap-x-3 gap-y-6 sm:grid-cols-4 md:grid-cols-6">
                            @foreach ($watchlist as $movie)
                                <x-media-card :item="$movie" type="movie" />
                            @endforeach
                        </div>
                    @else
                        <x-empty-state icon="bookmark" title="Watchlist is empty" />
                    @endif
                </section>

                {{-- Check-ins (friends only) --}}
                @if ($isFriend)
                    <section x-show="tab === 'tonight'" x-cloak class="mt-6">
                        @if ($checkIns->isNotEmpty())
                            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($checkIns as $checkIn)
                                    <li class="group relative overflow-hidden rounded-2xl bg-ink-850 ring-1 ring-white/5">
                                        <img src="{{ $checkIn->photoUrl() ?? 'https://image.tmdb.org/t/p/w342' . ($checkIn->movie['poster_path'] ?? '') }}" alt="" loading="lazy" class="aspect-[3/4] w-full object-cover">
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent p-3 pt-10">
                                            <a href="{{ $checkIn->titleUrl() }}" class="line-clamp-1 text-sm font-bold text-white">{{ $checkIn->movie['title'] ?? '' }}</a>
                                            <p class="text-[11px] text-gray-300">{{ $checkIn->created_at->diffForHumans() }}</p>
                                        </div>
                                        @if ($checkIn->isLive())
                                            <span class="absolute left-2 top-2 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Tonight</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <x-empty-state icon="sparkles" title="No check-ins yet" />
                        @endif
                    </section>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
