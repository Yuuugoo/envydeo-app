@extends('layouts.main')

@section('title', 'Pick tonight’s movie together')

@section('content')

{{-- ===================================================================== HERO: playable Movie Night demo --}}
<section class="relative isolate overflow-hidden">
    <div aria-hidden="true" class="absolute -left-40 -top-40 -z-10 size-[36rem] rounded-full bg-brand-600/25 blur-3xl"></div>
    <div aria-hidden="true" class="absolute -right-32 top-32 -z-10 size-[30rem] rounded-full bg-fuchsia-600/15 blur-3xl"></div>

    <div class="shell page-top grid items-center gap-12 pb-16 lg:grid-cols-[minmax(0,1fr)_24rem] lg:gap-16 lg:pb-24">
        <div class="max-w-2xl">
            <p class="inline-flex items-center gap-2 rounded-full bg-white/5 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-brand-200 ring-1 ring-white/10">
                <x-icon name="sparkles" class="size-4" /> Movie night, solved
            </p>
            <h1 class="mt-5 text-balance text-5xl font-black leading-[0.98] tracking-tight text-white sm:text-6xl lg:text-7xl">
                Stop scrolling.<br>
                <span class="bg-gradient-to-r from-brand-300 via-emerald-200 to-sky-300 bg-clip-text text-transparent">Pick tonight’s movie together.</span>
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-gray-300">
                Everyone swipes the same deck on their own phone. The first movie you <em class="not-italic font-semibold text-white">all</em> like wins.
                Then share what you’re watching with your friends, not the whole internet.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('movie-night.index') }}" class="btn-brand !px-6 !py-3 text-base">
                    <x-icon name="users" class="size-5" /> Start a room
                </a>
                <form method="POST" action="{{ route('movie-night.quick') }}">
                    @csrf
                    <button type="submit" class="btn-glass !px-6 !py-3 text-base"><x-icon name="sparkles" class="size-5" /> Decide for me</button>
                </form>
            </div>
            <p class="mt-4 text-sm text-gray-500">Free. No app store. Friends join with a code, no account needed.</p>
        </div>

        {{-- Demo deck --}}
        @if ($deck)
            <div x-data="swipeDeck({ cards: @js($deck), genres: @js($genres), demo: true })"
                 @keydown.arrow-left.window="decide(false)" @keydown.arrow-right.window="decide(true)"
                 class="relative mx-auto w-full max-w-[19rem] lg:max-w-none">
                <p class="mb-3 text-center text-sm font-semibold text-gray-400" x-show="!done && likes.length < 3">
                    Try it: swipe right on anything you’d watch tonight
                </p>

                <div class="relative aspect-[2/3] w-full">
                    <template x-if="upcoming && likes.length < 3">
                        <div class="absolute inset-0 overflow-hidden rounded-3xl bg-ink-800 ring-1 ring-white/10" :style="`transform: scale(${nextScale}) translateY(10px)`">
                            <img :src="poster(upcoming, 'w342')" alt="" class="size-full object-cover opacity-60">
                        </div>
                    </template>

                    <template x-if="current && likes.length < 3">
                        <div class="absolute inset-0 cursor-grab touch-none select-none overflow-hidden rounded-3xl bg-ink-800 shadow-2xl shadow-black/60 ring-1 ring-white/10 active:cursor-grabbing"
                             :style="cardStyle"
                             @pointerdown="pointerDown($event)" @pointermove="pointerMove($event)" @pointerup="pointerUp()" @pointercancel="pointerUp()">
                            <img :src="poster(current, 'w500')" :alt="current.title" draggable="false" class="pointer-events-none size-full object-cover">
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-5 pt-20">
                                <p class="text-xl font-extrabold leading-tight text-white" x-text="current.title"></p>
                                <p class="mt-1 text-sm text-gray-300"><span x-text="current.year"></span> <span class="text-gray-400" x-text="genreNames(current)"></span></p>
                            </div>
                            <span class="pointer-events-none absolute left-5 top-6 -rotate-12 rounded-xl border-4 border-brand-400 px-3 py-1 text-2xl font-black uppercase text-brand-300" :style="`opacity: ${likeOpacity}`">Yes!</span>
                            <span class="pointer-events-none absolute right-5 top-6 rotate-12 rounded-xl border-4 border-red-400 px-3 py-1 text-2xl font-black uppercase text-red-300" :style="`opacity: ${nopeOpacity}`">Nope</span>
                        </div>
                    </template>

                    {{-- Result: what a room would do with these likes --}}
                    <template x-if="done || likes.length >= 3">
                        <div class="absolute inset-0 flex flex-col overflow-hidden rounded-3xl bg-ink-900 p-6 text-center ring-1 ring-brand-400/30">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-300" x-text="likes.length ? 'Your shortlist' : 'Tough crowd'"></p>
                            <div class="mt-4 flex flex-1 items-center justify-center -space-x-6">
                                <template x-for="(card, i) in likes.slice(0, 3)" :key="card.id">
                                    <img :src="poster(card, 'w185')" :alt="card.title" class="w-24 rounded-xl shadow-xl ring-2 ring-ink-900"
                                         :class="['-rotate-6', 'z-10 -translate-y-2', 'rotate-6'][i]">
                                </template>
                            </div>
                            <p class="mt-4 text-lg font-extrabold text-white"
                               x-text="likes.length ? 'Now imagine your friends swiping too.' : 'Nothing grabbed you? Try a mood below.'"></p>
                            <p class="mt-1 text-sm text-gray-400">In a room, the first movie <strong class="text-white">everyone</strong> likes is the match.</p>
                            <a href="{{ route('movie-night.index') }}" class="btn-brand mt-5">Swipe with friends</a>
                        </div>
                    </template>
                </div>

                <div class="mt-5 flex items-center justify-center gap-6" x-show="!done && likes.length < 3">
                    <button type="button" @click="decide(false)" :disabled="leaving" aria-label="Nope"
                            class="grid size-14 place-items-center rounded-full bg-ink-900 text-red-400 shadow-xl ring-1 ring-white/10 transition hover:scale-110 hover:bg-red-500 hover:text-white">
                        <x-icon name="x" class="size-7" />
                    </button>
                    <span class="w-16 text-center text-xs font-semibold text-gray-500"><span x-text="likes.length"></span>/3 likes</span>
                    <button type="button" @click="decide(true)" :disabled="leaving" aria-label="Yes, I’d watch it"
                            class="grid size-14 place-items-center rounded-full bg-ink-900 text-brand-400 shadow-xl ring-1 ring-white/10 transition hover:scale-110 hover:bg-brand-600 hover:text-white">
                        <x-icon name="heart-solid" class="size-7" />
                    </button>
                </div>
            </div>
        @endif
    </div>
