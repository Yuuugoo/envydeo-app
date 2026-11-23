<x-guest-layout>
    <x-slot name="title">Sign in</x-slot>

    <div class="text-center">
        <x-logo :wordmark="false" size="lg" class="justify-center" />
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Welcome back</h1>
        <p class="mt-1.5 text-sm text-gray-400">Sign in to sync your watchlist and progress.</p>
    </div>

    <x-auth-session-status class="mt-6 rounded-xl bg-brand-600/15 px-4 py-3 text-sm text-brand-200 ring-1 ring-brand-500/30" :status="session('status')" />
    @if (session('error'))
        <p class="mt-6 rounded-xl bg-red-500/10 px-4 py-3 text-sm text-red-300 ring-1 ring-red-500/30">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" class="!mb-0" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-brand-300 hover:text-brand-200" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2.5 text-sm text-gray-300">
            <input id="remember_me" type="checkbox" name="remember" class="checkbox checkbox-sm checkbox-primary">
            Remember me
        </label>

        <x-primary-button class="w-full">Sign in</x-primary-button>
    </form>

    @include('auth.partials.google-button')

    <p class="mt-8 text-center text-sm text-gray-400">
        New to Envydeo?
        <a href="{{ route('register') }}" class="font-semibold text-brand-300 hover:text-brand-200">Create an account</a>
    </p>
</x-guest-layout>
