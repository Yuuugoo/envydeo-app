@extends('layouts.main')

@section('title', 'TV Shows')

@section('content')
<div class="shell page-top">
    <header class="mb-8 md:mb-10">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Trending today</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-5xl">TV shows</h1>
        <p class="mt-2 text-sm text-gray-400">What everyone is watching · Page {{ $currentPage }} of {{ number_format($totalPages) }}</p>
    </header>

    @if (count($popularTV['results']))
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach ($popularTV['results'] as $show)
                <x-media-card :item="$show" type="tv" :genres="$genres" />
            @endforeach
        </div>
    @else
        <x-empty-state icon="tv" title="No shows right now" message="We couldn’t load this page. Please try again in a moment." />
    @endif

    <x-pagination :current="$currentPage" :total="$totalPages" route="show.list" />
</div>
@endsection
