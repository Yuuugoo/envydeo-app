<div class="space-y-6" id="reviews">
    {{-- ============================================================ SCORES --}}
    <section class="rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5" aria-labelledby="scores-heading">
        <h2 id="scores-heading" class="section-title">Scores</h2>
        <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @if ($tmdbRating)
                <div class="rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/5">
                    <dt class="text-xs font-semibold text-gray-400">TMDB</dt>
                    <dd class="mt-1 text-2xl font-extrabold text-white">{{ number_format($tmdbRating, 1) }}<span class="text-sm font-semibold text-gray-500">/10</span></dd>
                </div>
            @endif
            @if ($communityCount)
                <div class="rounded-2xl bg-brand-600/10 p-4 ring-1 ring-brand-500/20">
                    <dt class="text-xs font-semibold text-brand-300">Envydeo</dt>
                    <dd class="mt-1 text-2xl font-extrabold text-white">{{ number_format($communityAverage / 2, 1) }}<span class="text-sm font-semibold text-gray-500">/5</span></dd>
                    <dd class="text-[11px] text-gray-500">{{ $communityCount }} {{ \Illuminate\Support\Str::plural('rating', $communityCount) }}</dd>
                </div>
            @endif
            @foreach (['imdb' => ['IMDb', '/10'], 'rotten_tomatoes' => ['Rotten Tomatoes', ''], 'metacritic' => ['Metacritic', '']] as $key => [$label, $suffix])
                @if (!empty($scores[$key]))
                    <div class="rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/5">
                        <dt class="text-xs font-semibold text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-extrabold text-white">{{ $scores[$key] }}<span class="text-sm font-semibold text-gray-500">{{ $suffix }}</span></dd>
                    </div>
                @endif
            @endforeach
        </dl>
        <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-gray-500">
            <span>See it on:</span>
            @foreach ($links as $label => $href)
                <a href="{{ $href }}" target="_blank" rel="noopener" class="rounded-full bg-white/5 px-3 py-1 font-semibold text-gray-200 ring-1 ring-white/10 hover:bg-white/10">{{ $label }} ↗</a>
            @endforeach
            @if (!$scoresEnabled)
                <span class="basis-full text-[11px] text-gray-600">IMDb / Rotten Tomatoes / Metacritic scores appear once an OMDb key is configured.</span>
            @endif
        </p>
    </section>

    {{-- ============================================================ FRIENDS WHO WANT TO WATCH --}}
    @if ($interested->isNotEmpty())
        <section class="flex flex-col gap-4 rounded-3xl bg-gradient-to-br from-brand-700/30 via-ink-900 to-ink-900 p-6 ring-1 ring-brand-500/20 sm:flex-row sm:items-center">
            <div class="flex -space-x-3">
                @foreach ($interested->take(5) as $person)
                    <a href="{{ $person->profileUrl() }}" title="{{ $person->name }}"><x-avatar :user="$person" size="md" class="ring-4 ring-ink-900" /></a>
                @endforeach
            </div>
            <p class="flex-1 text-sm text-gray-200">
                <strong class="text-white">{{ $interested->take(2)->pluck('name')->implode(' & ') }}{{ $interested->count() > 2 ? ' and ' . ($interested->count() - 2) . ' more' : '' }}</strong>
                {{ $interested->count() === 1 ? 'wants' : 'want' }} to watch this.
            </p>
            <button type="button" wire:click="startMovieNight" class="btn-brand shrink-0" wire:loading.attr="disabled">
                <x-icon name="sparkles" class="size-5" /> Start a movie night with them
            </button>
            @error('movieNight') <p class="basis-full text-sm text-red-300">{{ $message }}</p> @enderror
        </section>
    @endif

    {{-- ============================================================ REVIEWS --}}
    <section class="rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5" aria-labelledby="reviews-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="reviews-heading" class="section-title">Reviews</h2>
            @if ($friendAverage)
                <p class="flex items-center gap-2 text-sm text-gray-400">Friends average <x-stars :value="round($friendAverage)" size="size-3.5" /></p>
            @endif
        </div>

        <nav class="mt-4 flex gap-1 border-b border-white/10" role="tablist">
            @foreach (array_filter(['friends' => auth()->check() ? 'You & people you follow' : null, 'everyone' => 'Everyone on Envydeo', 'tmdb' => 'TMDB']) as $key => $label)
                <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        class="-mb-px border-b-2 px-3 py-2.5 text-sm font-semibold transition {{ $tab === $key ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white' }}">{{ $label }}</button>
            @endforeach
        </nav>

        <div class="mt-5 space-y-3" wire:loading.class="opacity-50" wire:target="tab">
            @if ($tab === 'friends')
                @forelse ($friendEntries as $entry)
                    <livewire:social.review-card :entry-id="$entry->id" :show-title="false" :key="'fr-' . $entry->id" />
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">Nobody you follow has logged this yet. Be the first.</p>
                @endforelse
            @elseif ($tab === 'everyone')
                @forelse ($everyone as $entry)
                    <livewire:social.review-card :entry-id="$entry->id" :show-title="false" :key="'ev-' . $entry->id" />
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">No reviews on Envydeo yet. Be the first: tap “Log / Rate” above.</p>
                @endforelse
            @else
                @forelse ($tmdbReviews as $review)
                    <article class="rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/5" x-data="{ more: false }">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-bold text-white">{{ $review['author'] ?? 'TMDB user' }}</span>
                            @if (!empty($review['author_details']['rating']))
                                <span class="flex items-center gap-1 text-amber-400"><x-icon name="star-solid" class="size-3.5" />{{ $review['author_details']['rating'] }}/10</span>
                            @endif
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-300" :class="!more && 'line-clamp-5'">{{ $review['content'] ?? '' }}</p>
                        <div class="mt-2 flex gap-4 text-xs">
                            <button type="button" @click="more = !more" class="font-semibold text-brand-300" x-text="more ? 'Show less' : 'Read more'"></button>
                            @if (!empty($review['url']))
                                <a href="{{ $review['url'] }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-white">on TMDB ↗</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">No TMDB reviews for this title.</p>
                @endforelse
            @endif
        </div>
    </section>
</div>