</section>

{{-- ===================================================================== HOW IT WORKS --}}
<section class="shell" aria-labelledby="how-heading">
    <h2 id="how-heading" class="text-3xl font-extrabold tracking-tight text-white md:text-4xl">How a movie night works</h2>
    <ol class="mt-8 grid gap-4 md:grid-cols-3">
        @foreach ([
            ['1', 'Start a room', 'Pick a mood, your streaming services and a max runtime. Share the code or QR in the group chat.', 'share'],
            ['2', 'Everyone swipes', 'Each person swipes the same deck on their own phone. No more “I don’t mind, you pick.”', 'heart-solid'],
            ['3', 'It’s a match', 'The first movie everyone likes wins, with where to stream it. Out of cards? The host picks from your closest calls.', 'check'],
        ] as [$n, $title, $text, $icon])
            <li class="relative overflow-hidden rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5">
                <span aria-hidden="true" class="absolute -right-2 -top-6 text-[7rem] font-black leading-none text-white/[0.04]">{{ $n }}</span>
                <span class="grid size-11 place-items-center rounded-2xl bg-brand-600/15 text-brand-300 ring-1 ring-brand-400/20"><x-icon :name="$icon" class="size-5" /></span>
                <h3 class="mt-4 text-lg font-bold text-white">{{ $title }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-gray-400">{{ $text }}</p>
            </li>
        @endforeach
    </ol>
</section>

{{-- ===================================================================== MOODS --}}
<section class="shell mt-20" aria-labelledby="moods-heading">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 id="moods-heading" class="text-3xl font-extrabold tracking-tight text-white md:text-4xl">What’s the mood?</h2>
            <p class="mt-2 text-gray-400">Tap one and swipe solo. Your first ♥ is tonight’s pick.</p>
        </div>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($themes as $key => $theme)
            <form method="POST" action="{{ route('movie-night.quick') }}">
                @csrf
                <input type="hidden" name="theme" value="{{ $key }}">
                <button type="submit" class="group relative h-full w-full overflow-hidden rounded-2xl bg-gradient-to-br {{ $theme['accent'] }} to-ink-900 p-4 text-left ring-1 ring-white/10 transition hover:-translate-y-0.5 hover:ring-white/25">
                    <x-icon :name="$theme['icon']" class="size-5 text-white/80" />
                    <span class="mt-3 block font-bold text-white">{{ $theme['name'] }}</span>
                    <span class="mt-0.5 block text-xs leading-snug text-gray-300">{{ $theme['tagline'] }}</span>
                </button>
            </form>
        @endforeach
    </div>
</section>

{{-- ===================================================================== BEYOND PICKING --}}
<section class="shell mt-24" aria-labelledby="social-heading">
    <h2 id="social-heading" class="max-w-2xl text-3xl font-extrabold tracking-tight text-white md:text-4xl">Movie night doesn’t end at the match</h2>
    <div class="mt-8 grid gap-4 lg:grid-cols-3">
        {{-- Watch Along (featured) --}}
        <article class="relative isolate grid items-center gap-6 overflow-hidden rounded-3xl bg-gradient-to-br from-sky-700/30 via-ink-900 to-fuchsia-900/20 p-6 ring-1 ring-sky-400/20 sm:p-8 lg:col-span-3 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-300">New · Watch Along</p>
                <h3 class="mt-2 text-2xl font-extrabold text-white md:text-3xl">Not on the same couch? Press play together.</h3>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-gray-300 md:text-base">
                    Everyone opens the movie on their own streaming app. Envydeo counts you down (3, 2, 1, play), keeps one shared clock,
                    and pins every 😱 and 😂 to the second it happened. When the credits roll, you get your group’s moment map.
                </p>
            </div>
            <div aria-hidden="true" class="relative rounded-[1.5rem] bg-ink-950/70 p-5 text-center ring-1 ring-white/10">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">You should be at</p>
                <p class="mt-1 font-mono text-5xl font-black tabular-nums text-white">1:04:12</p>
                <div class="mx-auto mt-3 h-1.5 max-w-[14rem] overflow-hidden rounded-full bg-white/10"><div class="h-full w-[46%] rounded-full bg-gradient-to-r from-brand-400 to-sky-400"></div></div>
                <div class="mt-4 flex justify-center gap-2 text-2xl">😱 😂 😭 🔥 🤯 ❤️</div>
                <span class="absolute right-6 top-4 rotate-6 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-semibold text-white">Kim 😱</span>
                <span class="absolute left-6 top-16 -rotate-6 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-semibold text-white">Ben: no way</span>
            </div>
        </article>

        {{-- Tonight --}}
        <article class="flex flex-col rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5">
            <div aria-hidden="true" class="flex items-center gap-3">
                @foreach (['AR' => 'from-brand-400 to-sky-400', 'BC' => 'from-fuchsia-400 to-amber-300', 'KL' => 'from-sky-400 to-brand-300'] as $initials => $ring)
                    <span class="rounded-full bg-gradient-to-tr {{ $ring }} p-[3px]"><span class="grid size-12 place-items-center rounded-full bg-ink-800 text-xs font-bold text-white ring-2 ring-ink-950">{{ $initials }}</span></span>
                @endforeach
                <span class="grid size-12 place-items-center rounded-full border-2 border-dashed border-white/20 text-brand-300">+</span>
            </div>
            <h3 class="mt-6 text-xl font-bold text-white">Tonight</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-gray-400">Snap what you’re watching (the couch, the snacks, the screen). Only friends see it, and it’s gone in 24 hours. Friends react, or tap <strong class="text-white">🙋 I’m in</strong>.</p>
        </article>

        {{-- Verdicts --}}
        <article class="flex flex-col rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5">
            <div aria-hidden="true" class="flex flex-wrap gap-2">
                <x-verdict value="loved" /> <x-verdict value="good" /> <x-verdict value="meh" /> <x-verdict value="skip" />
                <x-verdict value="" friends />
            </div>
            <h3 class="mt-6 text-xl font-bold text-white">Verdicts, not essays</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-gray-400">One tap to say how it was, plus “best with friends” for group picks. Your friends’ verdicts show up on every movie, right next to where to stream it.</p>
        </article>

        {{-- Us --}}
        <article class="flex flex-col rounded-3xl bg-gradient-to-br from-rose-500/15 via-ink-900 to-ink-900 p-6 ring-1 ring-rose-400/15">
            <div aria-hidden="true" class="flex items-center gap-2 text-3xl">💑 <span class="text-base font-bold text-rose-200">+ “Our matches”</span></div>
            <h3 class="mt-6 text-xl font-bold text-white">An “Us” room for two</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-gray-400">For couples and roommates: swipe whenever you’re bored, and every movie you both like lands in a shared list for later.</p>
        </article>
    </div>
</section>

{{-- ===================================================================== WHAT PEOPLE MATCH ON --}}
<section class="shell mt-24" aria-labelledby="matched-heading">
    @if (count($mostMatched) >= 4)
        <h2 id="matched-heading" class="section-title">Most matched this week</h2>
        <ul class="no-scrollbar -mx-4 mt-5 flex gap-3 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-6 sm:px-0">
            @foreach (array_slice($mostMatched, 0, 6) as $i => $movie)
                <li class="w-32 shrink-0 sm:w-auto">
                    <a href="{{ route('movie.show', $movie['id']) }}" class="group relative block overflow-hidden rounded-2xl ring-1 ring-white/10">
                        <img src="https://image.tmdb.org/t/p/w342{{ $movie['poster_path'] }}" alt="{{ $movie['title'] }}" loading="lazy" class="aspect-[2/3] w-full object-cover transition duration-500 group-hover:scale-105">
                        <span class="absolute left-2 top-2 rounded-full bg-ink-950/85 px-2 py-0.5 text-xs font-black text-white ring-1 ring-white/15">#{{ $i + 1 }}</span>
                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent px-2 pb-2 pt-8 text-[11px] font-semibold text-brand-200">❤ {{ $movie['matches'] }} {{ \Illuminate\Support\Str::plural('match', $movie['matches']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <x-poster-row title="Popular to swipe on this week" url="/api/movies/popular" :genres="$genres" :href="route('movie.list')" />
    @endif
    <p class="mt-6 text-sm text-gray-500">
        Looking for something specific?
        <a href="{{ route('movie.list') }}" class="font-semibold text-gray-300 hover:text-white">Browse movies</a> ·
        <a href="{{ route('show.list') }}" class="font-semibold text-gray-300 hover:text-white">TV shows</a> ·
        <a href="{{ route('search.results') }}" class="font-semibold text-gray-300 hover:text-white">Search</a>
    </p>
</section>

{{-- ===================================================================== FINAL CTA --}}
<section class="shell mt-24">
    <div class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand-700/50 via-ink-900 to-fuchsia-900/30 px-6 py-14 text-center ring-1 ring-white/10 sm:px-12">
        <div aria-hidden="true" class="absolute -right-16 -top-16 -z-10 size-72 rounded-full bg-brand-500/25 blur-3xl"></div>
        <h2 class="text-balance text-3xl font-black tracking-tight text-white md:text-5xl">Your group chat will thank you.</h2>
        <p class="mx-auto mt-3 max-w-lg text-gray-300">Start a room in ten seconds. Make an account later to keep your friends, verdicts and matches.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('movie-night.index') }}" class="btn-brand !px-6 !py-3 text-base"><x-icon name="users" class="size-5" /> Start a room</a>
            @guest
                <a href="{{ route('register') }}" class="btn-glass !px-6 !py-3 text-base">Create an account</a>
            @else
                <a href="{{ route('welcome') }}" class="btn-glass !px-6 !py-3 text-base">Set up your profile</a>
            @endguest
        </div>
    </div>
</section>
@endsection
