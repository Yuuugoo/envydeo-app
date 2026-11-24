@php
    $story = $variant === 'story';
    $image = $checkIn->photoUrl() ?? 'https://image.tmdb.org/t/p/w780' . ($checkIn->movie['poster_path'] ?? '');
@endphp

<article class="{{ $story ? 'flex h-full flex-col' : 'overflow-hidden rounded-3xl bg-ink-900/70 ring-1 ring-white/5' }}">
    {{-- Header --}}
    <header class="flex items-center gap-3 {{ $story ? 'px-4 pb-3 pt-2' : 'px-4 py-3' }}">
        <a href="{{ $checkIn->user->profileUrl() }}"><x-avatar :user="$checkIn->user" size="sm" /></a>
        <div class="min-w-0 flex-1 text-sm">
            <a href="{{ $checkIn->user->profileUrl() }}" class="font-bold text-white hover:text-brand-300">{{ $isOwner ? 'You' : $checkIn->user->name }}</a>
            <span class="text-gray-400">{{ $isOwner ? 'are' : 'is' }} watching</span>
            <p class="text-xs text-gray-500">{{ $checkIn->created_at->diffForHumans() }} @if ($checkIn->isLive()) · <span class="font-semibold text-rose-300">Tonight</span> @endif</p>
        </div>
        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = !open" class="grid size-8 place-items-center rounded-full text-gray-400 hover:bg-white/10 hover:text-white" aria-label="Check-in options">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-30 mt-1 w-44 rounded-xl bg-ink-850 p-1.5 shadow-2xl ring-1 ring-white/10">
                @if ($isOwner)
                    <button type="button" wire:click="delete" wire:confirm="Delete this check-in?" class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-300 hover:bg-white/5">Delete</button>
                @else
                    <button type="button" @click="open = false; $dispatch('open-report', { type: 'check_in', id: {{ $checkIn->id }} })" class="w-full rounded-lg px-3 py-2 text-left text-sm text-gray-200 hover:bg-white/5">Report</button>
                @endif
            </div>
        </div>
    </header>

    {{-- Photo / poster --}}
    <div class="relative {{ $story ? 'min-h-0 flex-1' : '' }} bg-black">
        <img src="{{ $image }}" alt="{{ $checkIn->photo_path ? 'Photo by ' . $checkIn->user->name : ($checkIn->movie['title'] ?? '') }}"
             class="{{ $story ? 'size-full object-contain' : 'max-h-[34rem] w-full object-cover' }}" loading="lazy">
        <a href="{{ $checkIn->titleUrl() }}" class="absolute bottom-3 left-3 right-3 flex items-center gap-3 rounded-2xl bg-black/60 p-2 pr-3 backdrop-blur-md transition hover:bg-black/75">
            <img src="https://image.tmdb.org/t/p/w92{{ $checkIn->movie['poster_path'] ?? '' }}" alt="" class="w-9 rounded-md">
            <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-white">{{ $checkIn->movie['title'] ?? '' }}</span>
                <span class="text-[11px] text-gray-300">{{ ($checkIn->movie['type'] ?? 'movie') === 'tv' ? 'TV' : 'Movie' }} · {{ $checkIn->movie['year'] ?? '' }}</span>
            </span>
        </a>
    </div>

    @if ($checkIn->caption)
        <p class="px-4 pt-3 text-sm leading-relaxed text-gray-200">{{ $checkIn->caption }}</p>
    @endif

    {{-- Reactions --}}
    <footer class="px-4 py-3">
        @if ($isOwner)
            @if ($checkIn->reactions->isNotEmpty())
                <ul class="flex flex-wrap gap-2">
                    @foreach ($checkIn->reactions as $reaction)
                        <li class="flex items-center gap-1.5 rounded-full bg-white/5 py-1 pl-1 pr-2.5 text-xs text-gray-300 ring-1 ring-white/10">
                            <x-avatar :user="$reaction->user" size="xs" /> {{ $reaction->emoji() }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-xs text-gray-500">No reactions yet. Your friends will see this for 24 hours.</p>
            @endif
        @else
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach (\App\Models\CheckInReaction::REACTIONS as $key => $emoji)
                    @continue($key === 'in')
                    <button type="button" wire:click="react('{{ $key }}')"
                            class="flex items-center gap-1 rounded-full px-2.5 py-1.5 text-base ring-1 transition active:scale-90 {{ $mine === $key ? 'bg-brand-600/25 ring-brand-400' : 'bg-white/5 ring-white/10 hover:bg-white/10' }}"
                            aria-pressed="{{ $mine === $key ? 'true' : 'false' }}" aria-label="React {{ $emoji }}">
                        {{ $emoji }} @if ($counts[$key] ?? 0) <span class="text-xs font-semibold text-gray-300">{{ $counts[$key] }}</span> @endif
                    </button>
                @endforeach
                <button type="button" wire:click="react('in')"
                        class="ml-auto rounded-full px-3.5 py-1.5 text-xs font-bold ring-1 transition {{ $mine === 'in' ? 'bg-brand-600 text-white ring-brand-500' : 'bg-white/5 text-gray-200 ring-white/10 hover:bg-white/10' }}">
                    🙋 {{ $mine === 'in' ? 'You’re in' : 'I’m in' }}
                </button>
            </div>
        @endif
    </footer>
</article>
