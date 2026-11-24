<article class="flex gap-4 rounded-2xl bg-ink-900/70 p-4 ring-1 ring-white/5">
    @if ($showTitle)
        <a href="{{ $entry->titleUrl() }}" class="shrink-0">
            <img src="https://image.tmdb.org/t/p/w154{{ $entry->movie['poster_path'] ?? '' }}" alt="" class="w-16 rounded-lg ring-1 ring-white/10">
        </a>
    @endif
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ $entry->user->profileUrl() }}" class="flex items-center gap-2">
                <x-avatar :user="$entry->user" size="xs" />
                <span class="font-bold text-white hover:text-brand-300">{{ $entry->user->name }}</span>
            </a>
            @if ($entry->rating) <x-stars :value="$entry->rating" size="size-3.5" /> @endif
        </div>
        @if ($showTitle)
            <a href="{{ $entry->titleUrl() }}" class="mt-1 block text-sm text-gray-400 hover:text-white">{{ $entry->movie['title'] ?? '' }} <span class="text-gray-500">{{ $entry->movie['year'] ?? '' }}</span></a>
        @endif

        @if ($entry->review)
            <div x-data="{ reveal: {{ $entry->has_spoilers ? 'false' : 'true' }} }" class="mt-2">
                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-200 transition" :class="!reveal && 'select-none blur-sm'">{{ $entry->review }}</p>
                @if ($entry->has_spoilers)
                    <button type="button" x-show="!reveal" @click="reveal = true" class="mt-1 text-xs font-semibold text-amber-300">⚠ Contains spoilers · tap to show</button>
                @endif
            </div>
        @endif

        <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
            <span>Watched {{ $entry->watched_on->format('M j, Y') }}</span>
            @if ($isOwner)
                <span>♥ {{ $entry->likes_count }}</span>
            @else
                <button type="button" wire:click="toggleLike" class="flex items-center gap-1 font-semibold transition {{ $liked ? 'text-rose-400' : 'hover:text-white' }}" aria-pressed="{{ $liked ? 'true' : 'false' }}">
                    {{ $liked ? '♥' : '♡' }} {{ $entry->likes_count ?: 'Like' }}
                </button>
                @auth
                    <button type="button" @click="$dispatch('open-report', { type: 'diary_entry', id: {{ $entry->id }} })" class="hover:text-white">Report</button>
                @endauth
            @endif
        </div>
    </div>
</article>
