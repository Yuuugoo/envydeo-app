<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Enums\OAuthProviderEnum;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthProviderController extends Controller
{
    public function index(OAuthProviderEnum $provider)
    {
        return Socialite::driver($provider->value)->redirect();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OAuthProviderEnum $provider)
    {
        try {
            $socialite = Socialite::driver($provider->value)->user();

            $user = User::firstOrCreate([
                'email' => $socialite->getEmail(),
            ], [
                'name' => $socialite->getName(),
            ]);
    
            $user->providers()->updateOrCreate([
                'provider' => $provider,
                'provider_id' => $socialite->getId(), 
            ]);

            if ($socialite->getAvatar() && $provider->value === 'google') {
                $user->clearMediaCollection('avatar');

                $user->addMediaFromUrl($socialite->getAvatar())
                    ->usingFileName('google-avatar-' . $user->id . '.jpg')
                    ->toMediaCollection('avatar');
            }

            Auth::login($user);
            return redirect()->intended(route('movie.index', absolute: false));

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
