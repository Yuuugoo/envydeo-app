<div>
    @if(!auth()->check())
        <div class="hidden lg:block tooltip tooltip-bottom" data-tip="Please login to add to watchlist">
            <button 
                wire:click="toggleWatchlist" 
                class="btn {{ $isInWatchlist ? 'btn-success' : 'btn-outline' }} w-full md:w-auto flex items-center gap-2 
                    {{ $isInWatchlist ? 'bg-green-600/20 hover:bg-green-600/30 text-green-400 border-green-400/50' : 'bg-white/10 hover:bg-white/20 text-white border-white/20' }} 
                    md:px-6 py-2 rounded-box backdrop-blur transition duration-200"
                disabled
            >
                @if($isInWatchlist)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
                    </svg>
                    In Watchlist
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg>
                    Add to Watchlist
                @endif
            </button>
        </div>
        
        <div class="lg:hidden flex flex-col">
            <button 
                wire:click="toggleWatchlist" 
                class="btn {{ $isInWatchlist ? 'btn-success' : 'btn-outline' }} w-full flex items-center gap-2 
                    {{ $isInWatchlist ? 'bg-green-600/20 hover:bg-green-600/30 text-green-400 border-green-400/50' : 'bg-white/10 hover:bg-white/20 text-white border-white/20' }} 
                    py-2 rounded-box backdrop-blur transition duration-200"
                disabled
            >
                @if($isInWatchlist)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
                    </svg>
                    In Watchlist
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg>
                    Add to Watchlist
                @endif
            </button>
            <p class="text-xs text-center mt-1 text-gray-400">Please login to add to watchlist</p>
        </div>
    @else
        <button 
            wire:click="toggleWatchlist" 
            class="btn {{ $isInWatchlist ? 'btn-success' : 'btn-outline' }} w-full md:w-auto flex items-center gap-2 
                {{ $isInWatchlist ? 'bg-green-600/20 hover:bg-green-600/30 text-green-400 border-green-400/50' : 'bg-white/10 hover:bg-white/20 text-white border-white/20' }} 
                md:px-6 py-2 rounded-box backdrop-blur transition duration-200"
        >
            @if($isInWatchlist)
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                    <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
                </svg>
                In Watchlist
            @else
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                </svg>
                Add to Watchlist
            @endif
        </button>
    @endif
</div>