<div class="contents">
    @auth
        <button type="button" wire:click="toggleWatchlist" wire:loading.attr="disabled"
                class="{{ $isInWatchlist ? 'btn-glass !bg-brand-600/20 !text-brand-200 !ring-brand-400/40' : 'btn-glass' }} w-full sm:w-auto"
                aria-pressed="{{ $isInWatchlist ? 'true' : 'false' }}">
            <x-icon :name="$isInWatchlist ? 'bookmark-solid' : 'bookmark'" class="size-5" />
            <span>{{ $isInWatchlist ? 'In Watchlist' : 'Watchlist' }}</span>
        </button>
    @else
        <a href="{{ route('login') }}" class="btn-glass w-full sm:w-auto" title="Sign in to add to your watchlist">
            <x-icon name="bookmark" class="size-5" />
            <span>Watchlist</span>
        </a>
    @endauth
</div>
