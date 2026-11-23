@php
    $user = Auth::user();
    $avatar = $user->getFirstMediaUrl('avatar');
    $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
    <button type="button" @click="open = !open"
            class="flex items-center gap-1.5 rounded-full p-1 pr-2 transition hover:bg-white/10"
            :aria-expanded="open" aria-haspopup="menu" aria-label="Account menu">
        @if ($avatar)
            <img src="{{ $avatar }}" alt="" class="size-9 rounded-full object-cover ring-2 ring-brand-500/70">
        @else
            <span class="grid size-9 place-items-center rounded-full bg-brand-700 text-sm font-bold text-white ring-2 ring-brand-500/70">{{ $initials }}</span>
        @endif
        <x-icon name="chevron-down" class="size-4 text-gray-400 transition" x-bind:class="open && 'rotate-180'" />
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute right-0 mt-3 w-64 origin-top-right overflow-hidden rounded-2xl bg-ink-900/95 shadow-2xl shadow-black/50 ring-1 ring-white/10 backdrop-blur-xl"
         role="menu">
        <div class="border-b border-white/5 px-4 py-3">
            <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
            <p class="truncate text-xs text-gray-400">{{ $user->email }}</p>
        </div>
        <div class="p-1.5">
            <a href="{{ route('profile.edit') }}" role="menuitem" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/5 hover:text-white">
                <x-icon name="user" class="size-5 text-gray-400" /> Profile
            </a>
            <a href="{{ route('watchlist.index') }}" role="menuitem" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/5 hover:text-white">
                <x-icon name="bookmark" class="size-5 text-gray-400" /> Watchlist
            </a>
            <a href="{{ route('movie-night.us') }}" role="menuitem" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/5 hover:text-white">
                <x-icon name="heart" class="size-5 text-gray-400" /> “Us” rooms
            </a>
            @can('view-movie-night-stats')
                <a href="{{ route('movie-night.stats') }}" role="menuitem" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/5 hover:text-white">
                    <x-icon name="adjustments" class="size-5 text-gray-400" /> Movie Night stats
                </a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/5 hover:text-white">
                    <x-icon name="logout" class="size-5 text-gray-400" /> Log out
                </button>
            </form>
        </div>
    </div>
</div>
