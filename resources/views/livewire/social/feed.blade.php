@php
    $groupSizes = $groups->map(fn ($g) => $g['checkIns']->count())->values();
    $viewerHasLive = $groups->contains(fn ($g) => $g['user']->is($viewer));
@endphp

<div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]"
     x-data="{
        group: @js($openGroup),
        index: @js($openIndex),
        sizes: @js($groupSizes),
        open(g) { this.group = g; this.index = 0; },
        close() { this.group = null; },
        next() {
            if (this.index < this.sizes[this.group] - 1) { this.index++; return; }
            if (this.group < this.sizes.length - 1) { this.group++; this.index = 0; return; }
            this.close();
        },
        prev() {
            if (this.index > 0) { this.index--; return; }
            if (this.group > 0) { this.group--; this.index = this.sizes[this.group] - 1; }
        }
     }"
     @keydown.escape.window="close()"
     @keydown.arrow-right.window="group !== null && next()"
     @keydown.arrow-left.window="group !== null && prev()">

    <div class="min-w-0 max-w-2xl">
        {{-- ============================================================ TONIGHT STRIP --}}
        <section aria-labelledby="tonight-heading">
            <h2 id="tonight-heading" class="section-title">Tonight</h2>
            <ul class="no-scrollbar -mx-4 mt-4 flex gap-4 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:px-6 lg:mx-0 lg:px-0">
                @unless ($viewerHasLive)
                    <li class="w-[4.5rem] shrink-0 text-center">
                        <button type="button" @click="$dispatch('open-check-in')" class="group block w-full">
                            <span class="relative mx-auto grid size-16 place-items-center rounded-full border-2 border-dashed border-white/25 bg-white/[0.03] text-brand-300 transition group-hover:border-brand-400">
                                <x-icon name="sparkles" class="size-6" />
                                <span class="absolute -bottom-0.5 -right-0.5 grid size-6 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white ring-2 ring-ink-950">+</span>
                            </span>
                            <span class="mt-1.5 block truncate text-xs font-semibold text-gray-300">Check in</span>
                        </button>
                    </li>
                @endunless
                @foreach ($groups as $g => $group)
                    <li class="w-[4.5rem] shrink-0 text-center" wire:key="tonight-{{ $group['user']->id }}">
                        <button type="button" @click="open({{ $g }})" class="group block w-full" aria-label="Open {{ $group['user']->name }}’s check-ins">
                            <span class="mx-auto block rounded-full bg-gradient-to-tr from-brand-400 via-emerald-300 to-sky-400 p-[3px] transition group-hover:scale-105">
                                <span class="block rounded-full bg-ink-950 p-[2px]"><x-avatar :user="$group['user']" size="lg" class="!size-[3.4rem]" /></span>
                            </span>
                            <span class="mt-1.5 block truncate text-xs font-semibold text-gray-200">{{ $group['user']->is($viewer) ? 'You' : $group['user']->name }}</span>
                            <span class="block truncate text-[10px] text-gray-500">{{ $group['checkIns']->last()->movie['title'] ?? '' }}</span>
                        </button>
                    </li>
                @endforeach
                @if ($groups->isEmpty() || ($groups->count() === 1 && $viewerHasLive))
                    <li class="flex min-w-[14rem] items-center text-sm text-gray-500">
                        When friends check in, they’ll show up here for 24 hours.
                    </li>
                @endif
            </ul>
        </section>

        {{-- ============================================================ FEED --}}
        <section class="mt-10" aria-labelledby="feed-heading">
            <h2 id="feed-heading" class="section-title">Friends’ activity</h2>
            <div class="mt-5 space-y-4">
                @forelse ($activities as $activity)
                    @include('livewire.social.partials.activity', ['activity' => $activity])
                @empty
                    <x-empty-state icon="users" title="Your feed is quiet"
                                   message="Follow friends to see what they watch, rate and check in. Try searching for people you know.">
                        <a href="{{ route('people') }}" class="btn-brand">Find people</a>
                    </x-empty-state>
                @endforelse
            </div>
            @if ($hasMore)
                <div class="mt-6 text-center">
                    <button type="button" wire:click="loadMore" class="btn-glass" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="loadMore">Load more</span>
                        <span wire:loading wire:target="loadMore" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            @endif
        </section>
    </div>

    {{-- ============================================================ SIDEBAR --}}
    <aside class="space-y-6">
        <div class="rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5">
            <div class="flex items-center gap-3">
                <x-avatar :user="$viewer" size="md" />
                <div class="min-w-0">
                    <a href="{{ $viewer->profileUrl() }}" class="block truncate font-bold text-white hover:text-brand-300">{{ $viewer->name }}</a>
                    <p class="truncate text-xs text-gray-400">{{ '@' . $viewer->username }}</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <button type="button" @click="$dispatch('open-check-in')" class="btn-brand !px-3 !py-2 text-sm">Check in</button>
                <a href="{{ route('people') }}" class="btn-glass !px-3 !py-2 text-sm">Find people</a>
            </div>
        </div>

        @if ($suggestions->isNotEmpty())
            <div class="rounded-3xl bg-ink-900/70 p-5 ring-1 ring-white/5">
                <h2 class="text-sm font-bold text-white">People to follow</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($suggestions as $person)
                        <li class="flex items-center gap-3" wire:key="suggest-{{ $person->id }}">
                            <a href="{{ $person->profileUrl() }}"><x-avatar :user="$person" size="sm" /></a>
                            <a href="{{ $person->profileUrl() }}" class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $person->name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $person->mutuals ? $person->mutuals . ' mutual ' . \Illuminate\Support\Str::plural('friend', $person->mutuals) : '@' . $person->username }}</p>
                            </a>
                            <livewire:social.follow-button :user="$person" compact :menu="false" :key="'sf-' . $person->id" />
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </aside>

    {{-- ============================================================ TONIGHT VIEWER (stories)
         Plain fixed overlay (not x-teleport) so the nested Livewire cards stay wired up. --}}
        <div x-show="group !== null" x-cloak x-transition.opacity class="fixed inset-0 z-[96] bg-black" role="dialog" aria-modal="true" aria-label="Tonight">
            <div class="mx-auto flex h-full max-w-md flex-col pt-[env(safe-area-inset-top)]">
                @foreach ($groups as $g => $group)
                    @foreach ($group['checkIns'] as $i => $checkIn)
                        <div x-show="group === {{ $g }} && index === {{ $i }}" class="flex min-h-0 flex-1 flex-col">
                            {{-- progress bars --}}
                            <div class="flex gap-1 px-3 pt-3">
                                @foreach ($group['checkIns'] as $j => $_)
                                    <span class="h-0.5 flex-1 rounded-full {{ $j <= $i ? 'bg-white' : 'bg-white/30' }}"></span>
                                @endforeach
                            </div>
                            <div class="relative flex min-h-0 flex-1 flex-col pt-2">
                                <livewire:social.check-in-card :check-in-id="$checkIn->id" variant="story" :key="'story-' . $checkIn->id" />
                                {{-- tap zones --}}
                                <button type="button" @click="prev()" class="absolute bottom-40 left-0 top-16 w-1/4" aria-label="Previous"></button>
                                <button type="button" @click="next()" class="absolute bottom-40 right-0 top-16 w-1/4" aria-label="Next"></button>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
            <button type="button" @click="close()" class="absolute right-3 top-[max(0.75rem,env(safe-area-inset-top))] z-10 grid size-10 place-items-center rounded-full bg-white/10 text-white backdrop-blur" aria-label="Close">
                <x-icon name="x" class="size-5" />
            </button>
        </div>
</div>
