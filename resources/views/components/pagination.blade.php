{{-- <x-pagination :current="$currentPage" :total="$totalPages" route="movie.list" :params="['query' => $q]" /> --}}
@props(['current', 'total', 'route', 'params' => []])

@php
    $current = max(1, (int) $current);
    $total = max(1, (int) $total);
    $url = fn ($page) => route($route, array_merge($params, ['page' => $page]));
    $start = max(1, $current - 2);
    $end = min($total, $current + 2);
    $base = 'grid h-10 min-w-10 place-items-center rounded-xl px-3 text-sm font-semibold transition';
    $idle = $base . ' bg-white/5 text-gray-300 ring-1 ring-white/10 hover:bg-white/10 hover:text-white';
    $active = $base . ' bg-brand-600 text-white shadow-lg shadow-brand-900/40';
    $disabled = $base . ' bg-white/5 text-gray-600 ring-1 ring-white/5 cursor-not-allowed';
@endphp

@if ($total > 1)
    <nav class="mt-12 flex items-center justify-center gap-1.5" aria-label="Pagination">
        @if ($current > 1)
            <a href="{{ $url($current - 1) }}" class="{{ $idle }} gap-1" rel="prev" aria-label="Previous page">
                <span class="flex items-center gap-1"><x-icon name="chevron-left" class="size-4" /><span class="hidden sm:inline">Prev</span></span>
            </a>
        @else
            <span class="{{ $disabled }}" aria-hidden="true"><span class="flex items-center gap-1"><x-icon name="chevron-left" class="size-4" /><span class="hidden sm:inline">Prev</span></span></span>
        @endif

        <span class="px-3 text-sm text-gray-400 sm:hidden">Page <strong class="text-white">{{ $current }}</strong> of {{ number_format($total) }}</span>

        <div class="hidden items-center gap-1.5 sm:flex">
            @if ($start > 1)
                <a href="{{ $url(1) }}" class="{{ $idle }}">1</a>
                @if ($start > 2) <span class="px-1 text-gray-600">…</span> @endif
            @endif

            @for ($i = $start; $i <= $end; $i++)
                @if ($i === $current)
                    <span class="{{ $active }}" aria-current="page">{{ $i }}</span>
                @else
                    <a href="{{ $url($i) }}" class="{{ $idle }}">{{ $i }}</a>
                @endif
            @endfor

            @if ($end < $total)
                @if ($end < $total - 1) <span class="px-1 text-gray-600">…</span> @endif
                <a href="{{ $url($total) }}" class="{{ $idle }}">{{ number_format($total) }}</a>
            @endif
        </div>

        @if ($current < $total)
            <a href="{{ $url($current + 1) }}" class="{{ $idle }}" rel="next" aria-label="Next page">
                <span class="flex items-center gap-1"><span class="hidden sm:inline">Next</span><x-icon name="chevron-right" class="size-4" /></span>
            </a>
        @else
            <span class="{{ $disabled }}" aria-hidden="true"><span class="flex items-center gap-1"><span class="hidden sm:inline">Next</span><x-icon name="chevron-right" class="size-4" /></span></span>
        @endif
    </nav>
@endif
