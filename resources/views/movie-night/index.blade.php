@extends('layouts.main')

@section('title', 'Movie Night')

@push('meta')
    <meta property="og:type" content="website">
    <meta property="og:title" content="Movie Night on Envydeo: stop scrolling, start watching">
    <meta property="og:description" content="Swipe on movies with friends. The first one you all like wins.">
@endpush

@section('content')
<div class="relative isolate overflow-hidden">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[40rem]">
        <div class="absolute left-1/4 top-10 size-[30rem] rounded-full bg-brand-600/20 blur-3xl"></div>
        <div class="absolute right-0 top-40 size-[24rem] rounded-full bg-fuchsia-500/10 blur-3xl"></div>
    </div>

    <div class="shell page-top">
        @if (session('movie_night_error'))
            <p class="mb-8 rounded-2xl bg-red-500/10 px-4 py-3 text-sm text-red-300 ring-1 ring-red-500/30">{{ session('movie_night_error') }}</p>
        @endif

        <div class="grid gap-12 lg:grid-cols-[1fr_minmax(0,32rem)] lg:gap-16">
            {{-- Intro --}}
            <div class="lg:pt-6">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-600/15 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-brand-300 ring-1 ring-brand-400/30">
                    <x-icon name="sparkles" class="size-4" /> Movie Night Matcher
                </span>
                <h1 class="mt-5 text-balance text-4xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Stop scrolling.<br>
                    <span class="bg-gradient-to-r from-brand-300 to-sky-300 bg-clip-text text-transparent">Start watching.</span>
                </h1>
                <p class="mt-5 max-w-xl text-base leading-relaxed text-gray-300 md:text-lg">
                    Can’t agree on a movie? Everyone swipes on the same deck from their own phone,
                    and the first movie you <em>all</em> like wins.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('movie-night.quick') }}">
                        @csrf
                        <button type="submit" class="btn-brand !px-6 !py-3.5 text-base">
                            <x-icon name="sparkles" class="size-5" /> Decide for me
                        </button>
                    </form>
                    <span class="text-sm text-gray-400">Swiping alone? Your first ♥ is tonight’s pick.</span>
                </div>

                <ol class="mt-10 grid gap-4 sm:grid-cols-3 lg:max-w-2xl">
                    @foreach ([
                        ['users', 'Create a room', 'Pick a theme or filters and the services you have.'],
                        ['share', 'Invite friends', 'Share the link, code or QR. No account needed.'],
                        ['heart', 'Swipe to match', 'Swipe right on what you’d watch. Unanimous wins.'],
                    ] as $i => [$icon, $title, $text])
                        <li class="rounded-2xl bg-ink-900/70 p-5 ring-1 ring-white/5">
                            <span class="grid size-10 place-items-center rounded-xl bg-brand-600/15 text-brand-300 ring-1 ring-brand-400/20">
                                <x-icon :name="$icon" class="size-5" />
                            </span>
                            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-gray-500">Step {{ $i + 1 }}</p>
                            <h2 class="mt-1 font-bold text-white">{{ $title }}</h2>
                            <p class="mt-1 text-sm text-gray-400">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                {{-- Join --}}
                <form action="{{ route('movie-night.join') }}" method="GET" class="mt-10 max-w-md">
                    <label for="join-code" class="text-sm font-semibold text-gray-200">Got a code from a friend?</label>
                    <div class="mt-2 flex gap-2">
                        <input id="join-code" name="code" value="{{ old('code') }}" maxlength="6" autocomplete="off" required
                               class="min-w-0 flex-1 rounded-xl bg-ink-900 px-4 py-3 font-mono text-lg font-bold uppercase tracking-[0.3em] text-white ring-1 ring-white/10 placeholder:font-sans placeholder:text-sm placeholder:font-normal placeholder:tracking-normal placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"
                               placeholder="Enter 6-character code">
                        <button type="submit" class="btn-glass shrink-0">Join</button>
                    </div>
                    @if (session('join_error'))
                        <p class="mt-2 text-sm text-red-400">{{ session('join_error') }}</p>
                    @endif
                </form>
            </div>

            {{-- Create --}}
            <div id="create" class="scroll-mt-24 rounded-3xl bg-ink-900/80 p-6 shadow-2xl shadow-black/40 ring-1 ring-white/10 backdrop-blur-xl sm:p-8">
                <h2 class="text-xl font-extrabold tracking-tight text-white">Start a movie night with friends</h2>
                <p class="mt-1 text-sm text-gray-400">Everything except your name is optional.</p>
                <livewire:movie-night.create-room :preset-theme="request('theme')" :preset-source="request('source')" />
            </div>
        </div>

        {{-- Themed decks --}}
        <section class="mt-20" aria-labelledby="themes-heading">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="themes-heading" class="section-title">Themed decks</h2>
                    <p class="mt-2 text-sm text-gray-400">One tap and you’re swiping. Go solo or bring friends.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($themes as $theme)
                    <article class="group relative isolate flex flex-col overflow-hidden rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5 transition hover:ring-white/15">
                        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-br {{ $theme['accent'] }} to-transparent opacity-70 transition group-hover:opacity-100"></div>
                        <span class="grid size-11 place-items-center rounded-2xl bg-black/30 text-white ring-1 ring-white/10">
                            <x-icon :name="$theme['icon']" class="size-5" />
                        </span>
                        <h3 class="mt-4 text-lg font-extrabold text-white">{{ $theme['name'] }}</h3>
                        <p class="mt-1 flex-1 text-sm text-gray-300">{{ $theme['tagline'] }}</p>
                        <div class="mt-5 flex gap-2">
                            <form method="POST" action="{{ route('movie-night.quick') }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="theme" value="{{ $theme['key'] }}">
                                <button type="submit" class="btn-brand w-full !px-3 !py-2 text-sm">Swipe solo</button>
                            </form>
                            <a href="{{ route('movie-night.index', ['theme' => $theme['key']]) }}#create" class="btn-glass flex-1 !px-3 !py-2 text-sm">With friends</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Us + Watchlist showdown --}}
        <section class="mt-16 grid gap-4 md:grid-cols-2">
            <article class="relative isolate overflow-hidden rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8">
                <div aria-hidden="true" class="absolute -right-10 -top-10 -z-10 size-56 rounded-full bg-rose-500/15 blur-3xl"></div>
                <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.18em] text-rose-300"><x-icon name="heart-solid" class="size-4" /> For couples</p>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white">“Us” rooms</h2>
                <p class="mt-2 text-sm leading-relaxed text-gray-300">
                    A permanent room for two. Swipe whenever you like, on any device. Every movie you both love lands in
                    <strong class="text-white">“Our matches”</strong>, so tonight you just pick from the list.
                </p>
                <div class="mt-6">
                    @auth
                        <a href="{{ route('movie-night.us') }}" class="btn-brand">Open my “Us” rooms</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-brand">Sign in to start</a>
                    @endauth
                </div>
            </article>

            <article class="relative isolate overflow-hidden rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8">
                <div aria-hidden="true" class="absolute -right-10 -top-10 -z-10 size-56 rounded-full bg-sky-500/15 blur-3xl"></div>
                <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.18em] text-sky-300"><x-icon name="bookmark-solid" class="size-4" /> Finally watch your list</p>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Watchlist Showdown</h2>
                <p class="mt-2 text-sm leading-relaxed text-gray-300">
                    A deck made only from the watchlists of everyone in the room. Movies more of you saved show up first.
                </p>
                <div class="mt-6">
                    @auth
                        <form method="POST" action="{{ route('movie-night.watchlist') }}">
                            @csrf
                            <button type="submit" class="btn-brand">Start a showdown</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-brand">Sign in to start</a>
                    @endauth
                </div>
            </article>
        </section>
    </div>
</div>
@endsection
