@extends('layouts.main')

@section('title', 'Watchlist')

@section('content')
<div class="shell page-top">
    <header class="mb-8 flex flex-wrap items-end justify-between gap-4 md:mb-10">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Your library</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-5xl">Watchlist</h1>
            @if (count($movies))
                <p class="mt-2 text-sm text-gray-400">{{ count($movies) }} {{ \Illuminate\Support\Str::plural('title', count($movies)) }} saved</p>
            @endif
        </div>
        @if (count($movies))
            <form method="POST" action="{{ route('movie-night.watchlist') }}">
                @csrf
                <button type="submit" class="btn-brand"><x-icon name="sparkles" class="size-5" /> Watchlist Showdown</button>
            </form>
        @endif
    </header>

    @if (session('movie_night_error'))
        <p class="mb-8 rounded-2xl bg-red-500/10 px-4 py-3 text-sm text-red-300 ring-1 ring-red-500/30">{{ session('movie_night_error') }}</p>
    @endif

    @if (count($movies))
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach ($movies as $movie)
                <x-media-card :item="$movie" show-type />
            @endforeach
        </div>
    @else
        <x-empty-state icon="bookmark" title="Your watchlist is empty"
                       message="Tap “Watchlist” on any movie or show to save it here for later.">
            <a href="{{ route('movie.index') }}" class="btn-brand">Browse movies</a>
        </x-empty-state>
    @endif
</div>
@endsection
