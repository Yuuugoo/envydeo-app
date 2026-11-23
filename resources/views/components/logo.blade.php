@props(['wordmark' => true, 'size' => 'md'])

@php
    $markSize = ['sm' => 'size-7', 'md' => 'size-9', 'lg' => 'size-12'][$size] ?? 'size-9';
    $textSize = ['sm' => 'text-lg', 'md' => 'text-xl md:text-[1.4rem]', 'lg' => 'text-3xl'][$size] ?? 'text-xl';
    $gid = 'logo-g-' . \Illuminate\Support\Str::random(6);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    {{-- Mark: an eye whose pupil is a play button --}}
    <svg class="{{ $markSize }} shrink-0 drop-shadow-[0_0_14px_rgba(98,195,116,0.35)]" viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $gid }}" x1="4" y1="8" x2="36" y2="32" gradientUnits="userSpaceOnUse">
                <stop stop-color="#8AD99A" />
                <stop offset="0.55" stop-color="#4CAF62" />
                <stop offset="1" stop-color="#2F8442" />
            </linearGradient>
        </defs>
        <path d="M3.5 20C8.2 11.6 13.7 7.8 20 7.8S31.8 11.6 36.5 20C31.8 28.4 26.3 32.2 20 32.2S8.2 28.4 3.5 20Z"
              stroke="url(#{{ $gid }})" stroke-width="3.2" stroke-linejoin="round" />
        <path d="M16.6 14.4c0-1.1 1.2-1.8 2.2-1.2l7.4 4.6a1.4 1.4 0 0 1 0 2.4l-7.4 4.6c-1 .6-2.2-.1-2.2-1.2v-9.2Z"
              fill="url(#{{ $gid }})" />
    </svg>

    @if ($wordmark)
        <span class="{{ $textSize }} font-extrabold leading-none tracking-tight text-white">
            envy<span class="bg-gradient-to-br from-brand-300 to-brand-500 bg-clip-text text-transparent">deo</span>
        </span>
    @endif
</span>
