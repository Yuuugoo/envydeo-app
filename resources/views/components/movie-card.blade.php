<div class="mt-8 relative group">
    <a href="{{ route('movie.show', $movie['id']) }}">
        <img src="{{ 'https://image.tmdb.org/t/p/w500/' .$movie['poster_path'] }}" alt="movie_poster" class="w-42 md:w-full block md:hover:opacity-75 transition ease-in-out duration-150">
    </a>
    <div class="absolute bottom-0 left-0 w-full bg-black bg-opacity-60 text-white p-2 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
        <a href="{{ route('movie.show', $movie['id']) }}" class="text-md md:text-lg mt-2 lg:hover:text-gray-300">{{ $movie['title'] }} </a>
        <div class="flex items-center text-xs md:text-sm">
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-yellow-400">
                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                </svg>
            </span>
            <span class="ml-1">{{ $movie['vote_average'] * 10 . '%'}}</span>
            <span class="mx-2">•</span>
            <span class="hidden md:block">{{ \Carbon\Carbon::parse($movie['release_date'])->format('M d, Y') }}</span>
        </div>
        <div class="text-xs mt-1 text-gray-300">
            @if(isset($movie['genre_ids']) && isset($genres))
                {{-- Format from movie list endpoints --}}
                @foreach($movie['genre_ids'] as $genreId)
                    {{ $genres->get($genreId) }}@if (!$loop->last), @endif
                @endforeach
            @elseif(isset($movie['genres']))
                {{-- Format from movie detail endpoint (like in watchlist) --}}
                @foreach($movie['genres'] as $genre)
                    {{ $genre['name'] }}@if (!$loop->last), @endif
                @endforeach
            @endif
        </div>

    </div>
</div>