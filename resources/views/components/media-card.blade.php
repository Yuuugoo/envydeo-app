{{-- Poster card for server-rendered grids (movies, TV, search, watchlist). --}}
@props(['item', 'type' => null, 'genres' => null, 'showType' => false])

@php
    $isTv = ($item['media_type'] ?? $type) === 'tv';
    $title = $item['title'] ?? $item['name'] ?? 'Untitled';
    $date = $item['release_date'] ?? $item['first_air_date'] ?? null;
    $year = $date ? substr($date, 0, 4) : null;
    $rating = !empty($item['vote_average']) ? number_format($item['vote_average'], 1) : null;
    $genreNames = isset($item['genre_ids']) && $genres
        ? collect($item['genre_ids'])->map(fn ($id) => $genres[$id] ?? null)->filter()->take(2)
        : collect($item['genres'] ?? [])->pluck('name')->take(2);
    $href = route($isTv ? 'show.show' : 'movie.show', $item['id']);
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group block rounded-xl focus-visible:outline-offset-4']) }}>
    <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-ink-800 shadow-lg shadow-black/30 ring-1 ring-white/5 transition duration-300 group-hover:-translate-y-1 group-hover:ring-brand-400/60">
        @if (!empty($item['poster_path']))
            <img src="https://image.tmdb.org/t/p/w342{{ $item['poster_path'] }}" alt="{{ $title }}" loading="lazy" decoding="async"
                 class="size-full object-cover transition duration-500 ease-out-soft group-hover:scale-105">
        @else
            <div class="flex size-full flex-col items-center justify-center gap-2 p-4 text-center text-gray-600">
                <x-icon :name="$isTv ? 'tv' : 'film'" class="size-8" />
                <span class="text-xs font-semibold">{{ $title }}</span>
            </div>
        @endif

        @if ($rating)
            <span class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-black/70 px-1.5 py-0.5 text-[11px] font-bold text-white backdrop-blur">
                <x-icon name="star-solid" class="size-3 text-amber-400" /> {{ $rating }}
            </span>
        @endif

        @if ($showType)
            <span class="absolute right-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-200 backdrop-blur">
                {{ $isTv ? 'TV' : 'Movie' }}
            </span>
        @endif
    </div>

    <h3 class="mt-2.5 line-clamp-1 text-sm font-semibold text-white transition group-hover:text-brand-300 md:text-[15px]">{{ $title }}</h3>
    <p class="mt-0.5 line-clamp-1 text-xs text-gray-400">
        {{ collect([$year, $genreNames->implode(', ')])->filter()->implode(' • ') }}
    </p>
</a>
