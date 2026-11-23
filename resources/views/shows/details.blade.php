@extends('layouts.main')

@section('content')
<div class="tv-info border-b border-gray-800">
    <div class="relative lg:-mt-10 lg:h-screen lg:max-h-[38rem] h-full">
        <div class="w-full pb-48 md:pb-0 lg:pb-0 md:h-full relative">
            <img src="{{ 'https://image.tmdb.org/t/p/original/' . $tvShow['backdrop_path'] }}"
                alt="{{ $tvShow['name'] }}"
                class="w-full h-full object-cover object-center">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
        
        <!-- SHOWS info and buttons -->
        <div class="absolute max-w-xl bottom-0 left-6 mt-64 md:mt-72 md:bottom-16 md:max-w-2xl lg:mt-0 lg:bottom-8 lg:left-28 text-white space-y-4 z-10">
            <span class="bg-gray-900 px-3 py-1 rounded-full text-xs font-semibold">{{ $selectedTVDetails['media_type']}}</span>
            <h2 class="text-xl mt-3 md:text-4xl font-bold mb-2 md:mb-4 hover:text-green-600">{{ $tvShow['name'] }}</h2>
            <p class="text-xs md:text-sm text-gray-300">
                {{ $tvShow['number_of_seasons'] }} Season •
                {{ \Carbon\Carbon::parse($tvShow['last_air_date'])->format('Y') }} •
                {{ implode(' • ', $selectedTVDetails['genres']) }}
            </p>
            
            <div class="flex flex-col md:flex-row gap-4 mt-4">
                
                <!-- Watch Trailer Button -->
                <div x-data="{ isOpen: false }">
                    <button
                        @click="isOpen = true"
                        class="btn gap-2 w-full md:w-auto bg-white/10 hover:bg-white/20 text-white md:px-6 md:py-2 rounded-box"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                        </svg>
                        Watch Latest Trailer
                    </button>
                    
                    <!-- Trailer Modal -->
                    <template x-if="isOpen">
                        <div
                            style="background-color: rgba(0, 0, 0, .5);"
                            class="fixed top-0 left-0 w-full h-full flex items-center shadow-lg overflow-y-auto z-50"
                        >
                            <div class="container mx-auto lg:px-32 rounded-lg overflow-y-auto">
                                <div class="bg-gray-900 rounded">
                                    <div class="flex justify-end pr-4 pt-2">
                                        <button
                                            @click="isOpen = false"
                                            @keydown.escape.window="isOpen = false"
                                            class="text-3xl leading-none hover:text-gray-300">&times;
                                        </button>
                                    </div>
                                    <div class="modal-body px-8 py-8">
                                        <div class="responsive-container overflow-hidden relative pt-96">
                                            @if(isset($tvShow['videos']['results'][0]['key']))
                                                <iframe class="responsive-iframe absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/{{ $tvShow['videos']['results'][0]['key'] }}" style="border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                            @else
                                                <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center bg-gray-800">
                                                    <p class="text-white text-xl">No trailer available</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Add to Watchlist Button (disabled) -->
                <button class="btn btn-disabled w-full md:w-auto flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white md:px-6 py-2 rounded-box border border-white/20 backdrop-blur transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg>
                    Add to Watchlist
                </button>
            </div>
        </div>
    </div>
</div>

<div class="mx-6 md:mx-auto max-w-xl md:max-w-3xl lg:max-w-7xl py-8 md:py-10">
    <div class="md:flex-row items-center md:items-start gap-6">
        <h3 class="text-white font-semibold text-center md:text-left">Storyline</h3>
        <p class="text-gray-300 mt-4 text-sm md:text-base">
            {{ $tvShow['overview'] }}
        </p>
    </div>
</div>
<div  x-data="{ activeTab: 'episodes' }">

<div class="max-w-xl mx-6 md:max-w-3xl lg:max-w-7xl md:mx-auto py-5">
<div role="tablist" class="flex space-x-6">
    <a role="tab" 
    @click.prevent="activeTab = 'episodes'" 
    :class="{ 'border-b-2 border-teal-500 pb-2': activeTab === 'episodes', 'text-gray-400': activeTab !== 'episodes' }"
    class="cursor-pointer text-sm font-semibold transition duration-150 text-white">
        Episodes
    </a>
    <a role="tab" 
    @click.prevent="activeTab = 'similar'" 
    :class="{ 'border-b-2 border-teal-500 pb-2': activeTab === 'similar', 'text-gray-400': activeTab !== 'similar' }"
    class="cursor-pointer text-sm font-semibold transition duration-150 text-white">
        Recommendations
    </a>
    <a role="tab" 
    @click.prevent="activeTab = 'images'" 
    :class="{ 'border-b-2 border-teal-500 pb-2': activeTab === 'images', 'text-gray-400': activeTab !== 'images' }"
    class="cursor-pointer text-sm font-semibold transition duration-150 text-white">
        Images
    </a>
