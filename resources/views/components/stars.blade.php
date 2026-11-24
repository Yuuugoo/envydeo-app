{{-- <x-stars :value="7" /> — value is 1–10 (½-star steps), shown as 0.5–5 stars. --}}
@props(['value', 'size' => 'size-4'])

@php $stars = max(0, min(10, (int) $value)) / 2; @endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 text-amber-400']) }} aria-label="{{ rtrim(rtrim(number_format($stars, 1), '0'), '.') }} out of 5 stars">
    @for ($i = 1; $i <= 5; $i++)
        @if ($stars >= $i)
            <x-icon name="star-solid" class="{{ $size }}" />
        @elseif ($stars >= $i - 0.5)
            <span class="relative inline-block {{ $size }}">
                <x-icon name="star-solid" class="{{ $size }} absolute inset-0 text-white/15" />
                <span class="absolute inset-0 overflow-hidden" style="width: 50%"><x-icon name="star-solid" class="{{ $size }}" /></span>
            </span>
        @else
            <x-icon name="star-solid" class="{{ $size }} text-white/15" />
        @endif
    @endfor
</span>
