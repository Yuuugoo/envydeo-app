<section x-data="{ avatarPreview: '{{ Auth::user()->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) }}' }">

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="text-center text-white mb-6">
            <label class="block text-white text-lg font-medium mb-2">Change Avatar</label>

            <div class="relative w-32 h-32 mx-auto">
                <img
                    :src="avatarPreview"
                    alt="Avatar Preview"
                    class="w-full h-full rounded-full object-cover border-4 border-white"
                >

                <label for="avatar-upload" class="absolute bottom-0 right-0 bg-green-500 p-2 rounded-full cursor-pointer shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                    </svg>
                    <input
                        id="avatar-upload"
                        name="avatar"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="
                            const file = $event.target.files[0];
                            if (file) {
                                avatarPreview = URL.createObjectURL(file);
                            }
                        "
                    />
                </label>
            </div>
        </div>

        <div class="mb-4">
            <x-input-label for="name" :value="__('Name')" class="text-white mb-2" />
            <x-text-input id="name" name="name" type="text" class="w-full bg-gray-800 border border-gray-600 rounded px-4 py-2 text-white" :value="old('name', $user->name)" required autofocus />
            <x-input-error class="mt-2 text-red-500" :messages="$errors->get('name')" />
        </div>

        <div class="mb-6">
            <x-input-label for="email" :value="__('Email')" class="text-white mb-2" />
            <x-text-input id="email" name="email" type="email" class="w-full bg-gray-800 border border-gray-600 rounded px-4 py-2 text-white" :value="old('email', $user->email)" required />
            <x-input-error class="mt-2 text-red-500" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-300">
                        {{ __('Your email address is unverified.') }}

                        <button type="submit" formaction="{{ route('verification.send') }}" formmethod="POST" class="underline text-sm text-gray-200 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            @csrf
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-500">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-brand-300"
                >{{ __('Profile Updated Successfully') }}</p>
            @endif
        </div>
    </form>
</section>
