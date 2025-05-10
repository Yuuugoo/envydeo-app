<nav class="bg-transparent w-full z-[100] md:absolute md:top-0 lg:left-28 md:max-w-screen-xl">
    <div class="container flex flex-row items-center justify-between px-4 py-6 md:max-w-7xl xl:max-w-7xl mx-auto">
        <!-- Drawer for mobile -->
        <div class="xl:hidden">
            <label for="my-drawer" class="drawer-button text-white cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </label>
        </div>
        
        <!-- Logo - hidden on small screens, visible on md and up -->
        <div class="hidden xl:block">
            <a href="{{ route('movie.index') }}" class="flex items-center space-x-2 hover:opacity-90 transition-opacity">
                <img src="/img/logo/envydeo_logo_bg_removed.png"
                    alt="envydeo-logo"
                    class="rounded-md w-12 h-12 p-1" />
                <span class="text-white text-xl font-bold tracking-wide">envydeo</span>
            </a>
        </div>
        
        <!-- Menu - hidden on small screens, visible on md and up -->
        <ul class="hidden xl:flex xl:flex-row xl:items-center">
            <li class="xl:ml-16">
                <a href="{{ route('movie.index') }}" class="hover:text-green-300">Home</a>
            </li>
            <li class="xl:ml-6">
                <a href="{{ route('movie.list') }}" class="hover:text-green-300">Movies</a>
            </li>
            <li class="xl:ml-6">
                <a href="{{ route('show.list') }}" class="hover:text-green-300">TV Shows</a>
            </li>
        </ul>
        
        <!-- Search and auth buttons -->
        <div class="flex flex-row items-center ml-auto xl:ml-0">
            <div class="xl:mr-5">
                <livewire:search-dropdown/>
            </div>
            <div class="hidden md:flex md:gap-10 lg:gap-6 xl:space-x-2">
                @if (Route::has('login'))
                    <div class="flex space-x-2">
                    @auth
                        <livewire:avatar-dropdown/>
                    @else
                        <a href="{{ route('login') }}" class="btn gap-2 bg-green-600 hover:bg-green-800 text-white px-4 py-1 rounded-box font-semibold">Login</a>
                        @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn gap-2 bg-white/10 hover:bg-white/20 text-white px-4 py-1 rounded-box">Sign Up</a>
                        @endif
                    @endauth
                    </div>
                @endif
            </div>
        </div>
    </div>
</nav>

<!-- Drawer component -->
<div class="drawer xl:hidden">
    <input id="my-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-side z-[200]">
        <label for="my-drawer" class="drawer-overlay"></label>
        <ul class="menu p-4 w-64 h-full bg-gray-800 text-white space-y-2">
            <li>
                <a href="{{ route('movie.index') }}" class="flex items-center space-x-2 hover:opacity-90 transition-opacity mb-6">
                    <img src="/img/logo/envydeo_logo_bg_removed.png"
                        alt="envydeo-logo"
                        class="rounded-md w-10 h-10 p-1" />
                    <span class="text-xl font-bold tracking-wide">envydeo</span>
                </a>
            </li>
            <li><a class="text-base font-semibold" href="{{ route('movie.index') }}">Home</a></li>
            <li><a class="text-base font-semibold" href="{{ route('movie.list') }}">Movies</a></li>
            <li><a class="text-base font-semibold" href="{{ route('show.list') }}">TV Shows</a></li>
            <div class="mt-4 md:hidden">
                @if (Route::has('login'))
                    <div class="flex flex-col space-y-2">
                        @auth
                            <li class="border-t border-gray-700 pt-2 mt-2">
                                <div class="flex items-center px-2 py-1">
                                <img
                                    class="w-8 h-8 rounded-full"
                                    src="{{ Auth::user()->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) }}"
                                    alt="{{ Auth::user()->name }}"
                                />
                                    <span class="ml-2 font-medium">{{ Auth::user()->name }}</span>
                                </div>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}" class="block ml-3 px-2 py-1 text-base font-semibold">
                                    Profile
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left text-base font-semibold px-2 py-1">
                                        Logout
                                    </button>
                                </form>
                            </li>
                        @else
                            <a href="{{ route('login') }}" class="btn gap-2 bg-green-600 hover:bg-green-800 text-white px-4 py-1 rounded-box font-semibold">Login</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn gap-2 bg-white/10 hover:bg-white/20 text-white px-4 py-1 rounded-box">Sign Up</a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </ul>
    </div>
</div>