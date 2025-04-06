@extends('layouts.main')

@section('content')
    <div class="container mx-auto px-4 pt-16">
        <div class="popular-movies">
            <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold mb-4">
                Popular Movies
            </h2>
            <div
                x-data="{
                    movies: {{ json_encode($popularMovies) }},
                    genres: {{ json_encode($genres) }},
                    getChunkSize() {
                        if (window.innerWidth < 640) return 1;
                        if (window.innerWidth < 768) return 3;
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
                        for (let i = 0; i < this.movies.length; i += chunkSize) {
                            this.chunks.push(this.movies.slice(i, i + chunkSize));
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
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                    <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                        <div class="mt-4 md:mt-8 relative group">
                                            <a :href="`/movies/${movie.id}`">
                                                <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                    :alt="movie.title"
                                                    class="w-full block hover:opacity-75 transition ease-in-out duration-150 rounded-lg shadow">
                                            </a>
                                            <div class="absolute bottom-0 left-0 w-full bg-black bg-opacity-75 text-white p-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity rounded-b-lg">
                                                <a :href="`/movies/${movie.id}`" class="text-base md:text-lg mt-1 hover:text-gray-300 line-clamp-1" x-text="movie.title"></a>
                                                <div class="flex items-center text-xs md:text-sm">
                                                    <span>⭐</span>
                                                    <span class="ml-1" x-text="formatVoteAverage(movie.vote_average)"></span>
                                                    <span class="mx-2">•</span>
                                                    <span x-text="new Date(movie.release_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})"></span>
                                                </div>
                                                <div class="text-xs mt-1 text-gray-300 line-clamp-1">
                                                    <template x-for="(genreId, genreIndex) in movie.genre_ids" :key="genreId">
                                                        <span>
                                                            <span x-text="genres[genreId]"></span>
                                                            <template x-if="genreIndex < movie.genre_ids.length - 1">
                                                                <span>, </span>
                                                            </template>
                                                        </span>
                                                    </template>
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
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 pt-16">
        <div class="top-rated-movies">
            <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold mb-4">
                Top Rated Movies
            </h2>
            <div
                x-data="{
                    movies: {{ json_encode($topRatedMovies) }},
                    genres: {{ json_encode($genres) }},
                    getChunkSize() {
                        if (window.innerWidth < 640) return 1;
                        if (window.innerWidth < 768) return 3;
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
                        for (let i = 0; i < this.movies.length; i += chunkSize) {
                            this.chunks.push(this.movies.slice(i, i + chunkSize));
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
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                    <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                        <div class="mt-4 md:mt-8 relative group">
                                            <a :href="`/movies/${movie.id}`">
                                                <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                    :alt="movie.title"
                                                    class="w-full block hover:opacity-75 transition ease-in-out duration-150 rounded-lg shadow">
                                            </a>
                                            <div class="absolute bottom-0 left-0 w-full bg-black bg-opacity-75 text-white p-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity rounded-b-lg">
                                                <a :href="`/movies/${movie.id}`" class="text-base md:text-lg mt-1 hover:text-gray-300 line-clamp-1" x-text="movie.title"></a>
                                                <div class="flex items-center text-xs md:text-sm">
                                                    <span>⭐</span>
                                                    <span class="ml-1" x-text="formatVoteAverage(movie.vote_average)"></span>
                                                    <span class="mx-2">•</span>
                                                    <span x-text="new Date(movie.release_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})"></span>
                                                </div>
                                                <div class="text-xs mt-1 text-gray-300 line-clamp-1">
                                                    <template x-for="(genreId, genreIndex) in movie.genre_ids" :key="genreId">
                                                        <span>
                                                            <span x-text="genres[genreId]"></span>
                                                            <template x-if="genreIndex < movie.genre_ids.length - 1">
                                                                <span>, </span>
                                                            </template>
                                                        </span>
                                                    </template>
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
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 pt-16 mt-5"></div>
@endsection