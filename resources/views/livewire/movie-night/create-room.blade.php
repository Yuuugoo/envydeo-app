@php
    $chip = 'block cursor-pointer select-none rounded-full px-3.5 py-2 text-sm font-semibold text-gray-300 ring-1 ring-white/10 transition hover:bg-white/5 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-500 peer-focus-visible:outline-2 peer-focus-visible:outline-brand-400';
@endphp

<form wire:submit="create" class="mt-6 space-y-7">
    {{-- Name --}}
    <div>
        <x-input-label for="mn-name" value="Your name" />
        <x-text-input id="mn-name" wire:model="name" placeholder="So friends know who’s who" maxlength="30" autocomplete="nickname" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    {{-- Deck source --}}
    @auth
        <fieldset>
            <legend class="text-sm font-semibold text-gray-200">Deck</legend>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <label>
                    <input type="radio" value="discover" wire:model.live="source" class="peer sr-only">
                    <span class="flex h-full cursor-pointer flex-col rounded-2xl p-3 ring-1 ring-white/10 transition hover:bg-white/5 peer-checked:bg-brand-600/20 peer-checked:ring-brand-400 peer-focus-visible:outline-2 peer-focus-visible:outline-brand-400">
                        <span class="flex items-center gap-2 text-sm font-bold text-white"><x-icon name="sparkles" class="size-4" /> Discover</span>
                        <span class="mt-1 text-xs text-gray-400">Fresh picks from your filters</span>
                    </span>
                </label>
                <label>
                    <input type="radio" value="watchlist" wire:model.live="source" class="peer sr-only">
                    <span class="flex h-full cursor-pointer flex-col rounded-2xl p-3 ring-1 ring-white/10 transition hover:bg-white/5 peer-checked:bg-brand-600/20 peer-checked:ring-brand-400 peer-focus-visible:outline-2 peer-focus-visible:outline-brand-400">
                        <span class="flex items-center gap-2 text-sm font-bold text-white"><x-icon name="bookmark" class="size-4" /> Watchlist Showdown</span>
                        <span class="mt-1 text-xs text-gray-400">Only movies saved by people in the room</span>
                    </span>
                </label>
            </div>
        </fieldset>
    @endauth

    @if ($source === 'watchlist')
        <p class="rounded-2xl bg-sky-500/10 px-4 py-3 text-sm text-sky-200 ring-1 ring-sky-400/20">
            The deck is built when you press start, from the watchlists of everyone who joined (signed-in members).
        </p>
    @else
    {{-- Theme --}}
    <fieldset>
        <legend class="text-sm font-semibold text-gray-200">Theme <span class="font-normal text-gray-500">(optional)</span></legend>
        <div class="mt-3 flex flex-wrap gap-2">
            <label><input type="radio" value="" wire:model="theme" class="peer sr-only"><span class="{{ $chip }}">None</span></label>
            @foreach ($themes as $themeOption)
                <label wire:key="theme-{{ $themeOption['key'] }}">
                    <input type="radio" value="{{ $themeOption['key'] }}" wire:model="theme" class="peer sr-only">
                    <span class="{{ $chip }}" title="{{ $themeOption['tagline'] }}">{{ $themeOption['name'] }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Genres --}}
    <fieldset>
        <legend class="text-sm font-semibold text-gray-200">Genres <span class="font-normal text-gray-500">(any of)</span></legend>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($genreOptions as $id => $genre)
                <label wire:key="genre-{{ $id }}">
                    <input type="checkbox" value="{{ $id }}" wire:model="genres" class="peer sr-only">
                    <span class="{{ $chip }}">{{ $genre }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Runtime & rating --}}
    <div class="grid gap-7 sm:grid-cols-2">
        <fieldset>
            <legend class="text-sm font-semibold text-gray-200">Length</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                <label><input type="radio" value="" wire:model="maxRuntime" class="peer sr-only"><span class="{{ $chip }}">Any</span></label>
                @foreach ($runtimes as $value => $label)
                    <label><input type="radio" value="{{ $value }}" wire:model="maxRuntime" class="peer sr-only"><span class="{{ $chip }}">{{ $label }}</span></label>
                @endforeach
            </div>
        </fieldset>
        <fieldset>
            <legend class="text-sm font-semibold text-gray-200">Minimum rating</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                <label><input type="radio" value="" wire:model="minRating" class="peer sr-only"><span class="{{ $chip }}">Any</span></label>
                @foreach ($ratings as $value => $label)
                    <label><input type="radio" value="{{ $value }}" wire:model="minRating" class="peer sr-only"><span class="{{ $chip }} flex items-center gap-1"><x-icon name="star-solid" class="size-3.5 text-amber-400" />{{ $label }}</span></label>
                @endforeach
            </div>
        </fieldset>
    </div>

    {{-- Country & services --}}
    <fieldset>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <legend class="text-sm font-semibold text-gray-200">
                Streaming services you have <span class="font-normal text-gray-500">(any of)</span>
            </legend>
            <label class="flex items-center gap-2 text-sm text-gray-400">
                <span class="sr-only sm:not-sr-only">Country</span>
                <select wire:model.live="region"
                        class="rounded-xl bg-ink-800 py-2 pl-3 pr-9 text-sm font-semibold text-white ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @foreach ($regions as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3" wire:loading.class="opacity-50" wire:target="region">
            @forelse ($providerOptions as $provider)
                <label wire:key="provider-{{ $region }}-{{ $provider['id'] }}">
                    <input type="checkbox" value="{{ $provider['id'] }}" wire:model="providers" class="peer sr-only">
                    <span class="flex cursor-pointer items-center gap-2.5 rounded-xl p-2 pr-3 text-sm font-semibold text-gray-300 ring-1 ring-white/10 transition hover:bg-white/5 peer-checked:bg-brand-600/20 peer-checked:text-white peer-checked:ring-brand-400 peer-focus-visible:outline-2 peer-focus-visible:outline-brand-400">
                        @if ($provider['logo'])
                            <img src="https://image.tmdb.org/t/p/w92{{ $provider['logo'] }}" alt="" class="size-8 shrink-0 rounded-lg">
                        @endif
                        <span class="line-clamp-1">{{ $provider['name'] }}</span>
                    </span>
                </label>
            @empty
                <p class="col-span-full text-sm text-gray-500">No services listed for this country.</p>
            @endforelse
        </div>
        <p class="mt-2 text-xs text-gray-500">Leave empty to include everything. Streaming data by JustWatch.</p>
    </fieldset>
    @endif

    @error('form')
        <p class="rounded-xl bg-red-500/10 px-4 py-3 text-sm text-red-300 ring-1 ring-red-500/30">{{ $message }}</p>
    @enderror

    <button type="submit" class="btn-brand w-full !py-3.5 text-base" wire:loading.attr="disabled" wire:target="create">
        <span wire:loading.remove wire:target="create" class="flex items-center gap-2"><x-icon name="sparkles" class="size-5" /> Create room</span>
        <span wire:loading wire:target="create" class="flex items-center gap-2"><span class="loading loading-spinner loading-sm"></span> Dealing the deck…</span>
    </button>
</form>
