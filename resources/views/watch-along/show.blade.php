@extends('layouts.main')

@section('title', 'Watch Along · ' . $party->title())

@push('meta')
    <meta property="og:title" content="Watch {{ $party->title() }} together on Envydeo">
    <meta property="og:description" content="Join the Watch Along: press play at the same time and react together, wherever you are.">
    @if (!empty($party->movie['poster_path']))
        <meta property="og:image" content="https://image.tmdb.org/t/p/w500{{ $party->movie['poster_path'] }}">
    @endif
@endpush

@php
    $poster = !empty($party->movie['poster_path']) ? 'https://image.tmdb.org/t/p/w342' . $party->movie['poster_path'] : null;
    $backdrop = !empty($party->movie['backdrop_path']) ? 'https://image.tmdb.org/t/p/w1280' . $party->movie['backdrop_path'] : null;
    $host = $members->firstWhere('is_host', true);
    $kinds = \App\Models\WatchPartyReaction::KINDS;
@endphp

@section('content')
<div class="relative isolate min-h-[80svh]">
    @if ($backdrop)
        <img src="{{ $backdrop }}" alt="" class="absolute inset-x-0 top-0 -z-10 h-[28rem] w-full object-cover opacity-25 [mask-image:linear-gradient(to_bottom,black,transparent)]">
    @endif

    <div class="shell page-top pb-10">
        {{-- ============================================================ HEADER --}}
        <header class="flex items-center gap-4">
            @if ($poster)
                <a href="{{ $party->titleUrl() }}" class="shrink-0"><img src="{{ $poster }}" alt="" class="w-16 rounded-xl shadow-xl ring-1 ring-white/10 sm:w-20"></a>
            @endif
            <div class="min-w-0">
                <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.16em] text-brand-300">🎬 Watch Along</p>
                <h1 class="mt-1 text-2xl font-black leading-tight tracking-tight text-white sm:text-4xl">{{ $party->title() }}</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Hosted by {{ $host?->name ?? 'someone' }} · {{ \App\Models\WatchParty::formatTime($party->runtime_seconds) }} long
                </p>
            </div>
        </header>

        @if (!$me)
            {{-- ============================================================ NOT A MEMBER --}}
            <section class="mx-auto mt-10 max-w-md rounded-3xl bg-ink-900/80 p-6 text-center ring-1 ring-white/10 backdrop-blur">
                @if ($party->isOver() || $expired)
                    <p class="text-lg font-bold text-white">This Watch Along has ended</p>
                    <p class="mt-1 text-sm text-gray-400">Start your own from any movie or show page.</p>
                    <a href="{{ $party->titleUrl() }}" class="btn-brand mt-5">Go to {{ $party->movie['title'] ?? 'the title' }}</a>
                @else
                    <div class="mx-auto flex w-fit -space-x-2">
                        @foreach ($members->take(5) as $m)
                            <span class="grid size-10 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-emerald-700 text-xs font-bold text-white ring-2 ring-ink-900">{{ $m->initials() }}</span>
                        @endforeach
                    </div>
                    <p class="mt-4 text-lg font-bold text-white">{{ $host?->name ?? 'A friend' }} invited you to watch together</p>
                    <p class="mt-1 text-sm text-gray-400">Everyone presses play at the same time on their own streaming app. Envydeo keeps you in sync and lets you react together.</p>
                    <form method="POST" action="{{ route('watch-along.join', $party->code) }}" class="mt-5 space-y-3">
                        @csrf
                        @auth
                            <button type="submit" class="btn-brand w-full">Join as {{ auth()->user()->name }}</button>
                        @else
                            <input type="text" name="name" maxlength="40" required placeholder="Your name" value="{{ old('name') }}" aria-label="Your name"
                                   class="w-full rounded-xl bg-ink-800 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <button type="submit" class="btn-brand w-full">Join</button>
                            <p class="text-xs text-gray-500">No account needed. <a href="{{ route('login') }}" class="font-semibold text-gray-300 hover:text-white">Sign in</a> to keep it in your feed.</p>
                        @endauth
                        <x-input-error :messages="$errors->get('name')" />
                    </form>
                @endif
            </section>

        @elseif ($summary)
            {{-- ============================================================ ENDED: MOMENT MAP --}}
            @include('watch-along.partials.summary')

        @else
            {{-- ============================================================ LIVE --}}
            <div x-data="watchAlong({ code: @js($party->code), initial: @js($initialState), urls: {
                    state: @js(route('watch-along.state', $party->code)),
                    react: @js(route('watch-along.react', $party->code)),
                    control: @js(route('watch-along.control', $party->code)),
                    invite: @js(route('watch-along.invite', $party->code)),
                 } })" class="mt-8">

                {{-- Toast (pause/resume/etc.) --}}
                <div x-show="toast" x-cloak x-transition.opacity
                     class="fixed inset-x-0 top-20 z-[60] mx-auto w-max max-w-[90vw] rounded-full bg-ink-800/95 px-4 py-2 text-sm font-semibold text-white shadow-xl ring-1 ring-white/10 backdrop-blur" x-text="toast"></div>

                {{-- ---------------------------------------------- LOBBY --}}
                <section x-show="status === 'lobby'" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div class="space-y-6">
                        <div class="rounded-3xl bg-gradient-to-br from-brand-700/30 via-ink-900 to-ink-900 p-6 ring-1 ring-brand-500/20">
                            <h2 class="text-xl font-extrabold text-white">Get ready</h2>
                            <ol class="mt-4 space-y-3 text-sm text-gray-200">
                                <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">1</span> Open <strong class="text-white">{{ $party->title() }}</strong> on your streaming app (or TV) and leave it at the very start, paused.</li>
                                <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">2</span> Keep this page open on your phone. When {{ $host?->is($me) ? 'you start' : 'the host starts' }} the countdown, everyone presses play the moment it hits <strong class="text-white">zero</strong>.</li>
                                <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">3</span> React as you watch. Every reaction is pinned to that second of the {{ $party->media_type === 'tv' ? 'episode' : 'movie' }}.</li>
                            </ol>
                            <div class="mt-6">
                                <template x-if="isHost">
                                    <button type="button" @click="control('start')" :disabled="busy" class="btn-brand w-full !py-3 text-base sm:w-auto">
                                        ▶ Start the countdown
                                    </button>
                                </template>
                                <template x-if="!isHost">
                                    <p class="flex items-center gap-2 text-sm font-semibold text-gray-300"><span class="loading loading-dots loading-sm text-brand-300"></span> Waiting for {{ $host?->name ?? 'the host' }} to start…</p>
                                </template>
                            </div>
                        </div>

                        @if ($whereToWatch)
                            <x-where-to-watch :data="$whereToWatch" :region="$region" :regions="$regions" />
                        @endif
                    </div>

                    <aside class="space-y-6">
                        <div class="rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5">
                            <h2 class="text-sm font-bold text-white">Who’s here <span class="font-normal text-gray-500" x-text="`· ${here} of ${members.length}`"></span></h2>
                            <ul class="mt-3 space-y-2">
                                <template x-for="m in members" :key="m.id">
                                    <li class="flex items-center gap-3">
                                        <span class="relative grid size-9 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-emerald-700 text-xs font-bold text-white">
                                            <span x-text="m.initials"></span>
                                            <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full ring-2 ring-ink-900" :class="m.here ? 'bg-brand-400' : 'bg-gray-600'"></span>
                                        </span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-semibold text-white" x-text="m.name + (m.me ? ' (you)' : '')"></span>
                                        <span x-show="m.host" class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-bold text-gray-300">HOST</span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div class="rounded-3xl bg-ink-900/70 p-5 text-center ring-1 ring-white/5" x-data="{ copied: false }">
                            <p class="text-sm font-bold text-white">Invite people</p>
                            <p class="mt-1 font-mono text-2xl font-black tracking-[0.3em] text-brand-200">{{ $party->code }}</p>
                            @if ($qrSvg)
                                <div class="mx-auto mt-3 w-fit rounded-2xl bg-white p-2.5 [&_svg]:size-32" aria-hidden="true">{!! $qrSvg !!}</div>
                            @endif
                            <button type="button" class="btn-glass mt-4 w-full !py-2 text-sm"
                                    @click="navigator.share ? navigator.share({ title: 'Watch Along on Envydeo', text: @js('Watch ' . $party->title() . ' with me 🎬'), url: @js(route('watch-along.show', $party->code)) }).catch(() => {}) : navigator.clipboard.writeText(@js(route('watch-along.show', $party->code))).then(() => copied = true)">
                                <span x-text="copied ? 'Link copied!' : 'Share the link'"></span>
                            </button>
                        </div>

                        @if ($invitable->isNotEmpty())
                            <div class="rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5" x-data="{ sent: [] }">
                                <p class="text-sm font-bold text-white">Invite friends</p>
                                <ul class="mt-3 space-y-2">
                                    @foreach ($invitable as $friend)
                                        <li class="flex items-center gap-3">
                                            <x-avatar :user="$friend" size="sm" />
                                            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-white">{{ $friend->name }}</span>
                                            <button type="button" class="rounded-full px-3 py-1 text-xs font-bold ring-1 transition"
                                                    :class="sent.includes(@js($friend->id)) ? 'bg-brand-600/20 text-brand-200 ring-brand-400/30' : 'bg-white/5 text-white ring-white/15 hover:bg-white/10'"
                                                    :disabled="sent.includes(@js($friend->id))"
                                                    @click="window.axios.post(urls.invite, { user_id: @js($friend->id) }).then(() => sent.push(@js($friend->id))).catch(() => flash('Couldn’t invite right now.'))"
                                                    x-text="sent.includes(@js($friend->id)) ? 'Invited' : 'Invite'"></button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </aside>
                </section>

                {{-- ---------------------------------------------- PLAYING / PAUSED --}}
                <section x-show="status === 'playing' || status === 'paused'" x-cloak class="mx-auto max-w-xl">
                    {{-- Clock --}}
                    <div class="relative overflow-hidden rounded-[2rem] bg-ink-900/80 p-6 text-center ring-1 ring-white/10 backdrop-blur">
                        <template x-if="countdown !== null">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-[0.2em] text-brand-300">Get ready to press play</p>
                                <p class="mt-2 text-8xl font-black tabular-nums text-white" x-text="countdown"></p>
                            </div>
                        </template>
                        <template x-if="countdown === null">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em]"
                                   :class="pressPlay ? 'text-brand-300 animate-pulse' : (status === 'paused' ? 'text-amber-300' : 'text-gray-400')"
                                   x-text="pressPlay ? '▶ Press play now!' : (status === 'paused' ? '⏸ Paused: pause yours too' : 'You should be at')"></p>
                                <p class="mt-1 font-mono text-6xl font-black tabular-nums text-white sm:text-7xl" x-text="clock"></p>
                                <div class="mx-auto mt-4 h-1.5 max-w-sm overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-sky-400" :style="`width: ${progress}%`"></div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500"><span x-text="format(runtime)"></span> total · <span x-text="here"></span> watching</p>
                            </div>
                        </template>

                        {{-- Floating reactions from everyone --}}
                        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                            <template x-for="f in floaters" :key="f.id">
                                <div class="absolute bottom-2 flex flex-col items-center animate-[wa-float_3.2s_ease-out_forwards]" :style="`left: ${f.x}%`">
                                    <span class="text-4xl" x-text="f.emoji"></span>
                                    <span class="mt-0.5 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-semibold text-white" x-text="f.body ? `${f.name}: ${f.body}` : f.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Reactions --}}
                    <div class="mt-4 grid grid-cols-6 gap-2">
                        @foreach ($kinds as $key => [$emoji, $word])
                            <button type="button" @click="react(@js($key), @js($emoji))" aria-label="React {{ $emoji }}"
                                    class="grid aspect-square place-items-center rounded-2xl bg-ink-900/80 text-3xl ring-1 ring-white/10 transition active:scale-90 hover:bg-white/10">{{ $emoji }}</button>
                        @endforeach
                    </div>
                    <form @submit.prevent="say()" class="mt-3 flex gap-2">
                        <input type="text" x-model="text" maxlength="80" placeholder="Say something…" aria-label="Message"
                               class="min-w-0 flex-1 rounded-full bg-ink-800 px-4 py-2.5 text-sm text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <button type="submit" class="btn-brand !px-4 !py-2.5 text-sm">Send</button>
                    </form>

                    {{-- Controls --}}
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                        <button type="button" x-show="status === 'playing'" @click="control('pause')" :disabled="busy || countdown !== null" class="btn-glass !py-2 text-sm">⏸ Pause for everyone</button>
                        <button type="button" x-show="status === 'paused'" @click="control('resume')" :disabled="busy" class="btn-brand !py-2 text-sm">▶ Resume for everyone</button>
                        <template x-if="isHost">
                            <span class="flex flex-wrap items-center justify-center gap-2">
                                <button type="button" @click="control('shift', -30)" :disabled="busy" class="btn-glass !px-3 !py-2 text-xs">−30s</button>
                                <button type="button" @click="control('shift', 30)" :disabled="busy" class="btn-glass !px-3 !py-2 text-xs">+30s</button>
                                <button type="button" @click="control('end')" :disabled="busy" class="btn-glass !px-3 !py-2 text-xs !text-red-300">End</button>
                            </span>
                        </template>
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-500">
                        Your stream a little off?
                        <button type="button" @click="nudge(-5)" class="font-semibold text-gray-300 hover:text-white">−5s</button> ·
                        <button type="button" @click="nudge(5)" class="font-semibold text-gray-300 hover:text-white">+5s</button>
                        <span x-show="nudgeMs !== 0" x-text="`(you: ${nudgeMs > 0 ? '+' : ''}${nudgeMs / 1000}s)`"></span>
                    </p>

                    {{-- Live feed --}}
                    <ul class="mt-6 space-y-1.5" aria-live="polite">
                        <template x-for="r in feed" :key="r.id">
                            <li class="flex items-center gap-2 text-sm">
                                <span class="w-14 shrink-0 text-right font-mono text-[11px] text-gray-500" x-text="format(r.pos)"></span>
                                <span class="text-lg leading-none" x-text="r.kind === 'text' ? '💬' : r.emoji"></span>
                                <span class="min-w-0 truncate text-gray-300"><strong class="text-white" x-text="(r.mine ? 'You' : r.name.split(' ')[0]) + (r.body ? ':' : '')"></strong> <span x-text="r.body || ''"></span></span>
                            </li>
                        </template>
                    </ul>
                    <p x-show="!feed.length" class="mt-6 text-center text-sm text-gray-500">Reactions show up here, pinned to the moment they happened.</p>
                </section>
            </div>
        @endif
    </div>
</div>
@endsection
