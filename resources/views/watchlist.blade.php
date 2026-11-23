@extends('layouts.main')

@section('title', 'Watchlist')

@section('content')
<div class="shell page-top">
    <header class="mb-8 md:mb-10">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Your library</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-5xl">Watchlist</h1>
        @if (count($movies))
            <p class="mt-2 text-sm text-gray-400">{{ count($movies) }} {{ \Illuminate\Support\Str::plural('title', count($movies)) }} saved</p>
        @endif
    </header>

    @if (count($movies))
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach ($movies as $movie)
                <x-media-card :item="$movie" type="movie" />
            @endforeach
        </div>
    @else
        <x-empty-state icon="bookmark" title="Your watchlist is empty"
                       message="Tap “Watchlist” on any movie to save it here for later.">
            <a href="{{ route('movie.index') }}" class="btn-brand">Browse movies</a>
        </x-empty-state>
    @endif
</div>
@endsection
