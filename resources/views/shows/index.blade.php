@extends('layouts.main')

@section('content')

<div class="container mx-auto px-4 md:pt-36 mb-10 max-w-3xl md:max-w-5xl lg:max-w-7xl lg:ml-28">
    <div class="latest-movies">
        <h2 class="uppercase tracking-wider text-green-600 text-lg font-semibold mb-4">
            Latest TV Shows
        </h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
        @foreach($popularTV['results'] as $show)
            <x-show-card :show="$show" :genres="$genres" />
        @endforeach
    </div>

    <div class="flex justify-center mt-10">
        <div class="join shadow-md rounded-md">
            @if($currentPage > 1)
                <a href="{{ route('show.list', ['page' => 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">«</a>
                <a href="{{ route('show.list', ['page' => $currentPage - 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">‹</a>
            @endif
            
            @php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
            @endphp
            
            @for ($i = $startPage; $i <= $endPage; $i++)
                <a href="{{ route('show.list', ['page' => $i]) }}" 
                   class="join-item btn {{ $i == $currentPage ? 'bg-green-500 hover:bg-green-600 text-white border-green-600' : 'bg-gray-800 hover:bg-gray-700 text-white border-gray-700' }}">
                    {{ $i }}
                </a>
            @endfor
            
            @if($currentPage < $totalPages)
                <a href="{{ route('show.list', ['page' => $currentPage + 1]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">›</a>
                <a href="{{ route('show.list', ['page' => $totalPages]) }}" class="join-item btn bg-gray-800 hover:bg-gray-700 text-white border-gray-700">»</a>
            @endif
        </div>
    </div>
</div>





<!-- <h1 class="text-4xl font-bold text-white mb-4">Site Under Construction</h1>
        <p class="text-xl text-white mb-8 max-w-lg">
            TV show is currently in development. <br>Please check back soon!
        </p> -->

<!-- <div class="mb-5">
            <img src="https://tenor.com/view/oiia-oiiaoiia-oiia-oiia-cat-gif-13397875673260883779.gif"
                alt="uiia"
                class="w-32 h-32 animate-spin-slow rounded-2xl" />
        </div>

         <div class="mb-5">
            <img src="https://media.giphy.com/media/sIIhZliB2McAo/giphy.gif"
                alt="Nyan Cat"
                class="w-32 h-32 animate-spin-slow rounded-2xl" />
        </div> --> 

@endsection

