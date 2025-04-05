@extends('layouts.main')

@section('content')
    <div class="movie-info border-b border-gray-800">
        <div class="container mx-auto px-4 py-16">
            <div class="flex items-start">
                <img src="{{ 'https://image.tmdb.org/t/p/w500/' .$movie['poster_path'] }}" alt="series" class="w-96">

                <div class="ml-12 max-w-5xl">
                    <h2 class="text-4xl font-bold mb-4">{{ $movie['title'] }}</h2>
                    <div class="flex items-center text-sm">
                        <span>⭐</span>
                        <span class="ml-1">{{ $movie['vote_average'] * 10 . '%'}}</span>
                        <span class="mx-2">|</span>
                        <span>{{ \Carbon\Carbon::parse($movie['release_date'])->format('M d, Y') }}</span>
                        <span class="mx-2">|</span>
                        <span>
                        @foreach($movie['genres'] as $genre)
                            {{ $genre['name'] }}@if (!$loop->last), @endif
                        @endforeach
                        </span>
                    </div>

                    <p class="text-gray-300 mt-8">
                        {{ $movie['overview'] }}
                    </p>

                    <div class="mt-12">
                        <h4 class="text-white font-semibold">Featured Cast</h4>
                    </div>
                    <div class="flex mt-4">
                        @foreach($movie['credits']['cast'] as $cast)
                            @if($loop->index < 5)
                                <div class="mr-8">
                                    <div class="flex items-center space-x-2">
                                        @php
                                            $avatar = $cast['profile_path']
                                                ? 'https://image.tmdb.org/t/p/w500/' . $cast['profile_path']
                                                : '/img/avatars/avatar_female.jpg';
                                        @endphp
                                        <img src="{{ $avatar }}"
                                        alt="{{ $cast['name'] }}"
                                        class="rounded-full w-12 h-12 object-cover ring-2 ring-gray-700 shadow-md" />
                                        <div>
                                            <div>{{ $cast['name'] }}</div>
                                            <div class="text-sm text-gray-400">{{ $cast['character'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-12">
                        <h4 class="text-white font-semibold">Featured Crew</h4>
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
                        
                    @if (count($movie['videos']['results']) > 0)
                        <div class="mt-12">
                            <a href="https://www.youtube.com/watch?v={{ $movie['videos']['results']['0']['key'] }}" target="_blank" rel="noopener noreferrer" 
                            class="inline-flex items-center bg-orange-500 text-gray-900 rounded font-semibold px-5 py-4  hover:bg-orange-600 transition ease-in-out duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                                </svg>
                                <span class="ml-2">Play Trailer</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="movie-images border-b border-gray-800">
        <div class="container mx-auto px-4 py-16">
            <h2 class="text-4xl font-semibold">Images</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                    @foreach($movie['images']['backdrops'] as $image)
                        @if($loop->index < 9)
                            <div class="mt-8 relative group">
                                <a href="#">
                                    <img src="{{ 'https://image.tmdb.org/t/p/w500/'.$image['file_path'] }}" alt="series" class="w-full block hover:opacity-75 transition ease-in-out duration-150">
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection