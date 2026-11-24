@php
    $host = $participants->firstWhere('is_host', true);
    $isHost = (bool) $me?->is_host;
    // Solo = swiping alone (Decide for me, or a host who started without friends) — not a lobby waiting for people.
    $isSolo = $participants->count() === 1 && !$room->is_persistent && $status !== 'lobby';
    $filters = $room->filters;
    $filterChips = array_values(array_filter([
        $room->is_persistent ? '“Us” room' : null,
        $room->source === 'watchlist' ? 'Watchlist Showdown' : null,
        $theme['name'] ?? null,
        ...collect($filters['genres'] ?? [])->map(fn ($id) => $genres[$id] ?? null)->all(),
        !empty($filters['max_runtime']) ? (\App\Services\MovieNightDealer::RUNTIMES[$filters['max_runtime']] ?? null) : null,
        !empty($filters['min_rating']) ? '★ ' . (int) $filters['min_rating'] . '+' : null,
        !empty($filters['providers']) ? count($filters['providers']) . ' ' . \Illuminate\Support\Str::plural('service', count($filters['providers'])) . ' · ' . ($filters['region'] ?? '') : null,
    ]));
    $poll = $me && ($room->is_persistent || in_array($status, ['lobby', 'voting', 'exhausted']));
@endphp

