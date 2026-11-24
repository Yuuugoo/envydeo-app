<div>
    @if ($open)
        <div class="fixed inset-0 z-[95] flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="checkin-title"
             x-data x-init="document.documentElement.classList.add('overflow-hidden'); $el.querySelector('input')?.focus()"
             x-on:remove="document.documentElement.classList.remove('overflow-hidden')"
             @keydown.escape.window="$wire.set('open', false); document.documentElement.classList.remove('overflow-hidden')">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.set('open', false); document.documentElement.classList.remove('overflow-hidden')"></div>

            <form wire:submit="share" class="relative flex max-h-[92svh] w-full max-w-lg flex-col rounded-t-3xl bg-ink-900 shadow-2xl ring-1 ring-white/10 sm:rounded-3xl">
                <header class="flex items-center justify-between border-b border-white/5 px-5 py-4">
                    <div>
                        <h2 id="checkin-title" class="font-extrabold text-white">What are you watching tonight?</h2>
                        <p class="text-xs text-gray-400">
                            @if ($friendCount === 0)
                                Only you for now: follow friends back to share
                            @else
                                Your {{ $friendCount }} {{ \Illuminate\Support\Str::plural('friend', $friendCount) }} {{ $friendCount === 1 ? 'sees' : 'see' }} it for 24 hours
                            @endif
                        </p>
                    </div>
                    <button type="button" @click="$wire.set('open', false); document.documentElement.classList.remove('overflow-hidden')" class="grid size-9 place-items-center rounded-full text-gray-400 hover:bg-white/10 hover:text-white" aria-label="Close">
                        <x-icon name="x" class="size-5" />
                    </button>
                </header>

                <div class="flex-1 space-y-5 overflow-y-auto p-5">
                    {{-- Title --}}
                    @if ($selected)
                        <div class="flex items-center gap-3 rounded-2xl bg-white/[0.04] p-3 ring-1 ring-white/10">
                            <img src="https://image.tmdb.org/t/p/w154{{ $selected['poster_path'] }}" alt="" class="w-12 rounded-lg">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-white">{{ $selected['title'] }}</p>
                                <p class="text-xs text-gray-400">{{ $selected['type'] === 'tv' ? 'TV' : 'Movie' }} · {{ $selected['year'] }}</p>
                            </div>
                            <button type="button" wire:click="clearTitle" class="text-xs font-semibold text-brand-300 hover:text-brand-200">Change</button>
                        </div>
                    @else
                        <div>
                            <div class="relative">
                                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-gray-500" />
                                <input type="search" wire:model.live.debounce.350ms="query" placeholder="Search movies & shows…" aria-label="Search a title"
                                       class="w-full rounded-xl bg-ink-800 py-3 pl-12 pr-4 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                            <x-input-error :messages="$errors->get('selected')" class="mt-2" />
                            @if ($results)
                                <ul class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-4">
                                    @foreach ($results as $r)
                                        <li>
                                            <button type="button" wire:click="choose({{ $r['id'] }}, '{{ $r['type'] }}')" class="group block w-full text-left">
                                                <img src="https://image.tmdb.org/t/p/w185{{ $r['poster_path'] }}" alt="{{ $r['title'] }}" class="aspect-[2/3] w-full rounded-lg object-cover ring-1 ring-white/10 transition group-hover:ring-brand-400">
                                                <span class="mt-1 line-clamp-1 block text-[11px] text-gray-300">{{ $r['title'] }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif (mb_strlen($query) >= 2)
                                <p class="mt-3 text-sm text-gray-500" wire:loading.remove wire:target="query">No matches.</p>
                            @endif
                        </div>
                    @endif

                    {{-- Photo --}}
                    <div>
                        @if ($photo)
                            <div class="relative overflow-hidden rounded-2xl ring-1 ring-white/10">
                                <img src="{{ $photo->temporaryUrl() }}" alt="Your photo" class="max-h-72 w-full object-cover">
                                <button type="button" wire:click="removePhoto" class="absolute right-2 top-2 grid size-9 place-items-center rounded-full bg-black/70 text-white" aria-label="Remove photo"><x-icon name="x" class="size-4" /></button>
                            </div>
                        @else
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-dashed border-white/15 px-4 py-5 text-sm font-semibold text-gray-300 transition hover:border-brand-400/60 hover:bg-white/[0.03]">
                                <x-icon name="photo" class="size-5" />
                                <span wire:loading.remove wire:target="photo">Add a photo (your setup, snacks, the screen…)</span>
                                <span wire:loading wire:target="photo">Uploading…</span>
                                <input type="file" wire:model="photo" accept="image/*" class="sr-only">
                            </label>
                        @endif
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                        <p class="mt-1.5 text-[11px] text-gray-500">Friends-only. Location info is removed from photos.</p>
                    </div>

                    {{-- Caption --}}
                    <div>
                        <textarea wire:model="caption" rows="2" maxlength="140" placeholder="Say something… (optional)"
                                  class="w-full resize-none rounded-xl bg-ink-800 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                        <x-input-error :messages="$errors->get('caption')" class="mt-1" />
                    </div>
                </div>

                <footer class="border-t border-white/5 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <button type="submit" class="btn-brand w-full !py-3" wire:loading.attr="disabled" wire:target="share,photo" @disabled(!$selected)>
                        <span wire:loading.remove wire:target="share">Share with friends</span>
                        <span wire:loading wire:target="share" class="loading loading-spinner loading-sm"></span>
                    </button>
                </footer>
            </form>
        </div>
    @endif
</div>
