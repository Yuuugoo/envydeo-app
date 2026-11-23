<x-guest-layout>
    <x-slot name="title">Create account</x-slot>

    <div class="text-center">
        <x-logo :wordmark="false" size="lg" class="justify-center" />
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Create your account</h1>
        <p class="mt-1.5 text-sm text-gray-400">Save a watchlist and continue where you left off.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" placeholder="Your name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="new-password" />
            </div>
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
            </div>
        </div>
        <x-input-error :messages="array_merge($errors->get('password'), $errors->get('password_confirmation'))" class="-mt-2" />

        <x-primary-button class="w-full">Create account</x-primary-button>
    </form>

    @include('auth.partials.google-button')

    <p class="mt-8 text-center text-sm text-gray-400">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-300 hover:text-brand-200">Sign in</a>
    </p>
</x-guest-layout>
