{{-- <x-verdict value="loved" /> — Envydeo's verdict pill (😍 Loved it · 👍 Good · 😐 Meh · 👎 Skip it). --}}
@props(['value', 'size' => 'sm', 'friends' => false])

@php
    [$emoji, $label] = \App\Models\DiaryEntry::VERDICTS[$value] ?? [null, null];
    $tone = [
        'loved' => 'bg-rose-500/15 text-rose-200 ring-rose-400/30',
        'good' => 'bg-brand-500/15 text-brand-200 ring-brand-400/30',
        'meh' => 'bg-amber-500/10 text-amber-200 ring-amber-400/25',
        'skip' => 'bg-white/5 text-gray-300 ring-white/15',
    ][$value] ?? '';
    $sizes = $size === 'lg' ? 'gap-2 px-4 py-2 text-base' : 'gap-1.5 px-2.5 py-1 text-xs';
@endphp

@if ($emoji)
    <span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full font-bold ring-1 $tone $sizes"]) }}>
        <span aria-hidden="true">{{ $emoji }}</span>{{ $label }}
    </span>
@endif
@if ($friends)
    <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-500/10 px-2.5 py-1 text-xs font-bold text-sky-200 ring-1 ring-sky-400/25">
        <span aria-hidden="true">🍿</span>Best with friends
    </span>
@endif
