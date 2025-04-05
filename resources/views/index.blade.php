@extends('layouts.main')

@section('content')
    <!-- Popular Movies Section -->
    <div class="container mx-auto px-4 pt-16">
        <div class="popular-movies">
            <h2 class="uppercase tracking-wider text-orange-500 text-lg font-semibold mb-4">
                Popular Movies
            </h2>
            <div x-data="{ scroll: $refs.carousel }" class="relative">

                <button @click="scroll.scrollBy({ left: -scroll.offsetWidth, behavior: 'smooth' })"
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <div x-ref="carousel" class="carousel flex overflow-x-auto space-x-4 scroll-smooth px-4">
                    @foreach(collect($popularMovies)->chunk(5) as $chunk)
                        <div class="carousel-item flex-none w-full">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                @foreach($chunk as $movie)
                                    <x-movie-card :movie="$movie" :genres="$genres" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <button @click="scroll.scrollBy({ left: scroll.offsetWidth, behavior: 'smooth' })"
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Top Rated Movies Section -->

    <div class="container mx-auto px-4 pt-16">
        <div class="top-rated-movies">
            <h2 class="uppercase tracking-wider text-orange-500 text-lg font-semibold mb-4">
                Top Rated Movies
            </h2>
            <div x-data="{ scroll: $refs.carousel }" class="relative">

                <button @click="scroll.scrollBy({ left: -scroll.offsetWidth, behavior: 'smooth' })"
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <div x-ref="carousel" class="carousel flex overflow-x-auto space-x-4 scroll-smooth px-4">
                    @foreach(collect($topRatedMovies)->chunk(5) as $chunk)
                        <div class="carousel-item flex-none w-full">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                @foreach($chunk as $movie)
                                    <x-movie-card :movie="$movie" :genres="$genres" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <button @click="scroll.scrollBy({ left: scroll.offsetWidth, behavior: 'smooth' })"
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-gray-900/80 text-white p-2 rounded-full hover:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 pt-16 mt-5"></div>
@endsection