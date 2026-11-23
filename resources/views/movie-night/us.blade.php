@extends('layouts.main')

@section('title', 'Us')

@section('content')
<div class="shell page-top">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.18em] text-rose-300"><x-icon name="heart-solid" class="size-4" /> Movie Night · Us</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-5xl">Your “Us” rooms</h1>
            <p class="mt-2 max-w-xl text-sm text-gray-400">Permanent rooms for two. Swipe any time, on any device. Movies you both like collect in “Our matches”.</p>
        </div>
        <form method="POST" action="{{ route('movie-night.us.create') }}">
            @csrf
            <button type="submit" class="btn-brand"><x-icon name="heart-solid" class="size-5" /> New “Us” room</button>
        </form>
    </header>

    @if (session('us_error') || session('movie_night_error'))
        <p class="mt-6 rounded-2xl bg-red-500/10 px-4 py-3 text-sm text-red-300 ring-1 ring-red-500/30">{{ session('us_error') ?? session('movie_night_error') }}</p>
    @endif

    <div class="mt-10">
        @if ($rooms->isEmpty())
            <x-empty-state icon="heart" title="No “Us” rooms yet"
                           message="Create one, send the link to your partner, and start swiping. Your matches will pile up here.">
                <form method="POST" action="{{ route('movie-night.us.create') }}">
                    @csrf
                    <button type="submit" class="btn-brand">Create our room</button>
                </form>
            </x-empty-state>
        @else
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rooms as $room)
                    <li>
                        <a href="{{ route('movie-night.show', $room->code) }}" class="group flex items-center gap-4 rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5 transition hover:ring-brand-400/50">
                            <div class="flex -space-x-3">
                                @foreach ($room->participants as $p)
                                    <span class="grid size-12 place-items-center rounded-full bg-brand-700 font-bold text-white ring-4 ring-ink-900">{{ $p->initials() }}</span>
                                @endforeach
                                @if ($room->participants->count() < 2)
                                    <span class="grid size-12 place-items-center rounded-full border-2 border-dashed border-white/20 text-gray-500 ring-4 ring-ink-900">?</span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-white group-hover:text-brand-300">{{ $room->participants->pluck('name')->implode(' & ') }}</p>
                                <p class="mt-0.5 text-sm text-gray-400">
                                    {{ $room->results_count }} {{ \Illuminate\Support\Str::plural('match', $room->results_count) }}
                                    @if ($room->participants->count() < 2) · waiting for partner @endif
                                </p>
                            </div>
                            <x-icon name="chevron-right" class="size-5 text-gray-500 transition group-hover:translate-x-0.5 group-hover:text-white" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
