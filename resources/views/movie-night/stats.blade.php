@extends('layouts.main')

@section('title', 'Movie Night stats')

@php
    $maxFunnel = max(1, collect($funnel)->max('value'));
    $maxDaily = max(1, collect($daily)->max(fn ($d) => max($d['created'], $d['matched'])));
@endphp

@section('content')
<div class="shell page-top">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Internal · admins only</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-4xl">Movie Night stats</h1>
            <p class="mt-2 text-sm text-gray-400">Anonymous events only: no names, emails or IP addresses are stored.</p>
        </div>
        <nav class="flex gap-1 rounded-full bg-white/5 p-1 ring-1 ring-white/10" aria-label="Date range">
            @foreach ($ranges as $value => $label)
                <a href="{{ route('movie-night.stats', ['days' => $value]) }}" @if ($days === $value) aria-current="page" @endif
                   class="rounded-full px-3.5 py-1.5 text-sm font-semibold transition {{ $days === $value ? 'bg-brand-600 text-white' : 'text-gray-300 hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    {{-- KPIs --}}
    <div class="mt-10 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <div class="rounded-2xl bg-ink-900/70 p-5 ring-1 ring-white/5">
                <p class="text-sm text-gray-400">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold tabular-nums text-white">{{ $kpi['value'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $kpi['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Funnel --}}
        <section class="rounded-2xl bg-ink-900/70 p-6 ring-1 ring-white/5">
            <h2 class="section-title">Funnel</h2>
            <ul class="mt-5 space-y-4">
                @foreach ($funnel as $step)
                    <li>
                        <div class="flex items-baseline justify-between text-sm"><span class="text-gray-300">{{ $step['label'] }}</span><span class="font-bold tabular-nums text-white">{{ number_format($step['value']) }}</span></div>
                        <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-white/5"><div class="h-full rounded-full bg-brand-500" style="width: {{ round($step['value'] / $maxFunnel * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Daily --}}
        <section class="rounded-2xl bg-ink-900/70 p-6 ring-1 ring-white/5">
            <div class="flex items-center justify-between">
                <h2 class="section-title">Last {{ count($daily) }} days</h2>
                <p class="flex items-center gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-brand-500"></span>Rooms</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-sky-400"></span>Matches</span>
                </p>
            </div>
            <div class="mt-6 flex h-40 items-end gap-1.5">
                @foreach ($daily as $day)
                    <div class="flex h-full flex-1 flex-col justify-end gap-0.5" title="{{ $day['date']->format('M j') }}: {{ $day['created'] }} rooms, {{ $day['matched'] }} matches">
                        <div class="flex flex-1 items-end gap-0.5">
                            <div class="flex-1 rounded-t bg-brand-500" style="height: {{ round($day['created'] / $maxDaily * 100) }}%"></div>
                            <div class="flex-1 rounded-t bg-sky-400" style="height: {{ round($day['matched'] / $maxDaily * 100) }}%"></div>
                        </div>
                        <span class="text-center text-[10px] text-gray-500">{{ $day['date']->format('j') }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Breakdowns --}}
    <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            'Room types' => $kinds,
            'Deck sources' => $sources,
            'Invites shared' => $shares['invites'],
            'Match cards shared' => $shares['cards'],
        ] as $heading => $rows)
            <section class="rounded-2xl bg-ink-900/70 p-6 ring-1 ring-white/5">
                <h2 class="text-sm font-bold text-white">{{ $heading }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($rows as $row)
                        <li class="flex justify-between"><span class="text-gray-400">{{ $row['label'] }}</span><span class="font-semibold tabular-nums text-white">{{ $row['value'] }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <section class="mt-6 rounded-2xl bg-ink-900/70 p-6 ring-1 ring-white/5">
        <h2 class="text-sm font-bold text-white">Themes</h2>
        <ul class="mt-4 grid gap-x-8 gap-y-2.5 text-sm sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($themes as $row)
                <li class="flex justify-between"><span class="text-gray-400">{{ $row['label'] }}</span><span class="font-semibold tabular-nums text-white">{{ $row['value'] }}</span></li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
