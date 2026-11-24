{{-- Social UI shared by every layout (signed-in users with a profile). --}}
@if (auth()->user()?->username)
    @php
        $me = auth()->user();
        $tabs = [
            ['label' => 'Home', 'icon' => 'home', 'href' => route('movie.index'), 'active' => request()->routeIs('movie.index')],
            ['label' => 'Feed', 'icon' => 'users', 'href' => route('feed'), 'active' => request()->routeIs('feed', 'people')],
            null, // centre "+" (check in)
            ['label' => 'Movie Night', 'icon' => 'sparkles', 'href' => route('movie-night.index'), 'active' => request()->routeIs('movie-night.*')],
            ['label' => 'Profile', 'icon' => 'user', 'href' => route('profile.show', $me->username), 'active' => request()->routeIs('profile.show') && request()->route('username') === $me->username],
        ];
    @endphp

    {{-- Bottom tab bar (phones/tablets): feels like a native app, especially when installed --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink-950/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden" aria-label="App">
        <ul class="mx-auto grid max-w-md grid-cols-5">
            @foreach ($tabs as $tab)
                <li>
                    @if ($tab === null)
                        <button type="button" x-data @click="$dispatch('open-check-in')" class="flex h-16 w-full items-center justify-center" aria-label="Check in: what are you watching tonight?">
                            <span class="grid size-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-2xl font-bold text-white shadow-lg shadow-brand-900/50 transition active:scale-95">+</span>
                        </button>
                    @else
                        <a href="{{ $tab['href'] }}" @if ($tab['active']) aria-current="page" @endif
                           class="flex h-16 flex-col items-center justify-center gap-1 text-[10px] font-semibold transition {{ $tab['active'] ? 'text-brand-300' : 'text-gray-400 hover:text-white' }}">
                            @if ($tab['label'] === 'Profile')
                                <x-avatar :user="$me" size="xs" class="{{ $tab['active'] ? '!ring-2 !ring-brand-400' : '' }}" />
                            @else
                                <x-icon :name="$tab['icon']" class="size-6" />
                            @endif
                            {{ $tab['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
    <div class="h-16 lg:hidden" aria-hidden="true"></div>{{-- keeps the footer clear of the tab bar --}}

    <livewire:social.check-in-composer />
    <livewire:social.report-modal />
@endif
