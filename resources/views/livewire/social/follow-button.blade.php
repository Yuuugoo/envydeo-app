@php
    $size = $compact ? '!px-3.5 !py-1.5 text-xs' : '!px-5 !py-2 text-sm';
@endphp
<div class="flex items-center gap-2">
    @if ($r['self'])
        <a href="{{ route('profile.edit') }}" class="btn-glass {{ $size }}">Edit profile</a>
    @elseif ($r['blocked'])
        <button type="button" wire:click="unblock" class="btn-glass {{ $size }}">Unblock</button>
    @elseif ($r['following'])
        <div x-data="{ confirm: false }" class="relative">
            <button type="button" @click="confirm = !confirm" class="btn-glass {{ $size }}">
                <x-icon name="check" class="size-4" /> {{ $r['friends'] ? 'Friends' : 'Following' }}
            </button>
            <div x-show="confirm" x-cloak @click.outside="confirm = false" x-transition
                 class="absolute right-0 z-30 mt-2 w-44 rounded-xl bg-ink-850 p-1.5 shadow-2xl ring-1 ring-white/10">
                <button type="button" wire:click="unfollow" @click="confirm = false" class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-300 hover:bg-white/5">Unfollow</button>
            </div>
        </div>
    @elseif ($r['requested'])
        <button type="button" wire:click="unfollow" class="btn-glass {{ $size }}" title="Cancel request">Requested</button>
    @else
        <button type="button" wire:click="follow" wire:loading.attr="disabled" class="btn-brand {{ $size }}">
            {{ $r['follows_you'] ? 'Follow back' : 'Follow' }}
        </button>
    @endif

    @if ($menu && !$r['self'] && auth()->check())
        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = !open" class="grid size-9 place-items-center rounded-full text-gray-400 ring-1 ring-white/10 transition hover:bg-white/5 hover:text-white" aria-label="More options">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" x-transition
                 class="absolute right-0 z-30 mt-2 w-48 rounded-xl bg-ink-850 p-1.5 shadow-2xl ring-1 ring-white/10">
                <button type="button" @click="open = false; $dispatch('open-report', { type: 'user', id: @js($target->id) })"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm text-gray-200 hover:bg-white/5">Report {{ '@' . $target->username }}</button>
                @unless ($r['blocked'])
                    <button type="button" wire:click="block" wire:confirm="Block {{ '@' . $target->username }}? You’ll unfollow each other and they won’t be able to see your profile."
                            class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-300 hover:bg-white/5">Block</button>
                @endunless
            </div>
        </div>
    @endif
</div>
