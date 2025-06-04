@extends('layouts.main')

@section('content')
<div class="relative md:-mt-2 lg:-mt-10 lg:h-screen lg:max-h-[38rem] h-full" 
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
    
    <div class="carousel w-full pb-[28rem] md:pb-10 lg:pb-0 md:h-full relative">
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
                @if($movie['media_type'] == 'TV')
                    <img src="{{ 'https://image.tmdb.org/t/p/original/' . $movie['backdrop_path'] }}"
                        class="w-full h-full object-cover object-center"
                        alt="{{ $movie['name'] }}" />

                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>

                    <div class="absolute max-w-xl mt-64 md:mt-72 lg:mt-0 left-4 right-4 lg:bottom-8 lg:left-32 lg:right-0 text-white md:max-w-2xl space-y-4 z-10">
                        <span class="bg-gray-900 px-3 py-1 rounded-full text-xs font-semibold">{{ $movie['media_type']}}</span>
                        <h2 class="text-3xl mt-3 font-bold">{{ $movie['name'] }}</h2>
                        <p class="text-sm text-gray-300">
                            {{ $movie['number_of_seasons'] }} Season •
                            {{ implode(' • ', $movie['genres']) }}
                        </p>
                        <p class="block md:hidden text-sm text-gray-200">
                            {{ \Illuminate\Support\Str::limit($movie['overview'], 200) }}
                        </p>
                        <p class="hidden md:block text-sm text-gray-200">
                            {{ \Illuminate\Support\Str::limit($movie['overview'], 500) }}
                        </p>
                        <div class="flex flex-col md:flex-row gap-3 mt-3 md:gap-4 md:mt-4">
                            <a href="{{ route('show.show', $movie['id']) }}"
                                class="btn gap-2 bg-green-600 hover:bg-green-800 text-white px-6 py-2 rounded-box font-semibold"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm14.024-.983a1.125 1.125 0 0 1 0 1.966l-5.603 3.113A1.125 1.125 0 0 1 9 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113Z" clip-rule="evenodd" />
                                </svg>
                                Play Now
                            </a>

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
                @else
                    <img src="{{ 'https://image.tmdb.org/t/p/original/' . $movie['backdrop_path'] }}"
                        class="w-full h-full object-cover object-center"
                        alt="{{ $movie['title'] }}" />

                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>

                    <div class="absolute max-w-xl mt-64 md:mt-72 lg:mt-0 left-4 right-4 lg:bottom-8 lg:left-32 lg:right-0 text-white md:max-w-2xl space-y-4 z-10">
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
                        <div class="flex flex-col md:flex-row gap-3 mt-3 md:gap-4 md:mt-4">
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

                            <livewire:add-watchlist :tmdb-id="$movie['id']" />
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="absolute flex top-56 right-7 md:top-150 lg:top-130 md:right-32 justify-center gap-2">
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

@auth
    @if($continueWatchingMovies->isNotEmpty())
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="continue-watching">
                <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
                    Continue Watching
                </h2>
                <div
                    x-data="{
                        movies: {{ json_encode($continueWatchingMovies) }},
                        genres: {{ json_encode($genres) }},
                        getChunkSize() {
                            if (window.innerWidth < 640) return 3;
                            else if (window.innerWidth < 1280) return 5;
                            return 6;
                        },
                        chunks: [],
                        currentIndex: 0,
                        touchStartX: 0,
                        touchEndX: 0,
                        formatVoteAverage(vote) {
                            return (vote * 10).toFixed(0) + '%';
                        },
                        formatProgress(watched, duration) {
                            if (!duration || duration === 0) return 0;
                            return Math.round((watched / duration) * 100);
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
                        },
                        handleTouchStart(e) {
                            this.touchStartX = e.changedTouches[0].screenX;
                        },
                        handleTouchEnd(e) {
                            this.touchEndX = e.changedTouches[0].screenX;
                            this.handleSwipe();
                        },
                        handleSwipe() {
                            const threshold = 50;
                            const swipeDistance = this.touchEndX - this.touchStartX;
                            
                            if (swipeDistance > threshold) {
                                this.prev();
                            } else if (swipeDistance < -threshold) {
                                this.next();
                            }
                        }
                    }"
                    class="relative"
                >
                    <button 
                        @click="prev" 
                        x-show="currentIndex > 0"
                        class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                            btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    
                    <div class="overflow-hidden px-4 py-4">
                        <div 
                            class="flex transition-transform duration-300 ease-in-out"
                            :style="`transform: translateX(-${currentIndex * 100}%)`"
                            @touchstart="handleTouchStart($event)"
                            @touchend="handleTouchEnd($event)"
                        >
                            <template x-for="(chunk, index) in chunks" :key="index">
                                <div class="w-full flex-shrink-0">
                                    <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                        <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                            <div class="mt-4 relative group">
                                                <a :href="`/movies/${movie.id}`">
                                                    <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                        :alt="movie.title"
                                                        class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow">
                                                </a>
                                                
                                                <!-- Progress Bar -->
                                                <div class="absolute bottom-0 left-0 w-full bg-gray-800 bg-opacity-90 rounded-b-lg">
                                                    <div class="px-2 py-1">
                                                        <div class="w-full bg-gray-600 rounded-full h-1.5">
                                                            <div class="bg-green-600 h-1.5 rounded-full transition-all duration-300" 
                                                                :style="`width: ${formatProgress(movie.watch_progress?.watched || 0, movie.watch_progress?.duration || 0)}%`">
                                                            </div>
                                                        </div>
                                                        <div class="text-xs text-white mt-1">
                                                            <span x-text="`${formatProgress(movie.watch_progress?.watched || 0, movie.watch_progress?.duration || 0)}%`"></span>
                                                            <span class="text-gray-300 ml-1">watched</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="hidden md-block">
                                                    <div class="absolute bottom-10 left-0 w-full bg-black bg-opacity-75 text-white p-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity rounded-lg">
                                                        <a :href="`/movies/${movie.id}`" class="text-base md:text-lg mt-1 hover:text-gray-300 line-clamp-1" x-text="movie.title"></a>
                                                        <div class="flex items-center text-xs md:text-sm">
                                                            <span>
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-yellow-400">
                                                                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                                                </svg>
                                                            </span>
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
                        class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                            btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div class="flex justify-center mt-4 lg:hidden">
                        <template x-for="(chunk, index) in chunks" :key="index">
                            <div 
                                class="w-2 h-2 mx-1 rounded-full transition-all duration-200" 
                                :class="currentIndex === index ? 'bg-green-600 scale-125' : 'bg-gray-400'"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth

