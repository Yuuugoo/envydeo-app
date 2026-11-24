<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative" wire:poll.30s.visible>
    <button type="button" @click="open = !open; if (open && {{ $unread }}) $wire.markAllRead()"
            class="relative grid size-10 place-items-center rounded-full text-gray-200 transition hover:bg-white/10 hover:text-white"
            :class="open && 'bg-white/10 text-white'" aria-label="Notifications{{ $unread ? ' (' . $unread . ' unread)' : '' }}" :aria-expanded="open">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($unread)
            <span class="absolute right-1 top-1 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-4 text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition
         class="fixed inset-x-3 top-[4.25rem] z-50 overflow-hidden rounded-2xl bg-ink-900/95 shadow-2xl shadow-black/50 ring-1 ring-white/10 backdrop-blur-xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-3 sm:w-[22rem]">
        <div class="flex items-center justify-between border-b border-white/5 px-4 py-3">
            <p class="text-sm font-bold text-white">Notifications</p>
            <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-brand-300 hover:text-brand-200">See all</a>
        </div>
        <div class="max-h-[60vh] overflow-y-auto p-1.5">
            @forelse ($latest as $n)
                @include('livewire.social.partials.notification-row', ['n' => $n])
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-500">No notifications yet.</p>
            @endforelse
        </div>
    </div>
</div>
