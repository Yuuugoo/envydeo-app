@php
    $navLinks = [
        ['label' => 'Home', 'icon' => 'home', 'href' => route('movie.index'), 'active' => request()->routeIs('movie.index')],
        ...(auth()->check() ? [['label' => 'Feed', 'icon' => 'users', 'href' => route('feed'), 'active' => request()->routeIs('feed', 'people', 'profile.show', 'profile.connections')]] : []),
        ['label' => 'Movies', 'icon' => 'film', 'href' => route('movie.list'), 'active' => request()->routeIs('movie.list', 'movie.show')],
        ['label' => 'TV Shows', 'icon' => 'tv', 'href' => route('show.list'), 'active' => request()->routeIs('show.*')],
        ['label' => 'Movie Night', 'icon' => 'sparkles', 'href' => route('movie-night.index'), 'active' => request()->routeIs('movie-night.*')],
    ];
    if (auth()->check()) {
        $navLinks[] = ['label' => 'Watchlist', 'icon' => 'bookmark', 'href' => route('watchlist.index'), 'active' => request()->routeIs('watchlist.*')];
    }
    $user = auth()->user();
    $avatar = $user?->getFirstMediaUrl('avatar');
    $initials = $user ? collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') : '';
@endphp

<header
    x-data="{ scrolled: false, open: false }"
    x-init="scrolled = window.scrollY > 10"
    @scroll.window.passive="scrolled = window.scrollY > 10"
    @keydown.escape.window="open = false"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
    class="fixed inset-x-0 top-0 z-50"
    data-app-header
>
    {{--
        The bar's background/blur lives on its own layer, NOT on <header>. A backdrop-filter on an element
        makes it the containing block for position:fixed descendants, so the mobile drawer and search panel
        would shrink to the 64px bar as soon as the page is scrolled.
    --}}
    <div aria-hidden="true"
         class="pointer-events-none absolute inset-0 -z-10 transition-[background-color,box-shadow] duration-300"
         :class="scrolled ? 'bg-ink-950/85 shadow-[0_1px_0_rgba(255,255,255,0.06)] backdrop-blur-xl' : 'bg-gradient-to-b from-ink-950/90 via-ink-950/40 to-transparent'"></div>

    <nav class="shell flex h-16 items-center gap-3 md:h-[4.5rem]" aria-label="Main">
        <a href="{{ route('movie.index') }}" class="shrink-0 rounded-lg transition hover:opacity-90" aria-label="Envydeo home">
            <x-logo />
        </a>

        {{-- Desktop links --}}
        <ul class="ml-8 hidden items-center gap-1 lg:flex">
            @foreach ($navLinks as $link)
                <li>
                    <a href="{{ $link['href'] }}"
                       @if ($link['active']) aria-current="page" @endif
                       class="relative rounded-full px-4 py-2 text-sm font-semibold transition
                              {{ $link['active'] ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        {{ $link['label'] }}
                        @if ($link['active'])
                            <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-brand-400"></span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            <livewire:search-dropdown />
            @if (auth()->user()?->username)
                <livewire:social.notification-bell />
            @endif

            @auth
                <div class="hidden lg:block">
                    <livewire:avatar-dropdown />
                </div>
            @else
                <div class="hidden items-center gap-2 lg:flex">
                    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-gray-200 transition hover:bg-white/5 hover:text-white">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-900/40 transition hover:bg-brand-500">Create account</a>
                </div>
            @endauth

            <button type="button" @click="open = true"
                    class="grid size-10 place-items-center rounded-full text-gray-200 transition hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Open menu" :aria-expanded="open">
                <x-icon name="menu" class="size-6" />
            </button>
        </div>
    </nav>

    {{-- Mobile / tablet drawer --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div x-show="open" x-transition.opacity.duration.200ms @click="open = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div x-show="open"
             x-transition:enter="transition ease-out-soft duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute inset-y-0 right-0 flex w-[min(20rem,86vw)] flex-col bg-ink-900 shadow-2xl ring-1 ring-white/10">
            <div class="flex h-16 items-center justify-between px-5">
                <x-logo size="sm" />
                <button type="button" @click="open = false" class="grid size-10 place-items-center rounded-full text-gray-300 hover:bg-white/10 hover:text-white" aria-label="Close menu">
                    <x-icon name="x" class="size-6" />
                </button>
            </div>

            @auth
                <div class="mx-4 mb-2 flex items-center gap-3 rounded-2xl bg-white/5 p-3">
                    @if ($avatar)
                        <img src="{{ $avatar }}" alt="" class="size-11 rounded-full object-cover ring-2 ring-brand-500/60">
                    @else
                        <span class="grid size-11 place-items-center rounded-full bg-brand-700 font-bold text-white ring-2 ring-brand-500/60">{{ $initials }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-white">{{ $user->name }}</p>
                        <p class="truncate text-xs text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
            @endauth

            <ul class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
                @foreach ($navLinks as $link)
                    <li>
                        <a href="{{ $link['href'] }}" @if ($link['active']) aria-current="page" @endif
                           class="flex items-center gap-3 rounded-xl px-3 py-3 text-base font-semibold transition
                                  {{ $link['active'] ? 'bg-brand-600/15 text-brand-300' : 'text-gray-200 hover:bg-white/5' }}">
                            <x-icon :name="$link['icon']" class="size-5" />
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
                @auth
                    <li>
                        <a href="{{ route('movie-night.us') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-base font-semibold transition {{ request()->routeIs('movie-night.us') ? 'bg-brand-600/15 text-brand-300' : 'text-gray-200 hover:bg-white/5' }}">
                            <x-icon name="heart" class="size-5" />
                            “Us” rooms
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-base font-semibold transition {{ request()->routeIs('profile.*') ? 'bg-brand-600/15 text-brand-300' : 'text-gray-200 hover:bg-white/5' }}">
                            <x-icon name="user" class="size-5" />
                            Profile
                        </a>
                    </li>
                @endauth
            </ul>

            <div class="border-t border-white/10 p-4">
                <div x-data="installApp()" x-show="available" x-cloak class="mb-3">
                    <button type="button" @click="open = false; install()"
                            class="flex w-full items-center gap-3 rounded-xl bg-brand-600/15 px-3 py-3 text-left font-semibold text-brand-200 ring-1 ring-brand-500/30 transition hover:bg-brand-600/25">
                        <x-icon name="phone" class="size-5" />
                        <span class="flex-1">Install the Envydeo app</span>
                    </button>
                </div>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-white/5 px-4 py-3 font-semibold text-gray-200 transition hover:bg-white/10">
                            <x-icon name="logout" class="size-5" />
                            Log out
                        </button>
                    </form>
                @else
                    <div class="grid gap-2">
                        <a href="{{ route('register') }}" class="btn-brand w-full">Create account</a>
                        <a href="{{ route('login') }}" class="btn-glass w-full">Sign in</a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>
