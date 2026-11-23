<div>
    <div class="relative md:mt-0" x-data="{ isOpen: false, inputExpanded: false }">
        <div class="flex items-center">
            <div 
                x-show="!inputExpanded" 
                @click="inputExpanded = true; $nextTick(() => $refs.searchInput.focus())"
                class="cursor-pointer p-2 rounded-full hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <div 
                x-show="inputExpanded" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-x-8"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                class="absolute right-0 w-64"
                @click.away="inputExpanded = false; isOpen = false"
            >
                <form wire:submit.prevent="searchAll">
                    <div class="relative">
                        <input
                            x-ref="searchInput"
                            wire:model.live.debounce.400ms="search"
                            type="text"
                            class="bg-gray-800 rounded-full w-full px-4 pl-10 py-2 focus:outline-none focus:shadow-outline text-sm"
                            placeholder="Search Movies & TV Shows"
                            @focus="isOpen = true"
                            @keydown="isOpen = true"
                            @keydown.escape.window="inputExpanded = false; isOpen = false"
                        >
                        
                        <div class="absolute top-2.5 left-3">
                            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>

                        <div wire:loading class="absolute right-3 top-1">
                            <div class="loading loading-dots"></div>
                        </div>
                    </div>
                </form>

                @if(strlen($search) >= 2)
                    <div class="absolute bg-gray-800 text-sm rounded w-full mt-2 z-50 max-h-64 md:max-h-96 overflow-y-auto" 
                        x-show.transition.opacity="isOpen"
                        @keydown.escape="isOpen = false"
                    >
                        @if($searchResults->count() > 0)
                            <ul>
                                @foreach($searchResults as $result)
                                    @php
                                        $isMovie = $result['media_type'] === 'movie';
                                        $title = $isMovie ? $result['title'] : $result['name'];
                                        $routeName = $isMovie ? 'movie.show' : 'show.show';
                                        $releaseDate = $isMovie 
                                            ? ($result['release_date'] ?? 'N/A')
                                            : ($result['first_air_date'] ?? 'N/A');
                                        $year = substr($releaseDate, 0, 4);
                                    @endphp

                                    <li class="border-b border-gray-700">
                                        <a 
                                            href="{{ route($routeName, $result['id']) }}"
                                            class="flex items-center hover:bg-gray-700 px-3 py-2 md:py-3 transition ease-in-out duration-150"
                                            @if ($loop->last) @keydown.tab="isOpen = false" @endif
                                        >
                                            @if($result['poster_path'])
                                                <img src="https://image.tmdb.org/t/p/w92/{{ $result['poster_path'] }}"
                                                    alt="poster" class="w-8 h-12 object-cover rounded shadow">
                                            @else
                                                <div class="w-8 h-12 bg-gray-700 rounded shadow flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif

                                            <div class="ml-4 flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="line-clamp-1 font-medium">{{ $title }}</span>
                                                    @if($year !== 'N/A')
                                                        <span class="text-xs text-gray-400">({{ $year }})</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs px-1.5 py-0.5 rounded bg-gray-900 {{ $isMovie }}">
                                                        {{ strtoupper($result['media_type']) }}
                                                    </span>
                                                    <span class="text-xs text-yellow-500">
                                                        ★ {{ number_format($result['vote_average'], 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            @if($searchResults->count() >= 4)
                                <div class="px-3 py-3 text-center border-t border-gray-700">
                                    <a 
                                        href="{{ route('search.results', ['query' => $search]) }}" 
                                        class="text-blue-400 hover:text-blue-300 transition"
                                    >
                                        View all results for "{{ $search }}"
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="px-3 py-3">No results for "{{ $search }}"</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>