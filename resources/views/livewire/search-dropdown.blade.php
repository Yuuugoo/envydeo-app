<div> 
    <div class="relative mt-3 md:mt-0 w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg">
        <input
            wire:model.live.debounce.500ms="search"
            type="text"
            class="bg-gray-800 rounded-full w-full px-4 pl-10 py-2 focus:outline-none focus:shadow-outline text-sm"
            placeholder="Search"
        >
        
        <div class="absolute top-2.5 left-3">
            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>

        <div wire:loading class="absolute right-3 top-1">
            <div class="loading loading-dots"></div>
        </div>

        @if(strlen($search) >= 2)
            <div class="absolute bg-gray-800 text-md rounded w-full mt-2 z-50 max-h-96 overflow-y-auto">
                @if($searchResults->count() > 0)
                    <ul>
                        <!-- I skipped results that dont have title and poster paths 
                        because most probably they are also not yet available for movie streaming -->
                        @foreach($searchResults as $result)
                            @if(!empty($result['poster_path']) && !empty($result['title']))
                                <li class="border-b border-gray-700">
                                    <a href="{{ route('movie.show', $result['id']) }}"
                                    class="flex items-center hover:bg-gray-700 px-3 py-3">
                                        <img src="https://image.tmdb.org/t/p/w92/{{ $result['poster_path'] }}"
                                            alt="poster" class="w-8 h-12 object-cover rounded shadow">
                                        <span class="ml-4">{{ $result['title'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @else
                    <div class="px-3 py-3">No results for "{{ $search }}"</div>
                @endif
            </div>
        @endif
    </div>
</div>