<div class="container mx-auto px-4 max-w-7xl">
    <div class="popular-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Popular Movies
        </h2>
        <div
            x-data="{
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;
                    
                    try {
                        const response = await fetch('/api/movies/popular');
                        const data = await response.json();
                        
                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading popular movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    // Set up intersection observer for lazy loading
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });
                    
                    observer.observe(this.$el);
                    
                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;
                    
                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-4">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load popular movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Navigation Buttons -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            
            <!-- Movie Content -->
            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-4 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                        
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Pagination Dots -->
            <div x-show="!loading && !error" class="flex justify-center mt-4 lg:hidden">
                <template x-for="(chunk, index) in chunks" :key="index">
                    <div 
                        class="w-2 h-2 mx-1 rounded-full transition-all duration-200" 
                        :class="currentIndex === index ? 'bg-green-600 scale-125' : 'bg-gray-400'"
                    ></div>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- Top Rated Movies Section with Lazy Loading -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="top-rated-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Top Rated Movies
        </h2>
        <div
            x-data="{
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;
                    
                    try {
                        const response = await fetch('/api/movies/top-rated');
                        const data = await response.json();
                        
                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading top rated movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });
                    
                    observer.observe(this.$el);
                    
                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;
                    
                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load top rated movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Navigation and Content (similar structure as Popular Movies) -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="flex justify-center mt-4 lg:hidden">
                <template x-for="(chunk, index) in chunks" :key="index">
                    <div 
                        class="w-2 h-2 mx-1 rounded-full transition-all duration-200" 
                        :class="currentIndex === index ? 'bg-green-600 scale-125' : 'bg-gray-400'"
                    ></div>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- Discover by Genre Header -->
<div 
    x-data="{
        collage: [],
        async init() {
            try {
                const res = await fetch('/api/movies/collage');
                const data = await res.json();
                if (data.success) {
                    this.collage = data.movies
                        .map(movie => movie.poster_path)
                        .filter(Boolean)
                        .slice(0, 24);
                }
            } catch (e) {
                console.error('Failed to load posters:', e);
            }
        }
    }" 
    x-init="init()"
    class="mx-auto w-full mt-16 mb-8"
>
    <div class="relative overflow-hidden">

        <!-- Collage Background -->
        <div class="absolute inset-0 z-0 grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-1 opacity-30 blur-sm">
            <template x-for="(poster, index) in collage" :key="index">
                <img :src="`https://image.tmdb.org/t/p/w500${poster}`" class="w-full h-full object-cover" loading="lazy" />
            </template>
        </div>

        <!-- Header Content -->
        <div class="relative z-20 px-8 py-16 text-center">
            <!-- Title -->
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                Discover by 
                <span class="bg-gradient-to-r from-green-500 to-blue-400 bg-clip-text text-transparent">
                    Genre
                </span>
            </h2>

            <!-- Subtitle -->
            <p class="text-gray-300 text-lg md:text-xl max-w-7xl mx-auto leading-relaxed">
                Explore curated collections of movies tailored to your favorite genres. 
                From heart-pounding action to spine-chilling horror, find your next perfect watch.
            </p>

            <!-- Decorative Elements -->
            <div class="flex justify-center space-x-2 mt-8">
                <div class="w-2 h-2 bg-green-600 rounded-full animate-pulse"></div>
                <div class="w-2 h-2 bg-green-700 rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                <div class="w-2 h-2 bg-green-800 rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
            </div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
    </div>
