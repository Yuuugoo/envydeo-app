@extends('layouts.main')

@section('title', ucfirst($list) . ' · @' . $owner->username)

@section('content')
<div class="shell page-top max-w-2xl pb-8">
    <a href="{{ route('profile.show', $owner->username) }}" class="text-sm font-semibold text-gray-400 hover:text-white">← {{ $owner->name }}</a>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white">{{ ucfirst($list) }}</h1>

    <nav class="mt-6 flex gap-1 border-b border-white/10">
        @foreach (['followers' => 'Followers', 'following' => 'Following'] as $key => $label)
            <a href="{{ route('profile.connections', [$owner->username, $key]) }}"
               class="-mb-px border-b-2 px-4 py-3 text-sm font-semibold {{ $list === $key ? 'border-brand-400 text-white' : 'border-transparent text-gray-400 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <ul class="mt-4 divide-y divide-white/5">
        @forelse ($people as $person)
            <li class="flex items-center gap-3 py-3">
                <a href="{{ $person->profileUrl() }}"><x-avatar :user="$person" /></a>
                <a href="{{ $person->profileUrl() }}" class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-white">{{ $person->name }}</p>
                    <p class="truncate text-xs text-gray-400">{{ '@' . $person->username }}</p>
                </a>
                @auth
                    <livewire:social.follow-button :user="$person" compact :menu="false" :key="'f-' . $person->id" />
                @endauth
            </li>
        @empty
            <li><x-empty-state icon="users" title="Nobody here yet" class="mt-6" /></li>
        @endforelse
    </ul>
    {{ $people->links() }}
</div>
@endsection
