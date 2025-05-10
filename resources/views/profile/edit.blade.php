<x-app-layout>
    <div class="container max-w-7xl mx-auto px-4 py-6">
        <h2 class="font-bold text-xl text-white leading-tight mb-6">
            {{ __('EDIT PROFILE') }}
        </h2>
        
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex justify-center">
                <div class="w-full max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
            
            <!-- <div class="flex justify-center">
                <div class="w-full max-w-xl">

                    @include('profile.partials.update-password-form')

                </div>
            </div>

            <div class="flex justify-center">
                <div class="w-full max-w-xl">

                        @include('profile.partials.delete-user-form')
                </div>
            </div> -->
        </div>
    </div>
</x-app-layout>
