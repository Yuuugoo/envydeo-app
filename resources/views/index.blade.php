@extends('layouts.main')

@section('content')
<div class="relative lg:-mt-28 lg:h-screen lg:max-h-[38rem] h-full" 
     x-data="{
         currentSlide: 0,
         totalSlides: {{ count($moviesWithDetails) }},
         autoplayInterval: null,
         isAutoplay: true,
         anyModalOpen: false,
         activeMovie: null,
         activeTrailerKey: null,

         nextSlide() {
             if (!this.anyModalOpen) {
                 this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
             }
         },
         
         prevSlide() {
             if (!this.anyModalOpen) {
                 this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
             }
         },
         
         goToSlide(index) {
             if (!this.anyModalOpen) {
                 this.currentSlide = index;
                 this.resetAutoplay();
             }
         },
         
         startAutoplay() {
             if (this.isAutoplay && !this.anyModalOpen) {
                 this.autoplayInterval = setInterval(() => {
                     this.nextSlide();
                 }, 5000);
             }
         },
         
         resetAutoplay() {
             clearInterval(this.autoplayInterval);
             this.startAutoplay();
         },
         
         toggleAutoplay() {
             this.isAutoplay = !this.isAutoplay;
             if (this.isAutoplay) {
                 this.startAutoplay();
             } else {
                 clearInterval(this.autoplayInterval);
             }
         },
         
         openMovieModal(movie) {
             this.anyModalOpen = true;
             this.activeMovie = movie;
             clearInterval(this.autoplayInterval);
         },
         
         openTrailerModal(movie) {
             this.anyModalOpen = true;
             this.activeTrailerKey = movie.trailer_key;
             clearInterval(this.autoplayInterval);
         },
         
         closeModals() {
             this.anyModalOpen = false;
             this.activeMovie = null;
             this.activeTrailerKey = null;
             if (this.isAutoplay) {
                 this.startAutoplay();
             }
         }
     }"
     x-init="startAutoplay()"
     @keydown.right.window="nextSlide()"
     @keydown.left.window="prevSlide()">
    
    <div class="carousel w-full pb-96 md:pb-48 lg:pb-0 md:h-full relative">
        @foreach($moviesWithDetails as $key => $movie)
            <div 
                class="carousel-item w-full relative transition-opacity duration-500"
                x-show="currentSlide === {{ $key }}"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                <img src="{{ 'https://image.tmdb.org/t/p/original/' . $movie['backdrop_path'] }}"
                    class="w-full h-full object-cover object-center"
                    alt="{{ $movie['title'] }}" />

                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>

                <div class="absolute max-w-xl mt-64 md:mt-72 lg:mt-0 left-6 lg:bottom-8 lg:left-8 text-white md:max-w-2xl space-y-4 z-10">
                    <span class="bg-gray-900 px-3 py-1 rounded-full text-xs font-semibold">{{ $movie['media_type']}}</span>
                    <h2 class="text-3xl mt-3 font-bold">{{ $movie['title'] }}</h2>
                    <p class="text-sm text-gray-300">
                        {{ floor($movie['runtime'] / 60) }}h {{ $movie['runtime'] % 60 }}m •
                        {{ \Carbon\Carbon::parse($movie['release_date'])->format('Y') }} •
                        {{ implode(' • ', $movie['genres']) }}
                    </p>
                    <p class="block md:hidden text-sm text-gray-200">
                        {{ \Illuminate\Support\Str::limit($movie['overview'], 200) }}
                    </p>
                    <p class="hidden md:block text-sm text-gray-200">
                        {{ \Illuminate\Support\Str::limit($movie['overview'], 500) }}
                    </p>
                    <div class="md:flex md:flex-wrap gap-4 mt-4">
                        <button 
                            @click.prevent="openMovieModal(@js($movie))" 
                            class="btn gap-2 bg-green-600 hover:bg-green-800 text-white px-6 py-2 rounded-box font-semibold"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm14.024-.983a1.125 1.125 0 0 1 0 1.966l-5.603 3.113A1.125 1.125 0 0 1 9 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113Z" clip-rule="evenodd" />
                            </svg>
                            Play Now
                        </button>

                        <button 
                            @click.prevent="openTrailerModal(@js($movie))" 
                            class="btn gap-2 bg-white/10 hover:bg-white/20 text-white px-6 py-2 rounded-box"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                            </svg>
                            Watch Trailer
                        </button>

                        {{-- Add to Watchlist currently Disabled, no user yet --}}
                        <button class="btn btn-disabled flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-6 py-2 rounded-box border border-white/20 backdrop-blur transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            Add to Watchlist
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="absolute flex top-56 right-7 md:top-150 lg:top-130 md:right-8 justify-center gap-2">
            @foreach($moviesWithDetails as $key => $movie)
                <label class="radio-container cursor-pointer">
                    <input 
                        type="radio" 
                        name="slide-radio" 
                        class="hidden" 
                        :checked="currentSlide === {{ $key }}"
                        @click="goToSlide({{ $key }})"
                    >
                    <div 
                        class="w-3 h-3 rounded-full transition-all duration-300" 
                        :class="currentSlide === {{ $key }} ? 'bg-white scale-125' : 'bg-white/40 hover:bg-white/60'"
                    ></div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Movie Modal --}}
    <template x-if="activeMovie">
        <div
            style="background-color: rgba(0, 0, 0, .5);"
            class="fixed top-0 left-0 w-full h-full flex items-center shadow-lg overflow-y-auto z-[100]"
        >
            <div class="container mx-auto lg:px-32 rounded-lg overflow-y-auto">
                <div class="bg-gray-900 rounded">
                    <div class="flex justify-end pr-4 pt-2">
                        <button
                            @click="closeModals()"
                            @keydown.escape.window="closeModals()"
                            class="text-3xl leading-none hover:text-gray-300"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body px-8 py-8">
                        <div class="responsive-container overflow-hidden relative pt-96">
                            <iframe 
                                class="responsive-iframe absolute top-0 left-0 w-full h-full" 
                                :src="'https://vidfast.pro/movie/' + activeMovie.id" 
                                style="border:0;" 
                                allow="autoplay; encrypted-media" 
                                allowfullscreen
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Trailer Modal --}}
    <template x-if="activeTrailerKey">
        <div
            style="background-color: rgba(0, 0, 0, .5);"
            class="fixed top-0 left-0 w-full h-full flex items-center shadow-lg overflow-y-auto z-[100]"
        >
            <div class="container mx-auto lg:px-32 rounded-lg overflow-y-auto">
                <div class="bg-gray-900 rounded">
                    <div class="flex justify-end pr-4 pt-2">
                        <button
                            @click="closeModals()"
                            @keydown.escape.window="closeModals()"
                            class="text-3xl leading-none hover:text-gray-300"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body px-8 py-8">
                        <div class="responsive-container overflow-hidden relative pt-96">
                            <iframe 
                                class="responsive-iframe absolute top-0 left-0 w-full h-full" 
                                :src="'https://www.youtube.com/embed/' + activeTrailerKey" 
                                style="border:0;" 
                                allow="autoplay; encrypted-media" 
                                allowfullscreen
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
<!-- <div class="absolute bottom-8 right-8 flex gap-4">
            <button 
                @click="prevSlide()"
                class="btn btn-circle bg-white/20 hover:bg-white/30 text-white border-none backdrop-blur"
            >
                ❮
            </button>
            <button 
                @click="toggleAutoplay()"
                class="btn btn-circle bg-white/20 hover:bg-white/30 text-white border-none backdrop-blur"
                :class="{ 'bg-green-600/30 hover:bg-green-600/40': isAutoplay }"
            >
                <span x-show="isAutoplay">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                    </svg>
                </span>
                <span x-show="!isAutoplay">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>
                </span>
            </button>
            <button 
                @click="nextSlide()"
                class="btn btn-circle bg-white/20 hover:bg-white/30 text-white border-none backdrop-blur"
            >
                ❯
            </button>
        </div> -->

<div class="container mx-auto px-4 pt-16 max-w-7xl">
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
                class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
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
                class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 pt-16  max-w-7xl">
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
                class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
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
                class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800"
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