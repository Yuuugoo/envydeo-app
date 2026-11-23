<div class="rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8">
    <h3 class="section-title">Where to watch{{ $regionName ? ' in ' . $regionName : '' }}</h3>
    @php $hasAny = $whereToWatch && ($whereToWatch['stream'] || $whereToWatch['rent'] || $whereToWatch['buy']); @endphp
    @if ($hasAny)
        <div class="mt-5 grid gap-6 sm:grid-cols-3">
            @foreach (['stream' => 'Stream', 'rent' => 'Rent', 'buy' => 'Buy'] as $key => $label)
                @if ($whereToWatch[$key])
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ $label }}</p>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($whereToWatch[$key] as $provider)
                                <li title="{{ $provider['name'] }}">
                                    @if ($provider['logo'])
                                        <img src="https://image.tmdb.org/t/p/w92{{ $provider['logo'] }}" alt="{{ $provider['name'] }}" class="size-11 rounded-xl ring-1 ring-white/10">
                                    @else
                                        <span class="rounded-lg bg-white/5 px-2 py-1 text-xs">{{ $provider['name'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <p class="mt-4 text-sm text-gray-400">Not available to stream, rent or buy here right now.</p>
    @endif
    <p class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
        <span>Availability data by JustWatch.</span>
        @if ($whereToWatch['link'] ?? null)
            <a href="{{ $whereToWatch['link'] }}" target="_blank" rel="noopener" class="font-semibold text-brand-300 hover:text-brand-200">See all options on TMDB →</a>
        @endif
    </p>
</div>
