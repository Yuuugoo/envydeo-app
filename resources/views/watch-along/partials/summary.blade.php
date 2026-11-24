{{-- End of a Watch Along: the group's moment map. Only members see this (it contains names). --}}
@php
    $kinds = \App\Models\WatchPartyReaction::KINDS;
    $barColor = ['scream' => 'bg-violet-400', 'laugh' => 'bg-amber-300', 'cry' => 'bg-sky-400', 'fire' => 'bg-orange-400', 'mind' => 'bg-fuchsia-400', 'love' => 'bg-rose-400'];
    $total = array_sum($summary['counts']);
@endphp

<div class="mt-8 space-y-8">
    <section class="rounded-[2rem] bg-gradient-to-br from-brand-700/30 via-ink-900 to-fuchsia-900/20 p-6 ring-1 ring-white/10 sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-300">That’s a wrap</p>
        <h2 class="mt-1 text-2xl font-black text-white sm:text-3xl">
            {{ $members->count() }} {{ \Illuminate\Support\Str::plural('person', $members->count()) }} watched together
        </h2>
        <p class="mt-1 text-sm text-gray-300">
            {{ $members->pluck('name')->map(fn ($n) => explode(' ', $n)[0])->join(', ', ' & ') }}
            · {{ \App\Models\WatchParty::formatTime($summary['watched']) }} on the clock
            · {{ $total }} {{ \Illuminate\Support\Str::plural('reaction', $total) }}
        </p>
        @if ($summary['counts'])
            <p class="mt-4 flex flex-wrap gap-2">
                @foreach ($summary['counts'] as $kind => $n)
                    <span class="rounded-full bg-white/10 px-3 py-1 text-sm font-bold text-white ring-1 ring-white/10">{{ $kinds[$kind][0] ?? '' }} {{ $n }}</span>
                @endforeach
            </p>
        @endif
    </section>

    {{-- Moment map --}}
    <section aria-labelledby="map-heading">
        <h2 id="map-heading" class="section-title">Your moment map</h2>
        @if ($total)
            <div class="mt-5 rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5">
                <div class="flex h-36 items-end gap-[2px]" role="img" aria-label="Reactions across the {{ $party->media_type === 'tv' ? 'episode' : 'movie' }}">
                    @foreach ($summary['bars'] as $bar)
                        <span class="min-w-0 flex-1 rounded-t-sm {{ $bar['count'] ? ($barColor[$bar['kind']] ?? 'bg-brand-400') : 'bg-white/5' }}"
                              style="height: {{ $bar['count'] ? max(8, 100 * $bar['count'] / $summary['max']) : 3 }}%"
                              title="{{ \App\Models\WatchParty::formatTime($bar['start']) }}{{ $bar['count'] ? ' · ' . $bar['count'] . ' ' . ($kinds[$bar['kind']][0] ?? '') : '' }}"></span>
                    @endforeach
                </div>
                <div class="mt-2 flex justify-between font-mono text-[11px] text-gray-500">
                    <span>0:00</span><span>{{ \App\Models\WatchParty::formatTime(intdiv($party->runtime_seconds, 2)) }}</span><span>{{ \App\Models\WatchParty::formatTime($party->runtime_seconds) }}</span>
                </div>
            </div>

            @if ($summary['moments'])
                <ol class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach ($summary['moments'] as $i => $moment)
                        <li class="rounded-2xl bg-ink-900/70 p-4 ring-1 ring-white/5">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ ['Biggest moment', 'Runner-up', 'Also'][$i] }}</p>
                            <p class="mt-2 text-3xl">{{ $moment['emoji'] }}</p>
                            <p class="mt-1 font-bold text-white">at {{ \App\Models\WatchParty::formatTime($moment['start']) }}</p>
                            <p class="text-sm text-gray-400">{{ implode(', ', array_slice($moment['names'], 0, 3)) }} {{ $moment['word'] }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        @else
            <p class="mt-4 text-sm text-gray-400">Nobody reacted this time. Next time, tap the emoji as you watch!</p>
        @endif
    </section>

    <div class="flex flex-wrap gap-3">
        <a href="{{ $party->titleUrl() }}" class="btn-brand">Give it a verdict</a>
        <a href="{{ route('movie-night.index') }}" class="btn-glass">Pick the next one</a>
    </div>

    {{-- Replay --}}
    @if ($summary['replay']->isNotEmpty())
        <section aria-labelledby="replay-heading">
            <h2 id="replay-heading" class="section-title">Replay</h2>
            <ul class="mt-4 max-w-xl space-y-1.5">
                @foreach ($summary['replay'] as $r)
                    <li class="flex items-center gap-2 text-sm">
                        <span class="w-14 shrink-0 text-right font-mono text-[11px] text-gray-500">{{ \App\Models\WatchParty::formatTime($r->position_s) }}</span>
                        <span class="text-lg leading-none">{{ $r->kind === 'text' ? '💬' : $r->emoji() }}</span>
                        <span class="min-w-0 text-gray-300"><strong class="text-white">{{ explode(' ', $r->member?->name ?? '')[0] }}{{ $r->body ? ':' : '' }}</strong> {{ $r->body }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
