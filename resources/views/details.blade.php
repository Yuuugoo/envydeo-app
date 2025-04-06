@extends('layouts.main')

@section('content')
    <div class="movie-info border-b border-gray-800">
        <div class="container mx-auto px-4 py-8 md:py-16">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                
                <img src="{{ 'https://image.tmdb.org/t/p/w500/' . $movie['poster_path'] }}"
                    alt="{{ $movie['title'] }}"
                    class="w-2/3 sm:w-1/2 md:w-1/3 lg:w-1/4 rounded-lg shadow-lg">

                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-2xl md:text-4xl font-bold mb-2 md:mb-4">{{ $movie['title'] }}</h2>

                    <div class="flex flex-wrap justify-center md:justify-start items-center text-sm gap-y-1 gap-x-3">
                        <div class="flex items-center">
                            <span>⭐</span>
                            <span class="ml-1">{{ $movie['vote_average'] * 10 . '%'}}</span>
                        </div>
                        <div>{{ \Carbon\Carbon::parse($movie['release_date'])->format('M d, Y') }}</div>
                        <div>
                            @foreach($movie['genres'] as $genre)
                                {{ $genre['name'] }}@if (!$loop->last), @endif
                            @endforeach
                        </div>
                    </div>

                    <p class="text-gray-300 mt-4 text-sm md:text-base">
                        {{ $movie['overview'] }}
                    </p>

                    <div class="mt-8 md:mt-12">
                        <h4 class="text-white font-semibold text-center md:text-left">Featured Cast</h4>
                        <div class="flex flex-wrap justify-center md:justify-start mt-4 gap-4">
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
                                        class="rounded-2xl hidden md:block md:w-12 md:h-12 object-cover ring-2 ring-gray-700 shadow-md" />
                                        <div>
                                            <div class="text-sm md:text-base">{{ $cast['name'] }}</div>
                                            <div class="text-xs md:text-sm text-gray-400">{{ $cast['character'] }}</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-12">
                        <h4 class="text-white font-semibold lg:block sm:hidden">Featured Crew</h4>
                    </div>
                    <div class="flex mt-4">
                        @foreach($movie['credits']['crew'] as $crew)
                            @if($loop->index < 2)
                                <div class="mr-8">
                                    <div>{{ $crew['name'] }}</div>
                                    <div class="text-sm text-gray-400">{{ $crew['department'] }}, {{ $crew['job'] }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex flex-col md:flex-row gap-3 md:gap-5">
                        <div x-data="{ isOpen: false }" class="modal-container">
                            @if (count($movie['videos']['results']) > 0)
                                <div class="mt-4 md:mt-12">
                                    <button
                                        @click="isOpen = true"
                                        class="w-full md:w-auto flex items-center justify-center bg-orange-500 text-gray-900 rounded font-semibold px-4 py-3 md:px-5 md:py-4 hover:bg-orange-600 transition ease-in-out duration-150 text-sm md:text-base"
                                    >
                                        <svg class="w-5 md:w-6 fill-current" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                                        <span class="ml-2">Play Trailer</span>
                                    </button>
                                </div>

                                <div
                                    x-show="isOpen"
                                    x-cloak
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
                                                <div class="responsive-container overflow-hidden relative" style="padding-top: 56.25%">
                                                    <iframe class="responsive-iframe absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/{{ $movie['videos']['results'][0]['key'] }}" style="border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div x-data="{ isOpen: false }">
                            @if (count($movie['videos']['results']) > 0)
                                <div class="mt-4 md:mt-12">
                                    <button
                                        @click="isOpen = true"
                                        class="w-full md:w-auto flex items-center justify-center bg-green-500 text-gray-900 rounded font-semibold px-4 py-3 md:px-5 md:py-4 hover:bg-green-600 transition ease-in-out duration-150 text-sm md:text-base"
                                    >
                                        <svg class="w-5 md:w-6 fill-current" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                                        <span class="ml-2">Play Movie (Server 1)</span>
                                    </button>
                                </div>

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
                                                    <div class="responsive-container overflow-hidden relative" style="padding-top: 56.25%">
                                                        <iframe class="responsive-iframe absolute top-0 left-0 w-full h-full" src="https://vidfast.pro/movie/{{ $movie['imdb_id'] }}" style="border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="movie-images" x-data="{ isOpen: false, image: ''}">
        <div class="container mx-auto px-4 py-16">
            <h2 class="text-4xl font-semibold">Images</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                @foreach ($movie['images']['backdrops'] as $image)
                    <div class="mt-8">
                        <a
                            @click.prevent="
                                isOpen = true
                                image='{{ 'https://image.tmdb.org/t/p/original/'.$image['file_path'] }}'
                            "
                            href="#"
                        >
                            <img src="{{ 'https://image.tmdb.org/t/p/w500/'.$image['file_path'] }}" alt="image1" class="hover:opacity-75 transition ease-in-out duration-150">
                        </a>
                    </div>
                @endforeach
            </div>

            <div
                style="background-color: rgba(0, 0, 0, .5);"
                class="fixed top-0 left-0 w-full h-full flex items-center shadow-lg overflow-y-auto"
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
    </div
@endsection