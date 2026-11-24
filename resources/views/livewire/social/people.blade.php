<div class="max-w-2xl">
    <div class="relative">
        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-gray-500" />
        <input type="search" wire:model.live.debounce.300ms="query" placeholder="Search by name or @username" aria-label="Search people" autofocus
               class="w-full rounded-2xl bg-ink-900 py-4 pl-12 pr-4 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>

    @php $people = $searching ? $results : $suggestions; @endphp
    <h2 class="mt-8 text-xs font-bold uppercase tracking-[0.18em] text-gray-500">{{ $searching ? 'Results' : 'Suggested for you' }}</h2>
    <ul class="mt-3 divide-y divide-white/5" wire:loading.class="opacity-50">
        @forelse ($people as $person)
            <li class="flex items-center gap-3 py-3" wire:key="person-{{ $person->id }}">
                <a href="{{ $person->profileUrl() }}"><x-avatar :user="$person" /></a>
                <a href="{{ $person->profileUrl() }}" class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-white">{{ $person->name }} @if ($person->is_private) <span class="text-xs" title="Private account">🔒</span> @endif</p>
                    <p class="truncate text-xs text-gray-400">
                        {{ '@' . $person->username }}
                        @if ($person->mutuals) · {{ $person->mutuals }} mutual {{ \Illuminate\Support\Str::plural('friend', $person->mutuals) }} @endif
                    </p>
                </a>
                <livewire:social.follow-button :user="$person" compact :menu="false" :key="'pf-' . $person->id" />
            </li>
        @empty
            <li class="py-10 text-center text-sm text-gray-500">{{ $searching ? 'No one found.' : 'No suggestions yet. Invite friends with your profile link!' }}</li>
        @endforelse
    </ul>
</div>
