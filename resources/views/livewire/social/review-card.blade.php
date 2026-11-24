{{-- A "take": someone's verdict on something they watched, with a "Same" (agree) button. --}}
<article class="rounded-3xl bg-ink-900/70 p-4 ring-1 ring-white/5">
    <div class="flex items-start gap-3">
        <a href="{{ $entry->user->profileUrl() }}" class="shrink-0"><x-avatar :user="$entry->user" size="sm" /></a>
        <div class="min-w-0 flex-1">
            <p class="text-sm leading-snug text-gray-300">
                <a href="{{ $entry->user->profileUrl() }}" class="font-bold text-white hover:text-brand-300">{{ $entry->user->is(auth()->user()) ? 'You' : $entry->user->name }}</a>
                watched
                @if ($showTitle)
                    <a href="{{ $entry->titleUrl() }}" class="font-semibold text-white hover:text-brand-300">{{ $entry->movie['title'] ?? '' }}</a>
                @else
                    this
                @endif
                {{ $entry->watchedWhen() }}
            </p>
            @if ($entry->verdict || $entry->best_with_friends)
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if ($entry->verdict) <x-verdict :value="$entry->verdict" /> @endif
                    @if ($entry->best_with_friends) <x-verdict value="" friends /> @endif
                </div>
            @endif
        </div>
        @if ($showTitle && !empty($entry->movie['poster_path']))
            <a href="{{ $entry->titleUrl() }}" class="shrink-0">
                <img src="https://image.tmdb.org/t/p/w154{{ $entry->movie['poster_path'] }}" alt="{{ $entry->movie['title'] ?? '' }}" class="w-12 rounded-lg ring-1 ring-white/10">
            </a>
        @endif
    </div>

    @if ($entry->review)
        <div x-data="{ reveal: {{ $entry->has_spoilers ? 'false' : 'true' }} }" class="relative mt-3 rounded-2xl rounded-tl-md bg-white/[0.04] px-4 py-3">
            <p class="whitespace-pre-line text-[15px] leading-relaxed text-gray-100 transition" :class="!reveal && 'select-none blur-sm'">{{ $entry->review }}</p>
            @if ($entry->has_spoilers)
                <button type="button" x-show="!reveal" @click="reveal = true" class="absolute inset-0 grid place-items-center text-xs font-bold text-amber-300">⚠ Spoilers · tap to show</button>
            @endif
        </div>
    @endif

    <div class="mt-3 flex items-center gap-2 text-xs">
        @if ($isOwner)
            <span class="rounded-full bg-white/5 px-3 py-1.5 font-semibold text-gray-400">🤝 {{ $entry->likes_count }} {{ $entry->likes_count === 1 ? 'person agrees' : 'people agree' }}</span>
        @else
            <button type="button" wire:click="toggleLike" aria-pressed="{{ $liked ? 'true' : 'false' }}"
                    class="flex items-center gap-1.5 rounded-full px-3 py-1.5 font-bold ring-1 transition {{ $liked ? 'bg-brand-600/20 text-brand-200 ring-brand-400/40' : 'bg-white/5 text-gray-300 ring-white/10 hover:bg-white/10' }}">
                🤝 Same{{ $entry->likes_count ? ' · ' . $entry->likes_count : '' }}
            </button>
            @auth
                <button type="button" @click="$dispatch('open-report', { type: 'diary_entry', id: {{ $entry->id }} })" class="ml-auto text-gray-500 hover:text-white">Report</button>
            @endauth
        @endif
    </div>
</article>
