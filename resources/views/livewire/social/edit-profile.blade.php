<section>
    <header>
        <h2 class="text-lg font-bold text-white">Social profile</h2>
        <p class="mt-1 text-sm text-gray-400">How people find you and what they see.</p>
    </header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <div>
            <x-input-label for="sp-username" value="Username" />
            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-gray-500">@</span>
                <x-text-input id="sp-username" wire:model="username" class="!pl-9 lowercase" maxlength="30" autocapitalize="none" spellcheck="false" />
            </div>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="sp-bio" value="Bio" />
            <textarea id="sp-bio" wire:model="bio" rows="2" maxlength="160"
                      class="w-full resize-none rounded-xl border-0 bg-ink-800/80 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
            <x-input-error :messages="$errors->get('bio')" class="mt-2" />
        </div>

        <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/10">
            <input type="checkbox" wire:model="isPrivate" class="checkbox checkbox-sm checkbox-primary mt-0.5">
            <span>
                <span class="block text-sm font-semibold text-white">Private account</span>
                <span class="mt-0.5 block text-xs text-gray-400">Only approved followers see your films, reviews and watchlist. Check-ins are always friends-only.</span>
            </span>
        </label>

        {{-- Top 4 --}}
        <div>
            <p class="text-sm font-semibold text-gray-200">Top 4 films</p>
            <ul class="mt-3 grid grid-cols-4 gap-3">
                @for ($i = 0; $i < 4; $i++)
                    <li class="relative">
                        @if (isset($topFilms[$i]))
                            <img src="https://image.tmdb.org/t/p/w185{{ $topFilms[$i]['poster_path'] }}" alt="{{ $topFilms[$i]['title'] }}" class="aspect-[2/3] w-full rounded-xl object-cover ring-1 ring-white/10">
                            <button type="button" wire:click="removeFilm({{ $i }})" class="absolute -right-2 -top-2 grid size-7 place-items-center rounded-full bg-ink-950 text-white ring-1 ring-white/20 hover:bg-red-500" aria-label="Remove {{ $topFilms[$i]['title'] }}"><x-icon name="x" class="size-3.5" /></button>
                        @else
                            <span class="grid aspect-[2/3] w-full place-items-center rounded-xl border border-dashed border-white/15 text-gray-600">{{ $i + 1 }}</span>
                        @endif
                    </li>
                @endfor
            </ul>
            @if (count($topFilms) < 4)
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Search a film to add…" aria-label="Search a film to add to your Top 4"
                       class="mt-3 w-full rounded-xl bg-ink-800/80 px-4 py-2.5 text-sm text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                @if ($results)
                    <ul class="mt-2 grid grid-cols-4 gap-2 sm:grid-cols-8">
                        @foreach ($results as $r)
                            <li>
                                <button type="button" wire:click="addFilm({{ $r['id'] }}, 'movie')" class="block w-full" title="{{ $r['title'] }} ({{ $r['year'] }})">
                                    <img src="https://image.tmdb.org/t/p/w154{{ $r['poster_path'] }}" alt="{{ $r['title'] }}" class="aspect-[2/3] w-full rounded-lg object-cover ring-1 ring-white/10 hover:ring-brand-400">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button wire:loading.attr="disabled">Save</x-primary-button>
            @if ($saved)
                <p class="text-sm text-brand-300" x-data x-init="setTimeout(() => $wire.set('saved', false), 2500)">Saved.</p>
            @endif
            @if (auth()->user()->username)
                <a href="{{ route('profile.show', auth()->user()->username) }}" class="ml-auto text-sm font-semibold text-gray-400 hover:text-white">View profile →</a>
            @endif
        </div>
    </form>
</section>
