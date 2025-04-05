<div class="mt-8 relative group">
    <a href="{{ route('movie.show', $movie['id']) }}">
        <img src="{{ 'https://image.tmdb.org/t/p/w500/' .$movie['poster_path'] }}" alt="movie_poster" class="w-full block hover:opacity-75 transition ease-in-out duration-150">
    </a>
    <div class="absolute bottom-0 left-0 w-full bg-black bg-opacity-60 text-white p-2 opacity-0 group-hover:opacity-100 transition-opacity">
        <a href="{{ route('movie.show', $movie['id']) }}" class="text-lg mt-2 hover:text-gray-300">{{ $movie['title'] }} </a>
        <div class="flex items-center text-sm">
            <span>⭐</span>
            <span class="ml-1">{{ $movie['vote_average'] * 10 . '%'}}</span>
            <span class="mx-2">•</span>
            <span>{{ \Carbon\Carbon::parse($movie['release_date'])->format('M d, Y') }}</span>
        </div>
        <div class="text-xs mt-1 text-gray-300">
            @foreach($movie['genre_ids'] as $genre)
                {{ $genres->get($genre) }}@if (!$loop->last), @endif
            @endforeach
        </div>
    </div>
</div>