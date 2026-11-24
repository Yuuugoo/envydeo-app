{{-- <x-avatar :user="$user" size="md" ring /> — photo if uploaded, otherwise initials. --}}
@props(['user', 'size' => 'md', 'ring' => false])

@php
    $sizes = ['xs' => 'size-7 text-[10px]', 'sm' => 'size-9 text-xs', 'md' => 'size-11 text-sm', 'lg' => 'size-16 text-lg', 'xl' => 'size-24 text-2xl sm:size-28'];
    $classes = ($sizes[$size] ?? $sizes['md']) . ' shrink-0 rounded-full object-cover ' . ($ring ? 'ring-2 ring-brand-500/70 ring-offset-2 ring-offset-ink-950' : 'ring-1 ring-white/10');
    $avatar = $user?->avatarUrl();
@endphp

@if ($avatar)
    <img src="{{ $avatar }}" alt="" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <span {{ $attributes->merge(['class' => $classes . ' grid place-items-center bg-gradient-to-br from-brand-600 to-brand-800 font-bold text-white']) }} aria-hidden="true">
        {{ $user?->initials() ?? '?' }}
    </span>
@endif
