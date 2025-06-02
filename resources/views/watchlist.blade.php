@extends('layouts.main')

@section('content')

<div class="container max-w-7xl lg:ml-28 mx-auto px-4 md:pt-36 mb-10 min-h-screen">
    <div class="latest-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold mb-4">
            Your Watchlist
        </h2>
    </div>

    @if(count($movies) > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
            @foreach($movies as $movie)
                <x-movie-card :movie="$movie" />
            @endforeach
        </div>
    @else
        <div class="text-white text-lg">
            Your watchlist is empty. Start adding movies!
        </div>
    @endif


    @if(isset($currentPage) && isset($totalPages) && $totalPages > 1)
        <div class="flex justify-center mt-10">
            <div class="join shadow-md rounded-md">
                @if($currentPage > 1)
                    <a href="?page=1" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">«</a>
                    <a href="?page={{ $currentPage - 1 }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">‹</a>
                @endif

                @for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                    <a href="?page={{ $i }}" class="join-item btn {{ $i == $currentPage ? 'bg-green-500 hover:bg-green-600 text-white border-green-600' : 'bg-gray-800 hover:bg-gray-700 text-white border-gray-700' }}">
                        {{ $i }}
                    </a>
                @endfor

                @if($currentPage < $totalPages)
                    <a href="?page={{ $currentPage + 1 }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">›</a>
                    <a href="?page={{ $totalPages }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">»</a>
                @endif
            </div>
        </div>
    @endif


</div>

@endsection
