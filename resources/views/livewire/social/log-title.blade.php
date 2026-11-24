<div class="contents">
    @if ($latest)
        <button type="button" wire:click="start({{ $latest->id }})" class="btn-glass w-full sm:w-auto" title="Edit your verdict">
            @if ($latest->verdict)
                <span aria-hidden="true">{{ $latest->verdictEmoji() }}</span> {{ $latest->verdictLabel() }}
            @else
                <x-icon name="check" class="size-5" /> Watched
            @endif
        </button>
        <button type="button" wire:click="start" class="btn-glass w-full sm:w-auto">Watched again</button>
    @else
        <button type="button" wire:click="start" class="btn-glass w-full sm:w-auto">
            <x-icon name="check" class="size-5 text-brand-300" /> I watched this
        </button>
    @endif

    {{-- Teleported to <body>: the title hero is an `isolate` stacking context, which would
         otherwise put this sheet underneath the mobile tab bar and hide the Save button. --}}
    @teleport('body')
    <div>
    @if ($open)
        <div class="fixed inset-0 z-[95] flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="log-title"
             @keydown.escape.window="$wire.set('open', false)">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.set('open', false)"></div>
            <form wire:submit="save" class="relative max-h-[92svh] w-full max-w-md overflow-y-auto overscroll-contain rounded-t-3xl bg-ink-900 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <h2 id="log-title" class="text-lg font-extrabold text-white">{{ $editingId ? 'Your verdict' : 'What’s the verdict?' }}</h2>

                {{-- Verdict: four big tap targets --}}
                <fieldset class="mt-5">
                    <legend class="sr-only">Verdict</legend>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach (\App\Models\DiaryEntry::VERDICTS as $key => [$emoji, $label])
                            <button type="button" wire:click="$set('verdict', {{ $verdict === $key ? 'null' : "'$key'" }})" aria-pressed="{{ $verdict === $key ? 'true' : 'false' }}"
                                    class="flex flex-col items-center gap-1 rounded-2xl px-1 py-3 ring-1 transition {{ $verdict === $key ? 'scale-[1.03] bg-brand-600/20 ring-2 ring-brand-400' : 'bg-white/[0.04] ring-white/10 hover:bg-white/[0.08]' }}">
                                <span class="text-3xl leading-none" aria-hidden="true">{{ $emoji }}</span>
                                <span class="text-[11px] font-bold {{ $verdict === $key ? 'text-white' : 'text-gray-300' }}">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('verdict')" class="mt-2" />
                </fieldset>

                <label class="mt-4 flex cursor-pointer items-center justify-between gap-3 rounded-2xl bg-white/[0.04] px-4 py-3 ring-1 ring-white/10">
                    <span>
                        <span class="block text-sm font-semibold text-white">🍿 Best with friends</span>
                        <span class="block text-xs text-gray-400">A good pick for a group movie night</span>
                    </span>
                    <input type="checkbox" wire:model="bestWithFriends" class="toggle toggle-success toggle-sm">
                </label>

                <div class="mt-4">
                    <label for="review" class="text-sm font-semibold text-gray-200">Your take <span class="font-normal text-gray-500">(optional)</span></label>
                    <textarea id="review" wire:model="review" rows="3" maxlength="2000" placeholder="One line is plenty…"
                              class="mt-2 w-full resize-none rounded-xl bg-ink-800 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                    <x-input-error :messages="$errors->get('review')" class="mt-1" />
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-300">
                        <input type="checkbox" wire:model="hasSpoilers" class="checkbox checkbox-sm checkbox-warning"> Spoilers
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
                        <button type="button" wire:click="delete" wire:confirm="Delete this?" class="btn-glass !text-red-300">Delete</button>
                    @endif
                    <button type="button" @click="$wire.set('open', false)" class="btn-glass ml-auto">Cancel</button>
                    <button type="submit" class="btn-brand" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </div>
    @endif
    </div>
    @endteleport
</div>