</div>



<!-- Genre: Action Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="action-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Action
        </h2>
        <div
            x-data="{
                genreId: 28,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Genre: Comedy Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="comedy-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Comedy
        </h2>
        <div
            x-data="{
                genreId: 35,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

        </div>
    </div>
</div>

<!-- Genre: Documentary Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="documentary-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Documentary
        </h2>
        <div
            x-data="{
                genreId: 99,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Genre: History Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="history-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            History
        </h2>
        <div
            x-data="{
                genreId: 36,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Genre: Horror Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="horror-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Horror
        </h2>
        <div
            x-data="{
                genreId: 27,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

        </div>
    </div>
</div>

<!-- Genre: Romance Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="romance-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Romance
        </h2>
        <div
            x-data="{
                genreId: 10749,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

        </div>
    </div>
</div>

<!-- Genre: Mystery Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="mystery-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Mystery
        </h2>
        <div
            x-data="{
                genreId: 9648,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

        </div>
    </div>
</div>

<!-- Genre: Science Fiction Movies -->
<div class="container mx-auto px-4 max-w-7xl mt-8">
    <div class="scifi-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold">
            Sci-fi
        </h2>
        <div
            x-data="{
                genreId: 878,
                movies: [],
                loading: true,
                error: false,
                chunks: [],
                currentIndex: 0,
                touchStartX: 0,
                touchEndX: 0,
                observed: false,
                getChunkSize() {
                    if (window.innerWidth < 640) return 3;
                    else if (window.innerWidth < 1280) return 5;
                    return 6;
                },
                formatVoteAverage(vote) {
                    return (vote * 10).toFixed(0) + '%';
                },
                async loadMovies() {
                    if (this.observed) return;
                    this.observed = true;

                    try {
                        const response = await fetch(`/api/movies/genre/${this.genreId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.movies = data.movies;
                            this.updateChunks();
                            this.loading = false;
                        } else {
                            this.error = true;
                            this.loading = false;
                        }
                    } catch (error) {
                        console.error('Error loading genre movies:', error);
                        this.error = true;
                        this.loading = false;
                    }
                },
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.loadMovies();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });

                    observer.observe(this.$el);

                    window.addEventListener('resize', () => {
                        if (!this.loading && this.movies.length > 0) {
                            this.updateChunks();
                        }
                    });
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
                },
                handleTouchStart(e) {
                    this.touchStartX = e.changedTouches[0].screenX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                },
                handleSwipe() {
                    const threshold = 50;
                    const swipeDistance = this.touchEndX - this.touchStartX;

                    if (swipeDistance > threshold) {
                        this.prev();
                    } else if (swipeDistance < -threshold) {
                        this.next();
                    }
                }
            }"
            class="relative"
        >

            <!-- Skeleton Loader -->
            <div x-show="loading" class="overflow-hidden px-4 py-4">
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <template x-for="i in 6" :key="i">
                        <div class="mt-1">
                            <div class="skeleton h-64 w-full rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="error && !loading" class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">Failed to load Action movies</p>
                <button @click="loadMovies()" class="btn btn-primary mt-4">Try Again</button>
            </div>

            <!-- Movies Carousel -->
            <button 
                @click="prev" 
                x-show="!loading && !error && currentIndex > 0"
                class="hidden lg:flex items-center justify-center absolute -left-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div x-show="!loading && !error" class="overflow-hidden px-4 py-4">
                <div 
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="`transform: translateX(-${currentIndex * 100}%)`"
                    @touchstart="handleTouchStart($event)"
                    @touchend="handleTouchEnd($event)"
                >
                    <template x-for="(chunk, index) in chunks" :key="index">
                        <div class="w-full flex-shrink-0">
                            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                    <div class="mt-1 relative group">
                                        <a :href="`/movies/${movie.id}`">
                                            <img :src="`https://image.tmdb.org/t/p/w500/${movie.poster_path}`"
                                                :alt="movie.title"
                                                class="w-full block transform transition-all duration-300 ease-in-out hover:scale-105 hover:border-2 hover:border-green-600 rounded-lg shadow"
                                                loading="lazy">
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <button 
                @click="next" 
                x-show="!loading && !error && currentIndex < chunks.length - 1"
                class="hidden lg:flex items-center justify-center absolute -right-5 top-1/2 transform -translate-y-1/2 z-10 
                    btn btn-circle btn-outline shadow-lg hover:scale-105 transition duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 md:pt-20 lg:pt-24 max-w-3xl md:max-w-5xl lg:max-w-7xl lg:ml-28"></div>

@endsection