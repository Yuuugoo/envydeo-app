<div class="contents">
    @if ($latest)
        <button type="button" wire:click="start({{ $latest->id }})" class="btn-glass !border-amber-400/30 w-full sm:w-auto" title="Edit your log">
            @if ($latest->rating)
                <x-stars :value="$latest->rating" size="size-4" />
            @else
                <x-icon name="check" class="size-5" /> Logged
            @endif
        </button>
        <button type="button" wire:click="start" class="btn-glass w-full sm:w-auto">Log again</button>
    @else
        <button type="button" wire:click="start" class="btn-glass w-full sm:w-auto">
            <x-icon name="star-solid" class="size-5 text-amber-400" /> Log / Rate
        </button>
    @endif

    @if ($open)
        <div class="fixed inset-0 z-[95] flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="log-title"
             @keydown.escape.window="$wire.set('open', false)">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.set('open', false)"></div>
            <form wire:submit="save" class="relative w-full max-w-md rounded-t-3xl bg-ink-900 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <h2 id="log-title" class="text-lg font-extrabold text-white">{{ $editingId ? 'Edit your log' : 'Log it' }}</h2>

                {{-- ½-star rating: each star has a left (½) and right (full) hit area --}}
                <div class="mt-5" x-data="{ hover: 0, value: @entangle('rating') }">
                    <p class="text-sm font-semibold text-gray-200">Rating</p>
                    <div class="mt-2 flex items-center gap-3">
                        <div class="flex" @mouseleave="hover = 0" role="radiogroup" aria-label="Rating out of 5 stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="relative size-10">
                                    <svg class="size-10" viewBox="0 0 24 24" aria-hidden="true">
                                        <defs><clipPath id="half-{{ $i }}"><rect x="0" y="0" width="12" height="24" /></clipPath></defs>
                                        <path fill="rgba(255,255,255,.12)" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" />
                                        <path fill="#fbbf24" x-show="(hover || value) >= {{ $i * 2 }}" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" />
                                        <path fill="#fbbf24" clip-path="url(#half-{{ $i }})" x-show="(hover || value) === {{ $i * 2 - 1 }}" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" />
                                    </svg>
                                    <button type="button" class="absolute inset-y-0 left-0 w-1/2" @mouseenter="hover = {{ $i * 2 - 1 }}" @click="value = value === {{ $i * 2 - 1 }} ? 0 : {{ $i * 2 - 1 }}" aria-label="{{ $i - 0.5 }} stars"></button>
                                    <button type="button" class="absolute inset-y-0 right-0 w-1/2" @mouseenter="hover = {{ $i * 2 }}" @click="value = value === {{ $i * 2 }} ? 0 : {{ $i * 2 }}" aria-label="{{ $i }} stars"></button>
                                </span>
                            @endfor
                        </div>
                        <span class="text-sm font-semibold text-gray-300" x-text="(hover || value) ? ((hover || value) / 2) + ' / 5' : 'No rating'"></span>
                    </div>
                    <x-input-error :messages="$errors->get('rating')" class="mt-1" />
                </div>

                <div class="mt-5">
                    <label for="review" class="text-sm font-semibold text-gray-200">Review <span class="font-normal text-gray-500">(optional)</span></label>
                    <textarea id="review" wire:model="review" rows="4" maxlength="2000" placeholder="What did you think?"
                              class="mt-2 w-full resize-none rounded-xl bg-ink-800 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                    <x-input-error :messages="$errors->get('review')" class="mt-1" />
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-300">
                        <input type="checkbox" wire:model="hasSpoilers" class="checkbox checkbox-sm checkbox-warning"> Contains spoilers
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-300">
                        Watched
                        <input type="date" wire:model="watchedOn" max="{{ now()->toDateString() }}"
                               class="rounded-lg bg-ink-800 px-2 py-1.5 text-sm text-white ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500 [color-scheme:dark]">
                    </label>
                </div>
                <x-input-error :messages="$errors->get('watchedOn')" class="mt-1" />

                <div class="mt-6 flex gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="delete" wire:confirm="Delete this log?" class="btn-glass !text-red-300">Delete</button>
                    @endif
                    <button type="button" @click="$wire.set('open', false)" class="btn-glass ml-auto">Cancel</button>
                    <button type="submit" class="btn-brand" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </div>
    @endif
</div>
