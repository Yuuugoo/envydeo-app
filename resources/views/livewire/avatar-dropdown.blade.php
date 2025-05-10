<div>
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="rounded-full avatar hover:ring hover:ring-success transition-all duration-300">
            <div class="w-10 rounded-full border-2 border-primary shadow-lg">
                <img
                    src="{{ Auth::user()->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) }}"
                    alt="{{ Auth::user()->name }}"
                />
            </div>
            <div class="ms-1 mt-3">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <ul tabindex="0" class="mt-3 z-[1] shadow-lg menu menu-sm dropdown-content bg-gray-800 rounded-lg w-64 border border-gray-700 transition-all duration-200 overflow-hidden">
            <li class="font-semibold text-sm py-6 text-gray-200 border-b flex items-center gap-3">
                <div class="w-full rounded-b-md">
                    <img class="h-10 w-10" src="{{ Auth::user()->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name) }}" alt="{{ Auth::user()->name }}" />
                    <span>{{ Auth::user()->name }}</span>
                </div>
            </li>
            <li><a href="{{ route('profile.edit') }}" class="hover:bg-gray-700 py-5 transition-colors duration-200 flex gap-2 items-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg> Profile</a></li>
            <form method="POST" action="{{ route('logout') }}">
                <li>
                    @csrf
                    <a href="{{ route('logout'  ) }}" onclick="event.preventDefault(); this.closest('form').submit();" class="hover:bg-gray-700 w-full py-5 transition-colors duration-200 flex gap-2 items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg> Log out
                    </a>
                </li>
            </form>
        </ul>
    </div>
</div>
