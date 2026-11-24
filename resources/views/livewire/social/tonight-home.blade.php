@php
    $viewerHasLive = $groups->contains(fn ($g) => $g['user']->is($viewer));
    $firstName = \Illuminate\Support\Str::before($viewer->name, ' ');
@endphp

<div class="space-y-12">
    {{-- ============================================================ HEADER + ACTIONS --}}
    <header>
        <p class="text-sm font-semibold text-gray-400">Hey {{ $firstName }} 👋</p>
        <h1 class="mt-1 text-4xl font-black tracking-tight text-white sm:text-5xl">What’s it going to be tonight?</h1>
        <div class="mt-6 grid grid-cols-3 gap-2 sm:flex sm:flex-wrap sm:gap-3">
            <a href="{{ route('movie-night.index') }}" class="btn-brand flex-col !gap-1 !px-3 !py-3 text-sm sm:flex-row sm:!gap-2 sm:!px-5">
                <x-icon name="users" class="size-5" /> Start a room
            </a>
            <form method="POST" action="{{ route('movie-night.quick') }}" class="contents">
                @csrf
                <button type="submit" class="btn-glass flex-col !gap-1 !px-3 !py-3 text-sm sm:flex-row sm:!gap-2 sm:!px-5">
                    <x-icon name="sparkles" class="size-5" /> Decide for me
                </button>
            </form>
            <button type="button" @click="$dispatch('open-check-in')" class="btn-glass flex-col !gap-1 !px-3 !py-3 text-sm sm:flex-row sm:!gap-2 sm:!px-5">
                <x-icon name="photo" class="size-5" /> Check in
            </button>
        </div>
    </header>

    {{-- ============================================================ TONIGHT (friends live) --}}
    <section aria-labelledby="live-heading">
        <div class="flex items-center justify-between gap-3">
            <h2 id="live-heading" class="section-title">Watching tonight</h2>
            <a href="{{ route('feed') }}" class="text-sm font-semibold text-brand-300 hover:text-brand-200">Friends →</a>
        </div>
        <ul class="no-scrollbar -mx-4 mt-4 flex gap-4 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:px-6 lg:mx-0 lg:px-0">
            @unless ($viewerHasLive)
                <li class="w-[4.5rem] shrink-0 text-center">
                    <button type="button" @click="$dispatch('open-check-in')" class="group block w-full">
                        <span class="relative mx-auto grid size-16 place-items-center rounded-full border-2 border-dashed border-white/25 bg-white/[0.03] text-brand-300 transition group-hover:border-brand-400">
                            <x-icon name="photo" class="size-6" />
                            <span class="absolute -bottom-0.5 -right-0.5 grid size-6 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white ring-2 ring-ink-950">+</span>
                        </span>
                        <span class="mt-1.5 block truncate text-xs font-semibold text-gray-300">You</span>
                    </button>
                </li>
            @endunless
            @foreach ($groups as $group)
                <li class="w-[4.5rem] shrink-0 text-center" wire:key="live-{{ $group['user']->id }}">
                    <a href="{{ route('feed', ['tonight' => $group['checkIns']->first()->id]) }}" class="group block" aria-label="Open {{ $group['user']->name }}’s check-ins">
                        <span class="mx-auto block rounded-full bg-gradient-to-tr from-brand-400 via-emerald-300 to-sky-400 p-[3px] transition group-hover:scale-105">
                            <span class="block rounded-full bg-ink-950 p-[2px]"><x-avatar :user="$group['user']" size="lg" class="!size-[3.4rem]" /></span>
                        </span>
                        <span class="mt-1.5 block truncate text-xs font-semibold text-gray-200">{{ $group['user']->is($viewer) ? 'You' : $group['user']->name }}</span>
                        <span class="block truncate text-[10px] text-gray-500">{{ $group['checkIns']->last()->movie['title'] ?? '' }}</span>
                    </a>
                </li>
            @endforeach
            @if ($groups->isEmpty() || ($groups->count() === 1 && $viewerHasLive))
                <li class="flex min-w-[14rem] items-center text-sm text-gray-500">
                    {{ $friendCount ? 'No friends have checked in yet tonight.' : 'Follow friends (and get followed back) to see what they’re watching.' }}
                </li>
            @endif
        </ul>
    </section>

    {{-- ============================================================ OVERLAPS --}}
    <section aria-labelledby="overlap-heading">
        <h2 id="overlap-heading" class="section-title">You both want to watch</h2>
        @error('night') <p class="mt-3 text-sm text-red-300">{{ $message }}</p> @enderror
        @if ($overlaps->isNotEmpty())
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($overlaps as $o)
                    @php
                        $m = $o['movie'];
                        $isTv = $o['type'] === 'tv';
                        $href = route($isTv ? 'show.show' : 'movie.show', $m['id']);
                        $name = $m['title'] ?? $m['name'] ?? '';
                        $date = $m['release_date'] ?? $m['first_air_date'] ?? '';
                    @endphp
                    <article class="relative isolate flex gap-4 overflow-hidden rounded-3xl bg-ink-900 p-4 ring-1 ring-white/10" wire:key="ov-{{ $o['type'] }}-{{ $m['id'] }}">
                        @if (!empty($m['backdrop_path']))
                            <img src="https://image.tmdb.org/t/p/w780{{ $m['backdrop_path'] }}" alt="" class="absolute inset-0 -z-10 size-full object-cover opacity-20">
                            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink-950 via-ink-950/80 to-ink-950/40"></div>
                        @endif
                        <a href="{{ $href }}" class="shrink-0">
                            <img src="https://image.tmdb.org/t/p/w185{{ $m['poster_path'] ?? '' }}" alt="{{ $name }}" class="w-20 rounded-xl ring-1 ring-white/10">
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="flex items-center gap-2">
                                <div class="flex -space-x-2">
                                    <x-avatar :user="$viewer" size="xs" class="ring-2 ring-ink-900" />
                                    @foreach ($o['people']->take(3) as $p) <x-avatar :user="$p" size="xs" class="ring-2 ring-ink-900" /> @endforeach
                                </div>
                                <p class="truncate text-xs text-gray-300">You &amp; {{ $o['people']->take(2)->pluck('name')->implode(' & ') }}{{ $o['people']->count() > 2 ? ' +' . ($o['people']->count() - 2) : '' }}</p>
                            </div>
                            <a href="{{ $href }}" class="mt-2 line-clamp-2 text-lg font-extrabold leading-tight text-white hover:text-brand-300">{{ $name }}</a>
                            <p class="text-xs text-gray-400">{{ $isTv ? 'TV · ' : '' }}{{ substr($date, 0, 4) }}{{ !empty($m['runtime']) ? ' · ' . floor($m['runtime'] / 60) . 'h ' . ($m['runtime'] % 60) . 'm' : '' }}</p>
                            @if ($isTv)
                                <a href="{{ $href }}#where-to-watch" class="btn-glass mt-auto self-start !px-4 !py-2 text-sm">📺 Where to watch it</a>
                            @else
                            <button type="button" wire:click="startNight({{ $m['id'] }})" wire:loading.attr="disabled" class="btn-brand mt-auto self-start !px-4 !py-2 text-sm">
                                <span wire:loading.remove wire:target="startNight({{ $m['id'] }})">🍿 Movie night with them</span>
                                <span wire:loading wire:target="startNight({{ $m['id'] }})" class="loading loading-spinner loading-sm"></span>
                            </button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="mt-4 rounded-3xl border border-dashed border-white/10 p-6 text-sm text-gray-400">
                When someone you follow saves a movie that’s also on <a href="{{ route('watchlist.index') }}" class="font-semibold text-white hover:text-brand-300">your watchlist</a>, it shows up here, with one tap to start a movie night together.
            </div>
        @endif
    </section>

    {{-- ============================================================ YOUR ROOMS --}}
    @if ($rooms->isNotEmpty() || $parties->isNotEmpty())
        <section aria-labelledby="rooms-heading">
            <h2 id="rooms-heading" class="section-title">Your rooms</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($parties as $party)
                    <li wire:key="party-{{ $party->id }}">
                        <a href="{{ route('watch-along.show', $party->code) }}" class="flex items-center justify-between gap-3 rounded-2xl bg-gradient-to-br from-sky-700/25 to-ink-900 p-4 ring-1 ring-sky-400/20 transition hover:ring-sky-300/50">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-white">🎬 {{ $party->title() }}</span>
                                <span class="block text-xs text-gray-400">{{ ['lobby' => 'Watch Along · getting ready', 'playing' => 'Watch Along · live now', 'paused' => 'Watch Along · paused'][$party->status] ?? 'Watch Along' }} · {{ $party->members_count }} {{ \Illuminate\Support\Str::plural('person', $party->members_count) }}</span>
                            </span>
                            <span class="rounded-lg bg-white/5 px-2 py-1 font-mono text-xs font-bold tracking-widest text-gray-300">{{ $party->code }}</span>
                        </a>
                    </li>
                @endforeach
                @foreach ($rooms as $room)
                    @php
                        $label = $room->is_persistent ? '💑 Us room' : (['lobby' => 'Waiting in the lobby', 'voting' => 'Swiping now', 'matched' => 'Matched'][$room->status] ?? $room->status);
                    @endphp
                    <li wire:key="room-{{ $room->id }}">
                        <a href="{{ route('movie-night.show', $room->code) }}" class="flex items-center justify-between gap-3 rounded-2xl bg-ink-900/70 p-4 ring-1 ring-white/5 transition hover:ring-brand-400/40">
                            <span>
                                <span class="block text-sm font-bold text-white">{{ $room->theme()['name'] ?? ($room->source === 'watchlist' ? 'Watchlist Showdown' : 'Movie Night') }}</span>
                                <span class="block text-xs text-gray-400">{{ $label }} · {{ $room->participants_count }} {{ \Illuminate\Support\Str::plural('person', $room->participants_count) }}</span>
                            </span>
                            <span class="rounded-lg bg-white/5 px-2 py-1 font-mono text-xs font-bold tracking-widest text-gray-300">{{ $room->code }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif


    {{-- ============================================================ LATELY --}}
    <section aria-labelledby="lately-heading">
        <div class="flex items-center justify-between gap-3">
            <h2 id="lately-heading" class="section-title">Lately with friends</h2>
            <a href="{{ route('feed') }}" class="text-sm font-semibold text-brand-300 hover:text-brand-200">See all →</a>
        </div>
        <div class="mt-4 max-w-2xl space-y-3">
            @forelse ($lately as $activity)
                @include('livewire.social.partials.activity', ['activity' => $activity])
            @empty
                <x-empty-state icon="users" title="Quiet so far" message="Follow friends to see what they watch, match on and think of it.">
                    <a href="{{ route('people') }}" class="btn-brand">Find people</a>
                </x-empty-state>
            @endforelse
        </div>
    </section>

    {{-- ============================================================ POPULAR --}}
    <div wire:ignore>
        <x-poster-row title="Popular to swipe on" url="/api/movies/popular" :genres="app(\App\Services\Tmdb::class)->movieGenres()" :href="route('movie.list')" />
    </div>
</div>
