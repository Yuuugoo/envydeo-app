<x-app-layout>
    <x-slot name="title">Profile</x-slot>

    <header class="mb-10">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Account</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white md:text-5xl">Edit profile</h1>
    </header>

    <div class="mx-auto max-w-xl rounded-3xl bg-ink-900/70 p-6 ring-1 ring-white/5 sm:p-8">
        @include('profile.partials.update-profile-information-form')
    </div>

    {{-- Password and account deletion are not exposed yet:
        @include('profile.partials.update-password-form')
        @include('profile.partials.delete-user-form')
    --}}
</x-app-layout>
