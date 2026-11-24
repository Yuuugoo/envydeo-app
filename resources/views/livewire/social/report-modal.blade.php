<div>
    @if ($open)
        <div class="fixed inset-0 z-[97] flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="report-title"
             @keydown.escape.window="$wire.set('open', false)">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.set('open', false)"></div>
            <div class="relative w-full max-w-sm rounded-t-3xl bg-ink-900 p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] shadow-2xl ring-1 ring-white/10 sm:rounded-3xl">
                @if ($sent)
                    <div class="text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-600/15 text-brand-300"><x-icon name="check" class="size-6" /></span>
                        <h2 class="mt-4 font-extrabold text-white">Thanks for letting us know</h2>
                        <p class="mt-1 text-sm text-gray-400">We’ll review it. You can also block this person from their profile.</p>
                        <button type="button" @click="$wire.set('open', false)" class="btn-glass mt-6 w-full">Done</button>
                    </div>
                @else
                    <form wire:submit="submit">
                        <h2 id="report-title" class="font-extrabold text-white">Report {{ ['user' => 'account', 'check_in' => 'check-in', 'diary_entry' => 'review'][$type] ?? '' }}</h2>
                        <fieldset class="mt-4 space-y-2">
                            <legend class="sr-only">Reason</legend>
                            @foreach ($reasons as $key => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 ring-1 transition {{ $reason === $key ? 'bg-brand-600/15 ring-brand-500/40' : 'ring-white/10 hover:bg-white/5' }}">
                                    <input type="radio" wire:model.live="reason" value="{{ $key }}" class="radio radio-sm radio-primary">
                                    <span class="text-sm text-gray-200">{{ $label }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        <textarea wire:model="details" rows="2" maxlength="500" placeholder="Anything else? (optional)"
                                  class="mt-4 w-full resize-none rounded-xl bg-ink-800 px-4 py-3 text-sm text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                        <div class="mt-5 flex gap-2">
                            <button type="button" @click="$wire.set('open', false)" class="btn-glass flex-1">Cancel</button>
                            <button type="submit" class="btn-brand flex-1" wire:loading.attr="disabled">Send report</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
