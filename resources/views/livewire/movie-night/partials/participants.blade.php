<aside class="rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5">
    <h2 class="flex items-center justify-between text-sm font-bold text-white">
        <span class="flex items-center gap-2"><x-icon name="users" class="size-5 text-gray-400" /> In the room</span>
        <span class="rounded-full bg-white/10 px-2 py-0.5 text-xs text-gray-300">{{ $participants->count() }}</span>
    </h2>
    <ul class="mt-4 space-y-2">
        @foreach ($participants as $p)
            @php $done = min($p->votes_count, $deckSize); @endphp
            <li wire:key="participant-{{ $p->id }}" class="flex items-center gap-3 rounded-2xl bg-white/[0.03] p-2.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-700 text-sm font-bold text-white">{{ $p->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-sm font-semibold text-white">
                        <span class="truncate">{{ $p->name }}</span>
                        @if ($me && $p->id === $me->id) <span class="shrink-0 text-xs font-normal text-gray-500">(you)</span> @endif
                        @if ($p->is_host) <span class="shrink-0 rounded bg-brand-600/20 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-300">Host</span> @endif
                    </p>
                    @if ($status !== 'lobby')
                        <div class="mt-1.5 flex items-center gap-2">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full rounded-full bg-brand-400 transition-all duration-500" style="width: {{ $deckSize ? round($done / $deckSize * 100) : 0 }}%"></div>
                            </div>
                            <span class="w-10 text-right text-[11px] tabular-nums text-gray-500">{{ $done }}/{{ $deckSize }}</span>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</aside>
