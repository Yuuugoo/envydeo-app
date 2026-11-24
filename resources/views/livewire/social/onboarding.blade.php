<form wire:submit="save" class="space-y-6">
    <div class="text-center">
        <x-logo :wordmark="false" size="lg" class="justify-center" />
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Set up your profile</h1>
        <p class="mt-1.5 text-sm text-gray-400">Pick a username so friends can find and follow you.</p>
    </div>

    <div>
        <x-input-label for="username" value="Username" />
        <div class="relative">
            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-gray-500">@</span>
            <x-text-input id="username" wire:model.live.debounce.400ms="username" class="!pl-9 lowercase" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" />
        </div>
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
        @if (!$errors->has('username') && strlen($username) >= 3)
            <p class="mt-2 text-xs text-gray-500">Your profile: <span class="text-gray-300">{{ url('/@' . $username) }}</span></p>
        @endif
    </div>

    <div>
        <x-input-label for="bio" value="Bio (optional)" />
        <textarea id="bio" wire:model="bio" rows="2" maxlength="160" placeholder="Horror fan, rom-com apologist…"
                  class="w-full resize-none rounded-xl border-0 bg-ink-800/80 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
        <x-input-error :messages="$errors->get('bio')" class="mt-2" />
    </div>

    <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/10">
        <input type="checkbox" wire:model="isPrivate" class="checkbox checkbox-sm checkbox-primary mt-0.5">
        <span>
            <span class="block text-sm font-semibold text-white">Private account</span>
            <span class="mt-0.5 block text-xs text-gray-400">You approve who follows you. Your “Tonight” check-ins are always friends-only either way.</span>
        </span>
    </label>

    <x-primary-button class="w-full" wire:loading.attr="disabled">Continue</x-primary-button>
</form>
