<x-guest-layout>
    <div class="max-w-md w-full space-y-8 bg-gray-800 p-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-white">
                {{ __('Create your account') }}
            </h2>
            <p class="mt-2 text-sm text-gray-300">
                {{ __('Join us to get started with your new account') }}
            </p>
        </div>
        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Name')" class="text-white" />
                <x-text-input id="name" class="block mt-1 p-3 w-full bg-gray-700 border-gray-600 text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" type="text" placeholder="Enter your name" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div class="mt-4">
                <x-input-label for="email" :value="__('Email')" class="text-white" />
                <x-text-input id="email" class="block mt-1 p-3 w-full bg-gray-700 border-gray-600 text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" type="email" placeholder="Enter a valid email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" class="text-white" />
                <x-text-input id="password" class="block mt-1 p-3 w-full bg-gray-700 border-gray-600 text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                type="password"
                                name="password"
                                placeholder="Enter your password"
                                required autocomplete="new-password"/>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-white" />
                <x-text-input id="password_confirmation" class="block mt-1 p-3 w-full bg-gray-700 border-gray-600 text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                type="password"
                                placeholder="Confirm your password"
                                name="password_confirmation" required autocomplete="new-password"/>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="mt-6">
                <x-primary-button class="w-full justify-center p-3 text-lg font-medium">
                    {{ __('Register') }}
                </x-primary-button>
            </div>

            <div class="flex items-center justify-center mt-6">
                <a class="text-sm text-indigo-400 hover:text-indigo-300 transition duration-150" href="{{ route('login') }}">
                    {{ __('Already have an account?') }}
                    <span class="text-md ms-2 text-white">Sign in</span>
                </a>
            </div>
        </form>
        <div class="mt-6 relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-600"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-gray-800 text-gray-400">Or continue with</span>
            </div>
        </div>

        <!-- Google Sign In -->
        <div class="mt-6">
            <a href="{{ route('login.provider', 'google') }}" class="flex items-center justify-center px-4 py-3 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 active:text-white active:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 w-full">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <g transform="matrix(1, 0, 0, 1, 27.009001, -39.238998)">
                        <path fill="#4285F4" d="M -3.264 51.509 C -3.264 50.719 -3.334 49.969 -3.454 49.239 L -14.754 49.239 L -14.754 53.749 L -8.284 53.749 C -8.574 55.229 -9.424 56.479 -10.684 57.329 L -10.684 60.329 L -6.824 60.329 C -4.564 58.239 -3.264 55.159 -3.264 51.509 Z"/>
                        <path fill="#34A853" d="M -14.754 63.239 C -11.514 63.239 -8.804 62.159 -6.824 60.329 L -10.684 57.329 C -11.764 58.049 -13.134 58.489 -14.754 58.489 C -17.884 58.489 -20.534 56.379 -21.484 53.529 L -25.464 53.529 L -25.464 56.619 C -23.494 60.539 -19.444 63.239 -14.754 63.239 Z"/>
                        <path fill="#FBBC05" d="M -21.484 53.529 C -21.734 52.809 -21.864 52.039 -21.864 51.239 C -21.864 50.439 -21.724 49.669 -21.484 48.949 L -21.484 45.859 L -25.464 45.859 C -26.284 47.479 -26.754 49.299 -26.754 51.239 C -26.754 53.179 -26.284 54.999 -25.464 56.619 L -21.484 53.529 Z"/>
                        <path fill="#EA4335" d="M -14.754 43.989 C -12.984 43.989 -11.404 44.599 -10.154 45.789 L -6.734 42.369 C -8.804 40.429 -11.514 39.239 -14.754 39.239 C -19.444 39.239 -23.494 41.939 -25.464 45.859 L -21.484 48.949 C -20.534 46.099 -17.884 43.989 -14.754 43.989 Z"/>
                    </g>
                </svg>
                {{ __('Sign in with Google') }}
            </a>
        </div>
    </div>

</x-guest-layout>
