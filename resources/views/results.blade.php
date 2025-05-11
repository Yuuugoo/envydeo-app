@extends('layouts.main')

@section('content')
    <div class="bg-gray-900 py-8" x-data="{ isAdvancedOpen: false }">
        <div class="container mx-auto px-4 md:pt-20 lg:pt-24 max-w-3xl md:max-w-5xl lg:max-w-7xl lg:ml-28">
            <h1 class="text-white text-4xl font-bold mb-6">Search</h1>
            
            <div class="relative">
                <form action="{{ route('search.results') }}" method="GET" id="searchForm">
                    <div class="flex items-center bg-gray-800 rounded-lg pr-4">
                        <div class="flex-1 relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                name="query" 
                                value="{{ $query ?? '' }}"
                                class="bg-transparent w-full py-4 pl-12 pr-10 text-white focus:outline-none placeholder-gray-400"
                                placeholder="Search for movies or TV shows..."
                                required
                                id="searchInput"
                            >
                        </div>
                        @if(!empty($query))
                            <button type="button" @click="document.getElementById('searchInput').value = ''" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>

                    <div x-show="isAdvancedOpen" x-cloak class="mt-6 bg-gray-800 p-6 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-white text-lg mb-4">Media Type</h3>
                                <div class="relative">
                                    <select name="media_type" class="w-full bg-gray-700 text-white py-3 px-4 pr-8 rounded-md appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="all" {{ ($selectedMediaType ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                        <option value="movie" {{ ($selectedMediaType ?? 'all') === 'movie' ? 'selected' : '' }}>Movies</option>
                                        <option value="tv" {{ ($selectedMediaType ?? 'all') === 'tv' ? 'selected' : '' }}>TV Shows</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-white text-lg mb-4">Sort By</h3>
                                <div class="relative">
                                    <select name="sort_by" class="w-full bg-gray-700 text-white py-3 px-4 pr-8 rounded-md appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="popularity" {{ ($selectedSortBy ?? 'popularity') === 'popularity' ? 'selected' : '' }}>Popularity</option>
                                        <option value="release_date" {{ ($selectedSortBy ?? 'popularity') === 'release_date' ? 'selected' : '' }}>Release Date</option>
                                        <option value="vote_average" {{ ($selectedSortBy ?? 'popularity') === 'vote_average' ? 'selected' : '' }}>Rating</option>
                                        <option value="title" {{ ($selectedSortBy ?? 'popularity') === 'title' ? 'selected' : '' }}>Title</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="flex flex-wrap items-center mt-6 gap-4">
                <button type="submit" form="searchForm" class="flex items-center btn gap-2 bg-green-600 hover:bg-green-800 text-white px-6 py-2 rounded-box font-semibold transition duration-150">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </button>
                
                <button @click="isAdvancedOpen = !isAdvancedOpen" class="flex items-center btn gap-2 bg-white/10 hover:bg-white/20 text-white px-4 py-1 rounded-box transition duration-150">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Advanced Search
                </button>
            </div>

            <div class="popular-movies mt-10">
                <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold mb-5">
                    Search Results for "{{ $query }}"
                </h2>

                @if($searchResults->count() > 0)
                    <div class="grid grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
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
                            @if($result['poster_path'])
                                <div class="mt-8 relative group">
                                    <a href="{{ route($routeName, $result['id']) }}">
                                        <img src="https://image.tmdb.org/t/p/w500/{{ $result['poster_path'] }}" alt="{{ $title }}" class="hover:opacity-75 transition ease-in-out duration-150 rounded">
                                        @if(isset($result['overview']) && !empty($result['overview']))
                                            <div class="absolute bottom-15 left-0 right-0 bg-black bg-opacity-75 p-2 rounded-b transform translate-y-full opacity-0 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                                                <p class="text-gray-300 text-sm line-clamp-3">{{ $result['overview'] }}</p>
                                            </div>
                                        @endif
                                    </a>
                                    <div class="mt-2">
                                        <a href="{{ route($routeName, $result['id']) }}" class="text-lg mt-2 hover:text-gray-300 line-clamp-1">{{ $title }}</a>
                                        <div class="flex items-center justify-between text-gray-400 text-sm mt-1">
                                            <div>
                                                <span class="mr-1 lg:mr-2 ">{{ $year !== 'N/A' ? $year : 'Unknown Year' }}</span>
                                                <span class="uppercase px-0.5 md:px-2 py-0.5 rounded text-xs bg-gray-800">
                                                    {{ strtoupper($result['media_type']) }}
                                                </span>
                                            </div>
                                            <div class="flex items-end text-gray-400 text-sm">
                                                <svg class="fill-current text-yellow-500 w-4" viewBox="0 0 24 24"><g data-name="Layer 2"><path d="M17.56 21a1 1 0 01-.46-.11L12 18.22l-5.1 2.67a1 1 0 01-1.45-1.06l1-5.63-4.12-4a1 1 0 01-.25-1 1 1 0 01.81-.68l5.7-.83 2.51-5.13a1 1 0 011.8 0l2.54 5.12 5.7.83a1 1 0 01.81.68 1 1 0 01-.25 1l-4.12 4 1 5.63a1 1 0 01-.4 1 1 1 0 01-.62.18z" data-name="star"/></g></svg>
                                                <span class="ml-1">{{ isset($result['vote_average']) ? number_format($result['vote_average'], 1) : 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if(!isset($totalPages) || $totalPages <= 1)
                        <div class="flex justify-center mt-10 mb-4 text-gray-400">
                            @php
                                $displayedCount = $searchResults->filter(function($result) {
                                    return !empty($result['poster_path']);
                                })->count();
                            @endphp
                            <p>Showing {{ $displayedCount }} of {{ $displayableResults ?? $totalResults ?? 'many' }} results</p>
                        </div>
                    @endif

                    @if(isset($totalPages) && $totalPages > 1)
                        <div class="flex justify-center mt-10">
                            <div class="join shadow-md rounded-md">
                                @if($currentPage > 1)
                                    <a href="{{ route('search.results', ['query' => $query, 'media_type' => $selectedMediaType, 'sort_by' => $selectedSortBy, 'page' => 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">«</a>
                                    <a href="{{ route('search.results', ['query' => $query, 'media_type' => $selectedMediaType, 'sort_by' => $selectedSortBy, 'page' => $currentPage - 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">‹</a>
                                @endif
                                
                                @php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                @endphp
                                
                                @for ($i = $startPage; $i <= $endPage; $i++)
                                    <a href="{{ route('search.results', ['query' => $query, 'media_type' => $selectedMediaType, 'sort_by' => $selectedSortBy, 'page' => $i]) }}" 
                                    class="join-item btn {{ $i == $currentPage ? 'bg-green-500 hover:bg-green-600 text-white border-green-600' : 'bg-gray-800 hover:bg-gray-700 text-white border-gray-700' }}">
                                        {{ $i }}
                                    </a>
                                @endfor
                                
                                @if($currentPage < $totalPages)
                                    <a href="{{ route('search.results', ['query' => $query, 'media_type' => $selectedMediaType, 'sort_by' => $selectedSortBy, 'page' => $currentPage + 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">›</a>
                                    <a href="{{ route('search.results', ['query' => $query, 'media_type' => $selectedMediaType, 'sort_by' => $selectedSortBy, 'page' => $totalPages]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">»</a>
                                @endif
                            </div>
                        </div>
                    @endif
                @else
                    <div class="bg-gray-800 p-8 rounded-lg text-center">
                        <p class="text-xl text-gray-300">No results found for "{{ $query }}"</p>
                        <p class="text-gray-400 mt-2">Try searching for a different term or check your spelling.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
