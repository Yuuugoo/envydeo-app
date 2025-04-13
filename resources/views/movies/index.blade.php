@extends('layouts.main')

@section('content')

<div class="container max-w-7xl ml-28 mx-auto px-4 md:pt-36 mb-10">
    <div class="latest-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold mb-4">
            Latest Movies
        </h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
        @foreach($nowPlayingMovies as $movie)
            <x-movie-card :movie="$movie" :genres="$genres" />
        @endforeach
    </div>

    <div class="flex justify-center mt-10">
        <div class="join shadow-md rounded-md">
            @if($currentPage > 1)
                <a href="{{ route('movie.list', ['page' => 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">«</a>
                <a href="{{ route('movie.list', ['page' => $currentPage - 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">‹</a>
            @endif
            
            @php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
            @endphp
            
            @for ($i = $startPage; $i <= $endPage; $i++)
                <a href="{{ route('movie.list', ['page' => $i]) }}" 
                   class="join-item btn {{ $i == $currentPage ? 'bg-green-500 hover:bg-green-600 text-white border-green-600' : 'bg-gray-800 hover:bg-gray-700 text-white border-gray-700' }}">
                    {{ $i }}
                </a>
            @endfor
            
            @if($currentPage < $totalPages)
                <a href="{{ route('movie.list', ['page' => $currentPage + 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">›</a>
                <a href="{{ route('movie.list', ['page' => $totalPages]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">»</a>
            @endif
        </div>
    </div>
</div>
@endsection