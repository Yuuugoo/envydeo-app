@extends('layouts.main')

@section('content')
<div class="movie-info border-b border-gray-800">
    <div class="relative lg:-mt-28 lg:h-screen lg:max-h-[38rem] h-full">
        <div class="w-full pb-48 md:pb-0 lg:pb-0 md:h-full relative">
            <img src="{{ 'https://image.tmdb.org/t/p/original/' . $movie['backdrop_path'] }}"
                alt="{{ $movie['title'] }}"
                class="w-full h-full object-cover object-center">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
        
        <div class="absolute max-w-xl bottom-0 left-6 mt-64 md:mt-72 md:bottom-16 md:max-w-2xl lg:mt-0 lg:bottom-8 lg:left-28 text-white space-y-4 z-10">
            <span class="bg-gray-900 px-3 py-1 rounded-full text-xs font-semibold">{{ $selectedMovieDetails['media_type']}}</span>
            <h2 class="text-xl mt-3 md:text-4xl font-bold mb-2 md:mb-4 hover:text-green-600">{{ $movie['title'] }}</h2>
            <p class="text-xs md:text-sm text-gray-300">
                {{ floor($movie['runtime'] / 60) }}h {{ $movie['runtime'] % 60 }}m •
                {{ \Carbon\Carbon::parse($movie['release_date'])->format('Y') }} •
                {{ implode(' • ', $selectedMovieDetails['genres']) }}
            </p>
            
            <div class="flex flex-col md:flex-row gap-4 mt-4">
                <div x-data="{ 
                    isOpen: false,
                    movieId: {{ $movie['id'] }},
                    userId: '{{ auth()->id() }}',
                    csrfToken: '{{ csrf_token() }}',
                    lastProgressUpdate: 0,
                    progressUpdateThrottle: 5000,
                    resumeTimestamp: 0,
                    
                    async fetchWatchProgress() {
                        if (!this.userId) {
                            return;
                        }
                        
                        try {
                            const response = await fetch('/watch-progress', {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            
                            if (!response.ok) {
                                return;
                            }
                            
                            const result = await response.json();
                            
                            if (result.success && result.data) {
                                const movieProgress = result.data.find(progress => 
                                    progress.tmdb_id == this.movieId && progress.media_type === 'movie'
                                );

                                if (movieProgress) {
                                    if (movieProgress.watched && movieProgress.duration && 
                                        movieProgress.watched > 30 && 
                                        movieProgress.watched < (movieProgress.duration * 0.9)) {
                                        this.resumeTimestamp = Math.floor(movieProgress.watched);
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('Error fetching watch progress:', error);
                        }
                    },
                    
                    initProgressTracking() {
                        if (!this.userId) {
                            return;
                        }
                        
                        window.addEventListener('message', ({ origin, data }) => {
                            if (origin !== 'https://vidfast.pro' || !data) {
                                return;
                            }

                            if (data.type === 'PLAYER_EVENT') {
                                this.handlePlayerEvent(data.data);
                            }
                        });
                    },
                    
                    handlePlayerEvent(eventData) {
                        const { event, currentTime, duration, tmdbId } = eventData;
                                                
                        const shouldSave = event === 'pause' || 
                                        event === 'ended' || 
                                        event === 'seeked' || 
                                        (event === 'timeupdate' && Date.now() - this.lastProgressUpdate > this.progressUpdateThrottle);
                        
                        if (shouldSave && duration > 0 && currentTime > 0) {
                            this.saveWatchProgress(tmdbId, currentTime, duration);
                            this.lastProgressUpdate = Date.now();
                        }
                    },
                    
                    async saveWatchProgress(tmdbId, watched, duration) {
                        try {
                            const response = await fetch('/watch-progress', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify({
                                    tmdb_id: tmdbId,
                                    media_type: 'movie',
                                    watched: watched,
                                    duration: duration
                                })
                            });

                        } catch (error) {
                            console.error('Error saving watch progress:', error);
                        }
                    },
                    
                    async openModal() {
                        this.isOpen = true;
                        
                        this.$nextTick(() => {
                            this.initProgressTracking();
                        });
                        
                        if (this.userId) {
                            this.fetchWatchProgress();
                        }
                    },
                    
                    closeModal() {
                        this.isOpen = false;
                        this.resumeTimestamp = 0; // Reset for next time
                    },
                    
                    getIframeUrl() {
                        let url = `https://vidfast.pro/movie/${this.movieId}?theme=16A085`;
                        if (this.resumeTimestamp > 0 && this.userId) {
                            url += `&t=${this.resumeTimestamp}`;
                        }
                        return url;
                    }
                }">
                    <button
                        @click="openModal"
                        class="btn gap-2 w-full md:w-auto bg-green-700 hover:bg-green-800 text-white md:px-6 md:py-2 rounded-box font-semibold"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm14.024-.983a1.125 1.125 0 0 1 0 1.966l-5.603 3.113A1.125 1.125 0 0 1 9 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113Z" clip-rule="evenodd" />
                        </svg>
                        Play Now
                    </button>
                
                    <template x-if="isOpen">
                        <div
                            style="background-color: rgba(0, 0, 0, .5);"
                            class="fixed top-0 left-0 w-full h-full flex items-center shadow-lg overflow-y-auto z-50"
                        >
                            <div class="container mx-auto lg:px-32 rounded-lg overflow-y-auto">
                                <div class="bg-gray-900 rounded">
                                    <div class="flex justify-end pr-4 pt-2">
                                        <button
                                            @click="closeModal()"
                                            @keydown.escape.window="closeModal()"
                                            class="text-3xl leading-none hover:text-gray-300">&times;
                                        </button>
                                    </div>
                                    <div class="modal-body px-8 py-8">
                                        <div class="responsive-container overflow-hidden relative pt-96">
                                            <iframe 
                                                class="responsive-iframe absolute top-0 left-0 w-full h-full" 
                                                :src="getIframeUrl()" 
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
                
                <div x-data="{ isOpen: false }">
                    <button
                        @click="isOpen = true"
                        class="btn gap-2 w-full md:w-auto bg-white/10 hover:bg-white/20 text-white md:px-6 md:py-2 rounded-box"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                        </svg>
                        Watch Trailer
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
                                            @if(isset($movie['videos']['results'][0]['key']))
                                                <iframe class="responsive-iframe absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/{{ $movie['videos']['results'][0]['key'] }}" style="border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>
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

                <livewire:add-watchlist :tmdb-id="$movie['id']" />
            </div>
        </div>
    </div>
</div>

<div class="mx-6 md:mx-auto max-w-xl md:max-w-3xl lg:max-w-7xl py-8 md:py-10">
    <div class="md:flex-row items-center md:items-start gap-6">
        <h3 class="text-white font-semibold text-center md:text-left">Storyline</h3>
        <p class="text-gray-300 mt-4 text-sm md:text-base">
            {{ $movie['overview'] }}
        </p>
        <div class="mt-8 md:mt-12">
            <h4 class="text-white font-semibold text-center md:text-left">Top Cast</h4>
            <div class="flex flex-wrap justify-center md:justify-start mt-4 gap-6">
                @foreach($movie['credits']['cast'] as $cast)
                    @if($loop->index < 5)
                        <div class="flex items-center space-x-2">
                            @php
                                $avatar = $cast['profile_path']
                                    ? 'https://image.tmdb.org/t/p/w500/' . $cast['profile_path']
                                    : '/img/avatars/avatar_female.jpg';
                            @endphp
                            <img src="{{ $avatar }}"
                            alt="{{ $cast['name'] }}"
                            class="avatar rounded-full w-8 h-8 md:block md:w-12 md:h-12 object-cover" />
                            <div>
                                <div class="text-sm md:text-base">{{ $cast['name'] }}</div>
                                <div class="text-xs md:text-sm text-gray-400">{{ $cast['character'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

<div x-data="{ 
    activeTab: 'similar',
    similarMovies: [],
    recommendedMovies: [],
    movieImages: [],
    genres: {},
    loading: false,
    
    async loadSimilarMovies() {
        if (this.similarMovies.length > 0) return;
        this.loading = true;
        try {
            const response = await fetch('/movies/{{ $movie['id'] }}/similar');
            const data = await response.json();
            this.similarMovies = data.similarMovie;
            this.genres = data.genres;
        } catch (error) {
            console.error('Error loading similar movies:', error);
        } finally {
            this.loading = false;
        }
    },
    
    async loadRecommendedMovies() {
        if (this.recommendedMovies.length > 0) return;
        this.loading = true;
        try {
            const response = await fetch('/movies/{{ $movie['id'] }}/recommended');
            const data = await response.json();
            this.recommendedMovies = data.recommendedMovie;
            this.genres = data.genres;
        } catch (error) {
            console.error('Error loading recommended movies:', error);
        } finally {
            this.loading = false;
        }
    },
    
    async loadMovieImages() {
        if (this.movieImages.length > 0) return;
        this.loading = true;
        try {
            const response = await fetch('/movies/{{ $movie['id'] }}/images');
            const data = await response.json();
            this.movieImages = data.backdrops || [];
        } catch (error) {
            console.error('Error loading movie images:', error);
        } finally {
            this.loading = false;
        }
    },
    
    async switchTab(tab) {
        this.activeTab = tab;
        if (tab === 'similar') {
            await this.loadSimilarMovies();
            await this.loadRecommendedMovies();
        } else if (tab === 'images') {
            await this.loadMovieImages();
        }
    },
    
    init() {
        // Load similar and recommended movies immediately since 'similar' is the default tab
        this.loadSimilarMovies();
        this.loadRecommendedMovies();
    }
}">
    <!-- Tab navigation -->
    <div class="max-w-xl mx-6 md:max-w-3xl lg:max-w-7xl md:mx-auto py-5">
        <div role="tablist" class="flex space-x-6">
            <a role="tab" 
                @click.prevent="switchTab('similar')" 
                :class="{ 'border-b-2 border-teal-500 pb-2': activeTab === 'similar', 'text-gray-400': activeTab !== 'similar' }"
                class="cursor-pointer text-sm font-semibold transition duration-150 text-white">
                Recommendations
            </a>
            <a role="tab" 
                @click.prevent="switchTab('images')" 
                :class="{ 'border-b-2 border-teal-500 pb-2': activeTab === 'images', 'text-gray-400': activeTab !== 'images' }"
                class="cursor-pointer text-sm font-semibold transition duration-150 text-white">
                Images
            </a>
        </div>
    </div>

    <div class="mx-6 md:max-w-3xl lg:max-w-7xl md:mx-auto py-5">
        <div x-show="activeTab === 'similar'" class="tab-panel">
            <div x-show="loading" class="text-center py-8">
                <div class="text-white">Loading Recommendations <span class="loading loading-spinner loading-lg text-success"></span></div>
            </div>
            
            <div x-show="!loading">
                <h2 class="text-2xl font-bold text-white">More Like This</h2>
                <div class="container mx-auto max-w-7xl">
                    <div class="similar-movies" x-data="{ 
                        currentIndex: 0,
                        get movies() { return similarMovies; },
                        get filteredMovies() { return this.movies.filter(movie => movie.backdrop_path); },
                        getChunkSize() {
                            if (window.innerWidth < 640) return 1;
                            if (window.innerWidth < 835) return 3;
                            return 5;
                        },
                        get chunks() {
                            const chunkSize = this.getChunkSize();
                            const chunks = [];
                            for (let i = 0; i < this.filteredMovies.length; i += chunkSize) {
                                chunks.push(this.filteredMovies.slice(i, i + chunkSize));
                            }
                            return chunks;
                        },
                        formatVoteAverage(vote) {
                            return (vote * 10).toFixed(0) + '%';
                        },
                        init() {
                            window.addEventListener('resize', () => {
                                if (this.currentIndex >= this.chunks.length) {
                                    this.currentIndex = Math.max(0, this.chunks.length - 1);
                                }
                            });
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
                    }">
                        <div class="relative">
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
                                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                                    <div class="mt-4 md:mt-8">
                                                        <div class="flex flex-col">
                                                            <a :href="`/movies/${movie.id}`">
                                                                <img :src="`https://image.tmdb.org/t/p/w500/${movie.backdrop_path}`"
                                                                    :alt="movie.title"
                                                                    class="w-full block hover:opacity-75 transition ease-in-out duration-150 rounded-lg shadow">
                                                            </a>
                                                            <div class="text-white p-2 rounded-b-lg">
                                                                <a :href="`/movies/${movie.id}`" class="text-base md:text-lg mt-1 hover:text-gray-300 line-clamp-1" x-text="movie.title"></a>
                                                                <div class="flex items-center text-xs md:text-sm">
                                                                    <span>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-yellow-400">
                                                                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </span>
                                                                    <span class="ml-1 font-semibold" x-text="formatVoteAverage(movie.vote_average)"></span>
                                                                    <span class="mx-1">|</span>
                                                                    <div class="text-xs mt-1 font-medium text-gray-500 line-clamp-1">
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

                    <h2 class="text-2xl font-bold mt-8 text-white">You Might Also Like</h2>
                    <div class="recommended-movies" x-data="{ 
                        currentIndex: 0,
                        get movies() { return recommendedMovies; },
                        get filteredMovies() { return this.movies.filter(movie => movie.backdrop_path); },
                        getChunkSize() {
                            if (window.innerWidth < 640) return 1;
                            if (window.innerWidth < 835) return 3;
                            return 5;
                        },
                        get chunks() {
                            const chunkSize = this.getChunkSize();
                            const chunks = [];
                            for (let i = 0; i < this.filteredMovies.length; i += chunkSize) {
                                chunks.push(this.filteredMovies.slice(i, i + chunkSize));
                            }
                            return chunks;
                        },
                        formatVoteAverage(vote) {
                            return (vote * 10).toFixed(0) + '%';
                        },
                        init() {
                            window.addEventListener('resize', () => {
                                if (this.currentIndex >= this.chunks.length) {
                                    this.currentIndex = Math.max(0, this.chunks.length - 1);
                                }
                            });
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
                    }">
                        <div class="relative">
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
                                                <template x-for="(movie, movieIndex) in chunk" :key="movie.id">
                                                    <div class="mt-4 md:mt-8">
                                                        <div class="flex flex-col">
                                                            <a :href="`/movies/${movie.id}`">
                                                                <img :src="`https://image.tmdb.org/t/p/w500/${movie.backdrop_path}`"
                                                                    :alt="movie.title"
                                                                    class="w-full block hover:opacity-75 transition ease-in-out duration-150 rounded-lg shadow">
                                                            </a>
                                                            <div class="text-white p-2 rounded-b-lg">
                                                                <a :href="`/movies/${movie.id}`" class="text-base md:text-lg mt-1 hover:text-gray-300 line-clamp-1" x-text="movie.title"></a>
                                                                <div class="flex items-center text-xs md:text-sm">
                                                                    <span>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-yellow-400">
                                                                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </span>
                                                                    <span class="ml-1 font-semibold" x-text="formatVoteAverage(movie.vote_average)"></span>
                                                                    <span class="mx-1">|</span>
                                                                    <div class="text-xs mt-1 font-medium text-gray-500 line-clamp-1">
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
        </div>

        <!-- Images Tab with Modal -->
        <div x-show="activeTab === 'images'" class="tab-panel" x-data="{ isOpen: false, image: '' }">
            <div x-show="loading" class="text-center py-8">
                <div class="text-white">Loading Images <span class="loading loading-spinner loading-lg text-success"></span></div>
            </div>
            
            <div x-show="!loading" class="movie-images">
                <div class="container mx-auto max-w-7xl">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                        <template x-for="(image, index) in movieImages.slice(0, 15)" :key="image.file_path">
                            <div class="mt-8">
                                <a
                                    @click.prevent="
                                        isOpen = true;
                                        image = `https://image.tmdb.org/t/p/original/${image.file_path}`;
                                    "
                                    href="#"
                                >
                                    <img :src="`https://image.tmdb.org/t/p/w500/${image.file_path}`" alt="Movie Image" class="hover:opacity-75 transition ease-in-out duration-150">
                                </a>
                            </div>
                        </template>
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

@endsection
