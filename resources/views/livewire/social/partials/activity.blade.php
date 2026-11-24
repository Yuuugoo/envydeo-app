{{-- One feed item. $activity->data holds a snapshot so this renders without TMDB calls. --}}
@php
    $actor = $activity->user;
    $data = $activity->data ?? [];
    $movie = $data['movie'] ?? null;
    $isMe = $actor->is(auth()->user());
@endphp

@switch ($activity->type)
    @case('checked_in')
        @if ($activity->subject)
            <livewire:social.check-in-card :check-in-id="$activity->subject_id" :key="'feed-ci-' . $activity->subject_id" />
        @endif
        @break

    @case('logged')
        @if ($activity->subject)
            <livewire:social.review-card :entry-id="$activity->subject_id" :key="'feed-rv-' . $activity->subject_id" />
        @endif
        @break

    @case('matched')
        {{-- A Movie Night match: the moment worth celebrating --}}
        <article class="relative isolate flex items-center gap-4 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700/40 via-ink-900 to-fuchsia-900/30 p-4 ring-1 ring-brand-400/20">
            @if ($movie && !empty($movie['poster_path']))
                <a href="{{ \App\Support\TitleSnapshot::url($movie) }}" class="shrink-0">
                    <img src="https://image.tmdb.org/t/p/w154{{ $movie['poster_path'] }}" alt="{{ $movie['title'] }}" class="w-14 -rotate-3 rounded-lg shadow-xl ring-2 ring-white/20">
                </a>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-brand-300">🍿 It’s a match</p>
                <p class="mt-1 text-sm text-gray-200">
                    <a href="{{ $actor->profileUrl() }}" class="font-bold text-white hover:text-brand-300">{{ $isMe ? 'You' : $actor->name }}</a>
                    {{ $isMe ? 'are' : 'is' }} watching
                    <a href="{{ \App\Support\TitleSnapshot::url($movie) }}" class="font-bold text-white hover:text-brand-300">{{ $movie['title'] ?? '' }}</a>
                    after a Movie Night
                </p>
                <p class="mt-0.5 text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
            </div>
        </article>
        @break

    @default
        <article class="flex items-center gap-3 rounded-2xl bg-ink-900/50 p-3 ring-1 ring-white/5">
            <a href="{{ $actor->profileUrl() }}"><x-avatar :user="$actor" size="sm" /></a>
            <p class="min-w-0 flex-1 text-sm text-gray-300">
                <a href="{{ $actor->profileUrl() }}" class="font-bold text-white hover:text-brand-300">{{ $isMe ? 'You' : $actor->name }}</a>
                @switch ($activity->type)
                    @case('watchlisted')
                        {{ $isMe ? 'want' : 'wants' }} to watch <a href="{{ \App\Support\TitleSnapshot::url($movie) }}" class="font-semibold text-white hover:text-brand-300">{{ $movie['title'] }}</a>
                        @break
                    @case('followed')
                        started following
                        @if (!empty($data['username']))
                            <a href="{{ route('profile.show', $data['username']) }}" class="font-semibold text-white hover:text-brand-300">{{ $data['name'] ?? '@' . $data['username'] }}</a>
                        @endif
                        @break
                @endswitch
                <span class="block text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
            </p>
            @if ($movie && !empty($movie['poster_path']))
                <a href="{{ \App\Support\TitleSnapshot::url($movie) }}" class="shrink-0">
                    <img src="https://image.tmdb.org/t/p/w92{{ $movie['poster_path'] }}" alt="{{ $movie['title'] }}" class="w-10 rounded-md ring-1 ring-white/10">
                </a>
            @endif
        </article>
@endswitch
