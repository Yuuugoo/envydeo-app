<x-app-layout>
    <x-slot name="title">Profile</x-slot>

    <header class="mb-10">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Account</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-5xl">Edit profile</h1>
    </header>

    <div class="mx-auto max-w-xl space-y-6">
        <div class="rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        @if (auth()->user()->username)
            <div class="rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8">
                <livewire:social.edit-profile />
            </div>
        @else
            <a href="{{ route('welcome') }}" class="block rounded-3xl bg-brand-600/10 p-6 text-sm text-brand-200 ring-1 ring-brand-500/30 hover:bg-brand-600/15">
                <strong class="text-white">Set up your social profile →</strong><br>Pick a username to follow friends, check in and post reviews.
            </a>
        @endif

        <div class="rounded-3xl bg-ink-900/70 p-6 ring-1 ring-red-500/10 sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