</div>
</div>

<!-- Tab content panels -->
<div class="mx-6 max-w-xl md:max-w-3xl lg:max-w-7xl md:mx-auto py-5">
            <!-- Episodes Tab -->
            <div x-show="activeTab === 'episodes'" class="tab-panel" x-data="{
                selectedSeason: 1,
                seasons: {{ json_encode(array_keys($episodes)) }},
                episodes: {{ json_encode($episodes) }},
                tvId: {{ $tvShow['id'] }}
            }">
                <div class="flex flex-row items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold">Episodes</h2>
                    <div class="dropdown dropdown-end" x-ref="seasonDropdown">
                        <div tabindex="0" role="button" class="btn btn-outline">
                            Season <span x-text="selectedSeason"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-gray-900 rounded-box w-52">
                            <template x-for="season in seasons" :key="season">
                                <li>
                                    <a @click="selectedSeason = season; $refs.seasonDropdown.querySelector('.dropdown-content').blur()" class="hover:bg-gray-700">
                                        Season <span x-text="season"></span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="grid gap-4">
                    <template x-for="episode in episodes[selectedSeason]" :key="episode.id">
                        <div class="card bg-gray-800 shadow-xl">
                            <div class="card-body">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0">
                                        <div x-data="{ isOpen: false }">
                                            <div 
                                                @click="isOpen = true" 
                                                class="relative w-32 cursor-pointer group"
                                            >
                                                <img 
                                                    :src="episode.still_path 
                                                        ? `https://image.tmdb.org/t/p/w200/${episode.still_path}`
                                                        : '/placeholder.jpg'"
                                                    :alt="episode.name"
                                                    class="rounded-lg mt-10 h-28 md:mt-5 md:h-full"
                                                >

                                                <!-- Play Button Overlay -->
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="bg-black bg-opacity-50 rounded-full p-2 group-hover:scale-110 transition">
                                                        <!-- You can use a play SVG or emoji, or an icon library -->
                                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M6 4l12 6-12 6V4z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>


                                            
                                            <!-- Episode Modal -->
                                            <template x-if="isOpen">
                                                <div
                                                    style="background-color: rgba(0, 0, 0, .5);"
                                                    class="fixed top-0 left-0 w-full h-full flex items-center shadow-lg overflow-y-auto z-50"
                                                >
                                                    <div class="container mx-auto lg:px-32 rounded-lg overflow-y-auto">
                                                        <div class="bg-gray-900 rounded">
                                                            <div class="flex justify-end pr-4 pt-2">
                                                                <button
                                                                    @click="isOpen = false"
                                                                    @keydown.escape.window="isOpen = false"
                                                                    class="text-3xl leading-none hover:text-gray-300">&times;
                                                                </button>
                                                            </div>
                                                            <div class="modal-body px-8 py-8">
                                                                <div class="responsive-container overflow-hidden relative pt-96">
                                                                    <iframe 
                                                                        class="responsive-iframe absolute top-0 left-0 w-full h-full" 
                                                                        x-bind:src="`https://vidfast.pro/tv/${tvId}/${selectedSeason}/${episode.episode_number}?nextButton=true&autoNext=true`"
                                                                        style="border:0;" 
                                                                        allow="autoplay; encrypted-media" 
                                                                        allowfullscreen>
                                                                    </iframe>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="card-title text-md md:text-lg">
                                            Episode <span x-text="episode.episode_number"></span>: 
                                            <span x-text="episode.name"></span>
                                        </h3>
                                        <div class="badge badge-outline" x-text="'Runtime: ' + episode.runtime + 'm'"></div>
                                        <p class="mt-2 text-gray-300 line-clamp-3" x-text="episode.overview || 'No description available'"></p>
                                        <div class="mt-2 flex items-center gap-2 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                            <span x-text="episode.vote_average.toFixed(1)"></span>
                                            <span class="mx-2">|</span>
                                            <span x-text="'Aired: ' + episode.air_date"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Similar Tab -->
            <div x-show="activeTab === 'similar'" class="tab-panel">
                <h2 class="text-2xl font-bold">More Like This Series</h2>
                <div class="container mx-auto max-w-7xl">
                    <div class="similar-shows">
                        <div
                            x-data="{
                                movies: {{ json_encode($similarTV) }},
                                genres: {{ json_encode($genres) }},
                                getChunkSize() {
                                    if (window.innerWidth < 640) return 1;
                                    if (window.innerWidth < 811) return 3;
                                    return 5;
                                },
                                chunks: [],
                                currentIndex: 0,
                                formatVoteAverage(vote) {
                                    return (vote * 10).toFixed(0) + '%';
                                },
                                init() {
                                    this.updateChunks();
                                    window.addEventListener('resize', () => this.updateChunks());
                                },
                                updateChunks() {
                                    const chunkSize = this.getChunkSize();
                                    this.chunks = [];

                                    const filteredMovies = this.movies.filter(movie => movie.backdrop_path);

                                    for (let i = 0; i < filteredMovies.length; i += chunkSize) {
                                        this.chunks.push(filteredMovies.slice(i, i + chunkSize));
                                    }

                                    if (this.currentIndex >= this.chunks.length) {
                                        this.currentIndex = this.chunks.length - 1;
                                    }
                                },
                                next() {
                                    if (this.currentIndex < this.chunks.length - 1) {
                                        this.currentIndex++;
                                    }
                                },
                                prev() {
                                    if (this.currentIndex > 0) {
                                        this.currentIndex--;
                                    }
                                }
                            }"
                            class="relative"
                        >

                            <button 
                                @click="prev" 
                                x-show="currentIndex > 0"
                                class="absolute left-0 top-1/2 z-10 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            
                            <div class="overflow-hidden px-4">
                                <div
                                    class="flex transition-transform duration-300 ease-in-out"
                                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                                >
                                    <template x-for="(chunk, index) in chunks" :key="index">
                                        <div class="w-full flex-shrink-0">
                                            <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                                <template x-for="(tv, tvIndex) in chunk" :key="tv.id">
                                                    <div class="mt-4 md:mt-8">
                                                        
                                                        <div class="flex flex-col">
                                                            <a :href="`/shows/${tv.id}`">
                                                                <img :src="`https://image.tmdb.org/t/p/w500/${tv.backdrop_path}`"
                                                                    :alt="tv.name"
                                                                    class="w-full block hover:opacity-75 transition ease-in-out duration-150 rounded-lg shadow">
                                                            </a>

                                                            <div class="text-white p-2 rounded-b-lg">
                                                                <a :href="`/shows/${tv.id}`" class="text-base md:text-lg mt-1 hover:text-gray-300 line-clamp-1" x-text="tv.name"></a>
                                                                <div class="flex items-center text-xs md:text-sm">
                                                                    <span>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-yellow-400">
                                                                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </span>
                                                                    <span class="ml-1 font-semibold" x-text="formatVoteAverage(tv.vote_average)"></span>
                                                                    <span class="mx-1">|</span>
                                                                    <div class="text-xs mt-1 font-medium text-gray-500 line-clamp-1">
                                                                        <template x-for="(genreId, genreIndex) in tv.genre_ids" :key="genreId">
                                                                            <span>
                                                                                <span x-text="genres[genreId]"></span>
                                                                                <template x-if="genreIndex < tv.genre_ids.length - 1">
                                                                                    <span>, </span>
                                                                                </template>
                                                                            </span>
                                                                        </template>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                                                            
                            <button 
                                @click="next" 
                                x-show="currentIndex < chunks.length - 1"
                                class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold mt-3">You Might Also Like</h2>                                             
                    <div class="recommended-tv">
                        <div
                            x-data="{
                                movies: {{ json_encode($recommendedTV) }},
                                genres: {{ json_encode($genres) }},
                                getChunkSize() {
                                    if (window.innerWidth < 640) return 1;
                                    if (window.innerWidth < 811) return 3;
                                    return 5;
                                },
                                chunks: [],
                                currentIndex: 0,
                                formatVoteAverage(vote) {
                                    return (vote * 10).toFixed(0) + '%';
                                },
                                init() {
                                    this.updateChunks();
                                    window.addEventListener('resize', () => this.updateChunks());
                                },
                                updateChunks() {
                                    const chunkSize = this.getChunkSize();
                                    this.chunks = [];

                                    const filteredMovies = this.movies.filter(movie => movie.backdrop_path);

                                    for (let i = 0; i < filteredMovies.length; i += chunkSize) {
                                        this.chunks.push(filteredMovies.slice(i, i + chunkSize));
                                    }

                                    if (this.currentIndex >= this.chunks.length) {
                                        this.currentIndex = this.chunks.length - 1;
                                    }
                                },
                                next() {
                                    if (this.currentIndex < this.chunks.length - 1) {
                                        this.currentIndex++;
                                    }
                                },
                                prev() {
                                    if (this.currentIndex > 0) {
                                        this.currentIndex--;
                                    }
                                }
                            }"
                            class="relative"
                        >

                            <button 
                                @click="prev" 
                                x-show="currentIndex > 0"
                                class="absolute left-0 top-1/2 z-10 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            
                            <div class="overflow-hidden px-4">
                                <div
                                    class="flex transition-transform duration-300 ease-in-out"
                                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                                >
                                    <template x-for="(chunk, index) in chunks" :key="index">
                                        <div class="w-full flex-shrink-0">
                                            <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                                <template x-for="(tv, tvIndex) in chunk" :key="tv.id">
                                                    <div class="mt-4 md:mt-8">
                                                        
                                                        <div class="flex flex-col">
                                                            <a :href="`/shows/${tv.id}`">
                                                                <img :src="`https://image.tmdb.org/t/p/w500/${tv.backdrop_path}`"
                                                                    :alt="tv.name"
                                                                    class="w-full block hover:opacity-75 transition ease-in-out duration-150 rounded-lg shadow">
                                                            </a>

                                                            <div class="text-white p-2 rounded-b-lg">
                                                                <a :href="`/shows/${tv-show.id}`" class="text-base md:text-lg mt-1 hover:text-gray-300 line-clamp-1" x-text="tv.name"></a>
                                                                <div class="flex items-center text-xs md:text-sm">
                                                                    <span>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-yellow-400">
                                                                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </span>
                                                                    <span class="ml-1 font-semibold" x-text="formatVoteAverage(tv.vote_average)"></span>
                                                                    <span class="mx-1">|</span>
                                                                    <div class="text-xs mt-1 font-medium text-gray-500 line-clamp-1">
                                                                        <template x-for="(genreId, genreIndex) in tv.genre_ids" :key="genreId">
                                                                            <span>
                                                                                <span x-text="genres[genreId]"></span>
                                                                                <template x-if="genreIndex < tv.genre_ids.length - 1">
                                                                                    <span>, </span>
                                                                                </template>
                                                                            </span>
                                                                        </template>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                                                            
                            <button 
                                @click="next" 
                                x-show="currentIndex < chunks.length - 1"
                                class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Images Tab with Modal -->
            <div x-show="activeTab === 'images'" class="tab-panel" x-data="{ isOpen: false, image: '' }">
                <div class="movie-images" x-data="{ isOpen: false, image: ''}">
                    <div class="container mx-auto max-w-7xl">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                            @foreach (array_slice($tvShow['images']['backdrops'] ?? [], 0, 15) as $image)
                                    <div class="mt-8">
                                        <a
                                            @click.prevent="
                                                isOpen = true
                                                image='{{ 'https://image.tmdb.org/t/p/w1280'.$image['file_path'] }}'
                                            "
                                            href="#"
                                        >
                                            <img src="{{ 'https://image.tmdb.org/t/p/w500'.$image['file_path'] }}" loading="lazy" alt="{{ $tvShow['name'] }} backdrop" class="hover:opacity-75 transition ease-in-out duration-150">
                                        </a>
                                    </div>
                            @endforeach
                        </div>

                        <div
                            style="background-color: rgba(0, 0, 0, .5);"
                            class="fixed top-5 left-0 w-full py-96 h-80 flex items-center shadow-lg overflow-y-auto z-50"
                            x-show="isOpen"
                        >
                            <div class="container mx-auto lg:px-32 rounded-lg overflow-y-auto">
                                <div class="bg-gray-900 rounded">
                                    <div class="flex justify-end pr-4 pt-2">
                                        <button
                                            @click="isOpen = false"
                                            @keydown.escape.window="isOpen = false"
                                            class="text-3xl leading-none hover:text-gray-300">&times;
                                        </button>
                                    </div>
                                    <div class="modal-body px-8 py-8">
                                        <img :src="image" alt="poster">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection