@extends('layouts.main')

@section('title', 'Search: ' . $query)

@php
    $types = ['all' => 'All', 'movie' => 'Movies', 'tv' => 'TV Shows'];
    $sorts = ['popularity' => 'Most popular', 'release_date' => 'Newest', 'vote_average' => 'Top rated', 'title' => 'Title A–Z'];
    $withPoster = $searchResults->filter(fn ($r) => !empty($r['poster_path']))->values();
@endphp

@section('content')
<div class="shell page-top">
    <header class="mb-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Search</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-5xl">
            Results for <span class="text-brand-300">“{{ $query }}”</span>
        </h1>
    </header>

    <form action="{{ route('search.results') }}" method="GET" x-data class="mb-10 space-y-4">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-gray-500" />
            <input type="search" name="query" value="{{ $query }}" required
                   class="w-full rounded-2xl bg-ink-900 py-4 pl-12 pr-28 text-base text-white ring-1 ring-white/10 transition placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"
                   placeholder="Search movies & TV shows…" aria-label="Search">
            <button type="submit" class="btn-brand absolute right-2 top-1/2 -translate-y-1/2 !px-4 !py-2">Search</button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0" role="radiogroup" aria-label="Media type">
                @foreach ($types as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="media_type" value="{{ $value }}" class="peer sr-only" @checked($selectedMediaType === $value) @change="$el.form.submit()">
                        <span class="block whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold text-gray-300 ring-1 ring-white/10 transition hover:bg-white/5 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-500 peer-focus-visible:outline-2 peer-focus-visible:outline-brand-400">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-400">
                <x-icon name="adjustments" class="size-5" />
                <span class="sr-only sm:not-sr-only">Sort by</span>
                <select name="sort_by" @change="$el.form.submit()"
                        class="rounded-xl bg-ink-900 py-2 pl-3 pr-9 text-sm font-semibold text-white ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected($selectedSortBy === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </form>

    @if ($withPoster->isNotEmpty())
        <p class="mb-5 text-sm text-gray-400">
            {{ number_format($totalResults) }} {{ \Illuminate\Support\Str::plural('result', $totalResults) }}
            @if ($totalPages > 1) · Page {{ $currentPage }} of {{ $totalPages }} @endif
        </p>
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach ($withPoster as $result)
                <x-media-card :item="$result" show-type />
            @endforeach
        </div>

        <x-pagination :current="$currentPage" :total="$totalPages" route="search.results"
                      :params="['query' => $query, 'media_type' => $selectedMediaType, 'sort_by' => $selectedSortBy]" />
    @else
        <x-empty-state icon="search" title="No results for “{{ $query }}”"
                       message="Try a different spelling, a shorter title, or switch between Movies and TV Shows." />
    @endif
</div>
@endsection
