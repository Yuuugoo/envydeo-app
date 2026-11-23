{{--
    Legal streaming / rent / buy options for one title in the visitor's country.
    Data: MovieNightDealer::whereToWatch() (TMDB watch providers, powered by JustWatch — attribution required).
    <x-where-to-watch :data="$whereToWatch" region="Philippines" />
--}}
@props(['data' => null, 'region' => null])

@php
    $sections = ['stream' => 'Stream', 'rent' => 'Rent', 'buy' => 'Buy'];
    $hasAny = $data && collect($sections)->keys()->contains(fn ($key) => !empty($data[$key]));
@endphp

<section {{ $attributes->merge(['class' => 'rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8']) }} aria-labelledby="where-to-watch">
    <h2 id="where-to-watch" class="section-title scroll-mt-28">Where to watch{{ $region ? ' in ' . $region : '' }}</h2>

    @if ($hasAny)
        <div class="mt-5 grid gap-6 sm:grid-cols-3">
            @foreach ($sections as $key => $label)
                @if (!empty($data[$key]))
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ $label }}</p>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($data[$key] as $provider)
                                <li title="{{ $provider['name'] }}">
                                    @if ($provider['logo'])
                                        <img src="https://image.tmdb.org/t/p/w92{{ $provider['logo'] }}" alt="{{ $provider['name'] }}" loading="lazy" class="size-11 rounded-xl ring-1 ring-white/10">
                                    @else
                                        <span class="block rounded-lg bg-white/5 px-2 py-1 text-xs">{{ $provider['name'] }}</span>
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
        @if (!empty($data['link']))
            <a href="{{ $data['link'] }}" target="_blank" rel="noopener" class="font-semibold text-brand-300 hover:text-brand-200">See all options →</a>
        @endif
    </p>
</section>
