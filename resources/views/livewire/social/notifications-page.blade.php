<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="min-w-0 space-y-8">
        @if ($requests->isNotEmpty())
            <section class="rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5" aria-labelledby="requests-heading">
                <h2 id="requests-heading" class="section-title">Follow requests</h2>
                <ul class="mt-4 divide-y divide-white/5">
                    @foreach ($requests as $person)
                        <li class="flex items-center gap-3 py-3" wire:key="req-{{ $person->id }}">
                            <a href="{{ $person->profileUrl() }}"><x-avatar :user="$person" /></a>
                            <a href="{{ $person->profileUrl() }}" class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-white">{{ $person->name }}</p>
                                <p class="truncate text-xs text-gray-400">{{ '@' . $person->username }}</p>
                            </a>
                            <button type="button" wire:click="accept('{{ $person->id }}')" class="btn-brand !px-3.5 !py-1.5 text-xs">Accept</button>
                            <button type="button" wire:click="decline('{{ $person->id }}')" class="btn-glass !px-3.5 !py-1.5 text-xs">Decline</button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section aria-labelledby="all-heading">
            <h2 id="all-heading" class="section-title">All notifications</h2>
            <div class="mt-4 space-y-1">
                @forelse ($notifications as $n)
                    @include('livewire.social.partials.notification-row', ['n' => $n])
                @empty
                    <x-empty-state icon="sparkles" title="Nothing yet" message="Follows, reactions, likes and movie night invites show up here." />
                @endforelse
            </div>
            <div class="mt-4">{{ $notifications->links() }}</div>
        </section>
    </div>

    {{-- Push settings --}}
    <aside class="h-fit space-y-4 rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5"
           x-data="pushToggle({ vapidKey: @js($vapidKey) })">
        <h2 class="text-sm font-bold text-white">Phone notifications</h2>
        <p class="text-xs leading-relaxed text-gray-400">Get pinged when friends check in, react, or invite you to movie night, even when Envydeo is closed.</p>

        <template x-if="!supported">
            <p class="rounded-xl bg-white/5 p-3 text-xs text-gray-400">This browser doesn’t support push notifications.</p>
        </template>
        <template x-if="supported && needsInstall">
            <div class="rounded-xl bg-sky-500/10 p-3 text-xs text-sky-200 ring-1 ring-sky-400/20">
                On iPhone, first install Envydeo: <strong>Share → Add to Home Screen</strong>, then open it from your home screen and turn this on.
            </div>
        </template>
        <template x-if="supported && !needsInstall">
            <div>
                <button type="button" x-show="!subscribed" @click="enable()" :disabled="busy" class="btn-brand w-full">Turn on for this device</button>
                <button type="button" x-show="subscribed" x-cloak @click="disable()" :disabled="busy" class="btn-glass w-full">✓ On, turn off</button>
                <p x-show="error" x-text="error" class="mt-2 text-xs text-red-300"></p>
            </div>
        </template>

        <label class="flex items-start gap-3 border-t border-white/5 pt-4 text-sm text-gray-300" x-data="{ on: @js($notifyCheckins) }">
            <input type="checkbox" x-model="on" @change="$wire.savePreferences(on)" class="checkbox checkbox-sm checkbox-primary mt-0.5">
            <span>Tell me when friends check in<span class="block text-xs text-gray-500">“Ana is watching Dune 🍿”</span></span>
        </label>
    </aside>
</div>