<div class="mx-auto max-w-6xl" @if ($poll) wire:poll.{{ $room->is_persistent ? '4s' : '2s' }}.visible @endif>

    {{-- ================================================================ HEADER --}}
    <header class="flex items-start justify-between gap-3 sm:items-end">
        <div class="min-w-0">
            <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.18em] text-brand-400">
                <x-icon :name="$room->is_persistent ? 'heart-solid' : 'sparkles'" class="size-4" />
                {{ $room->is_persistent ? 'Us' : ($isSolo ? 'Decide for me' : 'Movie night') }}
            </p>
            <h1 class="mt-1.5 text-2xl font-extrabold tracking-tight text-white sm:mt-2 sm:text-3xl md:text-4xl">
                @if ($room->is_persistent)
                    {{ $participants->pluck('name')->implode(' & ') }}
                @else
                    Room <span class="font-mono tracking-[0.15em] text-brand-300">{{ $room->code }}</span>
                @endif
            </h1>
            @if ($filterChips)
                <div class="mt-3 flex-wrap gap-1.5 {{ $status === 'voting' ? 'hidden sm:flex' : 'flex' }}">
                    @foreach ($filterChips as $chipLabel)
                        <span class="rounded-full bg-white/5 px-2.5 py-1 text-xs font-semibold text-gray-300 ring-1 ring-white/10">{{ $chipLabel }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($me && (!$room->is_persistent || $participants->count() < \App\Models\MatchRoom::MAX_US_PARTICIPANTS))
            <div x-data="{
                    copied: false,
                    url: @js($shareUrl),
                    async copy() {
                        try { await navigator.clipboard.writeText(this.url); this.copied = true; setTimeout(() => this.copied = false, 2000); }
                        catch (e) { window.prompt('Copy this link', this.url); }
                        $wire.trackShare('invite', 'copy');
                    },
                    async share() {
                        if (!navigator.share) return this.copy();
                        try {
                            await navigator.share({ title: 'Movie night on Envydeo', text: @js($room->is_persistent ? 'Find movies we both love 💚' : 'Join my movie night! 🍿 Room ' . $room->code), url: this.url });
                            $wire.trackShare('invite', 'native');
                        } catch (e) {}
                    }
                 }" class="flex shrink-0 gap-2 pt-5 sm:pt-0">
                <button type="button" @click="copy()" class="btn-glass !px-3 !py-2 text-sm sm:!px-4" aria-label="Copy invite link">
                    <span x-show="!copied" class="flex items-center gap-2"><x-icon name="copy" class="size-4" /><span class="hidden sm:inline">Copy link</span></span>
                    <span x-show="copied" x-cloak class="flex items-center gap-2 text-brand-300"><x-icon name="check" class="size-4" /><span class="hidden sm:inline">Copied!</span></span>
                </button>
                <button type="button" @click="share()" class="btn-brand !px-3 !py-2 text-sm sm:!px-4" aria-label="Invite">
                    <x-icon name="share" class="size-4" /><span class="hidden sm:inline">{{ $room->is_persistent ? 'Invite partner' : 'Invite' }}</span>
                </button>
            </div>
        @endif
    </header>

    {{-- ================================================================ MATCH (regular rooms) --}}
    @if ($status === 'matched' && $match)
        <section wire:key="state-matched" class="mt-8"
                 x-data x-init="$el.animate([{ opacity: 0, transform: 'scale(.97)' }, { opacity: 1, transform: 'scale(1)' }], { duration: 450, easing: 'cubic-bezier(.22,1,.36,1)' })">
            <div class="relative isolate overflow-hidden rounded-3xl ring-1 ring-white/10">
                @if ($match['backdrop_path'])
                    <img src="https://image.tmdb.org/t/p/w1280{{ $match['backdrop_path'] }}" alt="" class="absolute inset-0 -z-10 size-full object-cover">
                @endif
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink-950 via-ink-950/80 to-ink-950/30"></div>
                <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink-950/90 to-transparent"></div>

                <div class="grid items-end gap-6 p-6 sm:grid-cols-[11rem_1fr] sm:p-10 md:grid-cols-[14rem_1fr] md:gap-10">
                    <img src="https://image.tmdb.org/t/p/w342{{ $match['poster_path'] }}" alt="{{ $match['title'] }} poster"
                         class="w-36 rounded-2xl shadow-2xl shadow-black/60 ring-1 ring-white/15 sm:w-full">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full bg-brand-600/25 px-3.5 py-1.5 text-sm font-extrabold uppercase tracking-wider text-brand-200 ring-1 ring-brand-400/40">
                            <x-icon name="heart-solid" class="size-4 text-brand-300" /> {{ $isSolo ? 'Tonight’s pick' : 'It’s a match!' }}
                        </p>
                        <h2 class="mt-4 text-balance text-4xl font-extrabold leading-[1.05] tracking-tight text-white md:text-5xl">{{ $match['title'] }}</h2>
                        <p class="mt-3 flex flex-wrap items-center gap-x-3 text-sm font-medium text-gray-300">
                            @if ($match['rating']) <span class="flex items-center gap-1 font-bold text-white"><x-icon name="star-solid" class="size-4 text-amber-400" />{{ number_format($match['rating'], 1) }}</span> @endif
                            @if ($match['year']) <span>{{ $match['year'] }}</span> @endif
                        </p>
                        @if ($match['overview'])
                            <p class="mt-4 line-clamp-3 max-w-2xl text-sm leading-relaxed text-gray-300 md:text-base">{{ $match['overview'] }}</p>
                        @endif

                        @unless ($isSolo)
                            <div class="mt-5 flex items-center gap-2">
                                <div class="flex -space-x-2">
                                    @foreach ($participants->take(6) as $p)
                                        <span class="grid size-8 place-items-center rounded-full bg-brand-700 text-xs font-bold text-white ring-2 ring-ink-950" title="{{ $p->name }}">{{ $p->initials() }}</span>
                                    @endforeach
                                </div>
                                <span class="text-sm text-gray-400">Everyone said yes</span>
                            </div>
                        @endunless

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="#where-to-watch" class="btn-brand"><x-icon name="tv" class="size-5" /> Where to watch</a>
                            {{-- Not watching on the same couch? Press play together. --}}
                            <x-watch-along-button :tmdb-id="$match['id']" type="movie" />
                            <a href="{{ route('movie.show', $match['id']) }}" class="btn-glass"><x-icon name="info" class="size-5" /> Details</a>
                            @if ($isHost)
                                <button type="button" wire:click="newRound" wire:loading.attr="disabled" class="btn-glass">
                                    <x-icon name="retry" class="size-5" /> Another round
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
                @include('livewire.movie-night.partials.where-to-watch')
                @include('livewire.movie-night.partials.share-card', ['cardUrl' => route('movie-night.card', $room->code), 'title' => $match['title']])
            </div>
        </section>

    {{-- ================================================================ JOIN --}}
    @elseif (!$me)
        <section wire:key="state-join" class="mx-auto mt-10 max-w-md rounded-3xl bg-ink-900/80 p-6 text-center ring-1 ring-white/10 sm:p-8">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-600/15 text-brand-300 ring-1 ring-brand-400/20">
                <x-icon :name="$room->is_persistent ? 'heart-solid' : 'users'" class="size-7" />
            </span>
            <h2 class="mt-5 text-2xl font-extrabold tracking-tight text-white">
                {{ $host ? $host->name . ' invited you' : 'You’re invited' }}
            </h2>
            <p class="mt-2 text-sm text-gray-400">
                @if ($room->is_persistent)
                    Swipe whenever you like. Every movie you both love lands in your shared “Our matches” list.
                @else
                    {{ $participants->count() }} {{ \Illuminate\Support\Str::plural('person', $participants->count()) }} in this room.
                    Swipe on movies together. The first one everybody likes wins.
                @endif
            </p>

            @if ($participants->count() >= $room->maxParticipants())
                <p class="mt-6 rounded-xl bg-white/5 px-4 py-3 text-sm text-gray-300">This room is full.</p>
            @elseif ($room->is_persistent && !auth()->check())
                <div class="mt-6 grid gap-2">
                    <a href="{{ route('login') }}" class="btn-brand w-full !py-3">Sign in to join</a>
                    <a href="{{ route('register') }}" class="btn-glass w-full">Create a free account</a>
                    <p class="mt-1 text-xs text-gray-500">“Us” rooms need an account so they remember you on every device.</p>
                </div>
            @else
                <form wire:submit="join" class="mt-6 space-y-3 text-left">
                    <x-input-label for="join-name" value="Your name" />
                    <x-text-input id="join-name" wire:model="joinName" maxlength="30" placeholder="So friends know who’s who" autofocus />
                    <x-input-error :messages="$errors->get('joinName')" />
                    <button type="submit" class="btn-brand w-full !py-3" wire:loading.attr="disabled">
                        {{ $room->is_persistent ? 'Join' : 'Join movie night' }}
                    </button>
                </form>
            @endif
        </section>

    {{-- ================================================================ LOBBY --}}
    @elseif ($status === 'lobby')
        <section wire:key="state-lobby" class="mt-8 grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="flex flex-col items-center justify-center rounded-3xl bg-ink-900/70 px-6 py-10 text-center ring-1 ring-white/5">
                <div class="flex flex-col items-center gap-8 sm:flex-row sm:gap-12">
                    <div>
                        <p class="text-sm font-semibold text-gray-400">Share this code</p>
                        <p class="mt-3 font-mono text-5xl font-extrabold tracking-[0.25em] text-white sm:text-6xl">{{ $room->code }}</p>
                        <p class="mt-3 max-w-xs text-sm text-gray-400">Or send the invite link. Friends just tap it and enter a name.</p>
                    </div>
                    @if ($qrSvg)
                        <figure class="shrink-0">
                            <div class="rounded-2xl bg-white p-3 shadow-xl [&_svg]:size-40 sm:[&_svg]:size-44" aria-hidden="true">{!! $qrSvg !!}</div>
                            <figcaption class="mt-2 text-xs text-gray-400">Same room? Scan to join</figcaption>
                        </figure>
                    @endif
                </div>

                <div class="mt-10 w-full max-w-sm">
                    @if ($isHost)
                        <button type="button" wire:click="start" class="btn-brand w-full !py-3.5 text-base" wire:loading.attr="disabled">
                            <x-icon name="heart-solid" class="size-5" /> Start swiping
                        </button>
                        <p class="mt-3 text-xs text-gray-500">
                            @if ($room->source === 'watchlist')
                                The deck is built from everyone’s watchlists when you start.
                            @elseif ($participants->count() < 2)
                                Swiping solo? The first movie you like wins.
                            @else
                                People can still join after you start.
                            @endif
                        </p>
                    @else
                        <p class="flex items-center justify-center gap-2 text-sm text-gray-300">
                            <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-75"></span><span class="relative inline-flex size-2.5 rounded-full bg-brand-400"></span></span>
                            Waiting for {{ $host?->name ?? 'the host' }} to start…
                        </p>
                    @endif
                </div>
            </div>

            @include('livewire.movie-night.partials.participants')
        </section>

    {{-- ================================================================ NO MATCH --}}
    @elseif ($status === 'exhausted' && !$room->is_persistent)
        <section wire:key="state-exhausted" class="mt-8 grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8">
                <h2 class="text-2xl font-extrabold tracking-tight text-white">{{ $isSolo ? 'Nothing caught your eye?' : 'No unanimous match… yet' }}</h2>
                <p class="mt-2 text-sm text-gray-400">{{ $isSolo ? 'Deal more movies to keep going.' : 'Everyone’s been through the deck. Here’s what came closest.' }}</p>

                @if ($topPicks->isNotEmpty())
                    <ul class="mt-6 grid gap-4 sm:grid-cols-3">
                        @foreach ($topPicks as $pick)
                            <li class="flex flex-col overflow-hidden rounded-2xl bg-ink-850 ring-1 ring-white/5">
                                <img src="https://image.tmdb.org/t/p/w342{{ $pick['movie']['poster_path'] }}" alt="{{ $pick['movie']['title'] }}" class="aspect-[2/3] w-full object-cover">
                                <div class="flex flex-1 flex-col p-3">
                                    <p class="line-clamp-1 font-semibold text-white">{{ $pick['movie']['title'] }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $pick['likes'] }} of {{ $participants->count() }} liked it</p>
                                    @if ($isHost)
                                        <button type="button" wire:click="pick({{ $pick['movie']['id'] }})" class="btn-brand mt-3 !py-2 text-sm">Pick this</button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @elseif (!$isSolo)
                    <p class="mt-6 rounded-2xl border border-dashed border-white/10 p-6 text-center text-sm text-gray-400">Nobody liked anything. Tough crowd!</p>
                @endif

                @error('deck') <p class="mt-4 text-sm text-amber-300">{{ $message }}</p> @enderror

                <div class="mt-6">
                    @if ($isHost)
                        <button type="button" wire:click="dealMore" wire:loading.attr="disabled" class="btn-glass">
                            <x-icon name="retry" class="size-5" /> Deal more movies
                        </button>
                    @else
                        <p class="text-sm text-gray-400">Waiting for {{ $host?->name ?? 'the host' }} to pick or deal more…</p>
                    @endif
                </div>
            </div>

            @include('livewire.movie-night.partials.participants')
        </section>

    {{-- ================================================================ VOTING (and "Us" rooms) --}}
    @else
        @if ($room->is_persistent)
            <nav class="mt-6 flex gap-1 border-b border-white/10" role="tablist">
                <button type="button" role="tab" wire:click="$set('tab', 'swipe')" :aria-selected="@js($tab === 'swipe')"
                        class="-mb-px border-b-2 px-4 py-3 text-sm font-semibold transition {{ $tab === 'swipe' ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                    Swipe
                </button>
                <button type="button" role="tab" wire:click="$set('tab', 'matches')" :aria-selected="@js($tab === 'matches')"
                        class="-mb-px flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition {{ $tab === 'matches' ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                    Our matches
                    <span class="rounded-full bg-brand-600/25 px-2 py-0.5 text-xs text-brand-200">{{ $results->count() }}</span>
                </button>
            </nav>
        @endif

        @if ($room->is_persistent && $tab === 'matches')
            @include('livewire.movie-night.partials.our-matches')
        @else
            <section wire:key="state-voting" class="mt-5 grid items-start gap-8 sm:mt-8 lg:grid-cols-[1fr_20rem]">
                {{-- Card size follows the screen height so the card + buttons always fit without scrolling --}}
                <div wire:ignore wire:key="deck-{{ $room->updated_at?->timestamp }}-{{ $deckSize }}"
                     x-data="swipeDeck({ cards: @js($myDeck), genres: @js($genres), persistent: @js($room->is_persistent) })"
                     @keydown.arrow-left.window="decide(false)"
                     @keydown.arrow-right.window="decide(true)"
                     class="relative mx-auto w-full min-w-[14rem] max-w-[min(22rem,calc((100svh-19rem)*0.6667))]">

                    {{-- "Us" rooms: new match toast --}}
                    <div x-show="toast" x-cloak x-transition.opacity.duration.200ms
                         class="absolute inset-x-0 -top-3 z-30 mx-auto w-max max-w-full rounded-full bg-brand-600 px-4 py-2 text-sm font-bold text-white shadow-xl shadow-brand-900/50">
                        <span class="flex items-center gap-2"><x-icon name="heart-solid" class="size-4" /> It’s a match: <span x-text="toast"></span></span>
                    </div>

                    <div class="relative aspect-[2/3] w-full">
                        {{-- Next card peeking underneath --}}
                        <template x-if="upcoming">
                            <div class="absolute inset-0 overflow-hidden rounded-3xl bg-ink-800 ring-1 ring-white/10 transition-transform duration-200"
                                 :style="`transform: scale(${nextScale}) translateY(10px)`">
                                <img :src="poster(upcoming)" alt="" class="size-full object-cover opacity-60">
                            </div>
                        </template>

                        {{-- Current card --}}
                        <template x-if="current">
                            <div class="absolute inset-0 cursor-grab touch-none select-none overflow-hidden rounded-3xl bg-ink-800 shadow-2xl shadow-black/60 ring-1 ring-white/10 active:cursor-grabbing"
                                 :style="cardStyle"
                                 @pointerdown="pointerDown($event)" @pointermove="pointerMove($event)"
                                 @pointerup="pointerUp()" @pointercancel="pointerUp()">
                                <img :src="poster(current)" :alt="current.title" draggable="false" class="pointer-events-none size-full object-cover">

                                <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/80 to-transparent p-5 pt-24">
                                    <h2 class="text-2xl font-extrabold leading-tight text-white" x-text="current.title"></h2>
                                    <p class="mt-1.5 flex flex-wrap items-center gap-x-2.5 text-sm text-gray-300">
                                        <template x-if="current.rating">
                                            <span class="flex items-center gap-1 font-bold text-white"><x-icon name="star-solid" class="size-4 text-amber-400" /><span x-text="current.rating.toFixed(1)"></span></span>
                                        </template>
                                        <span x-text="current.year"></span>
                                        <span class="text-gray-400" x-text="genreNames(current)"></span>
                                    </p>
                                    <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-300" x-text="current.overview"></p>
                                </div>

                                {{-- Decision stamps --}}
                                <span class="pointer-events-none absolute left-5 top-6 -rotate-12 rounded-xl border-4 border-brand-400 px-3 py-1 text-3xl font-black uppercase tracking-wider text-brand-300"
                                      :style="`opacity: ${likeOpacity}`">Yes!</span>
                                <span class="pointer-events-none absolute right-5 top-6 rotate-12 rounded-xl border-4 border-red-400 px-3 py-1 text-3xl font-black uppercase tracking-wider text-red-300"
                                      :style="`opacity: ${nopeOpacity}`">Nope</span>
                            </div>
                        </template>

                        {{-- Finished --}}
                        <template x-if="done">
                            <div class="absolute inset-0 flex flex-col items-center justify-center rounded-3xl border border-dashed border-white/15 bg-ink-900/60 p-8 text-center">
                                <span class="grid size-14 place-items-center rounded-2xl bg-brand-600/15 text-brand-300 ring-1 ring-brand-400/20"><x-icon name="check" class="size-7" /></span>
                                @if ($room->is_persistent)
                                    <p class="mt-4 text-lg font-bold text-white">All caught up</p>
                                    <p class="mt-1 text-sm text-gray-400">We’ll deal fresh movies as soon as there are more.</p>
                                @else
                                    <p class="mt-4 text-lg font-bold text-white">You’ve swiped them all</p>
                                    <p class="mt-1 text-sm text-gray-400">{{ $isSolo ? 'Deal more to keep going.' : 'Waiting for the others to finish…' }}</p>
                                @endif
                            </div>
                        </template>
                    </div>

                    {{-- Buttons --}}
                    <div class="mt-4 flex items-center justify-center gap-6 sm:mt-6">
                        <button type="button" @click="decide(false)" :disabled="done || leaving" aria-label="Nope"
                                class="grid size-16 place-items-center rounded-full bg-ink-900 text-red-400 shadow-xl ring-1 ring-white/10 transition hover:scale-110 hover:bg-red-500 hover:text-white disabled:opacity-40 disabled:hover:scale-100">
                            <x-icon name="x" class="size-8" />
                        </button>
                        <p class="w-20 text-center text-xs font-semibold text-gray-500"><span x-text="remaining"></span> left</p>
                        <button type="button" @click="decide(true)" :disabled="done || leaving" aria-label="Yes, I’d watch it"
                                class="grid size-16 place-items-center rounded-full bg-ink-900 text-brand-400 shadow-xl ring-1 ring-white/10 transition hover:scale-110 hover:bg-brand-600 hover:text-white disabled:opacity-40 disabled:hover:scale-100">
                            <x-icon name="heart-solid" class="size-8" />
                        </button>
                    </div>
                    <p class="mt-3 hidden text-center text-xs text-gray-500 sm:block">Drag the card, tap the buttons, or use the ← → keys.</p>
                </div>

                <div class="space-y-6">
                    @include('livewire.movie-night.partials.participants')

                    @if ($room->is_persistent && $participants->count() < \App\Models\MatchRoom::MAX_US_PARTICIPANTS && $qrSvg)
                        <aside class="rounded-3xl bg-ink-900/70 p-5 text-center ring-1 ring-white/5">
                            <p class="text-sm font-bold text-white">Waiting for your partner</p>
                            <p class="mt-1 text-xs text-gray-400">Keep swiping: matches appear as soon as they like the same movies.</p>
                            <div class="mx-auto mt-4 w-fit rounded-2xl bg-white p-3 [&_svg]:size-32" aria-hidden="true">{!! $qrSvg !!}</div>
                            <p class="mt-2 text-xs text-gray-500">Scan to join · they’ll need an account</p>
                        </aside>
                    @endif
                </div>
            </section>
        @endif
    @endif
</div>
