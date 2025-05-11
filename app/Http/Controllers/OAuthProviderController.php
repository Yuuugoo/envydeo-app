<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Enums\OAuthProviderEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthProviderController extends Controller
{
    public function index(OAuthProviderEnum $provider)
    {
        try {
            
            return Socialite::driver($provider->value)
                ->stateless()
                ->scopes(['email', 'profile'])
                ->redirect();
        } catch (\Exception $e) {

            return redirect()->route('login')->with('error', 'Unable to connect to ' . $provider->value . '. Please try again.');
        }
    }

    public function store(OAuthProviderEnum $provider, Request $request)
    {
        try {
            
            if ($request->has('error')) {
                return redirect()->route('login')->with('error', 'Authentication failed: ' . $request->get('error_description', 'Unknown error'));
            }

            if (!$request->has('code')) {
                Log::error('OAuth callback missing code parameter', ['provider' => $provider->value]);
                return redirect()->route('login')->with('error', 'Authentication failed: Missing authorization code');
            }

            $socialite = Socialite::driver($provider->value)->stateless()->user();
            
            if (empty($socialite->getEmail())) {
                return redirect()->route('login')->with('error', 'Unable to retrieve email from ' . $provider->value);
            }

            $user = User::firstOrCreate(
                ['email' => $socialite->getEmail()],
                [
                    'name' => $socialite->getName() ?? explode('@', $socialite->getEmail())[0],
                    
                ]
            );

            $user->providers()->updateOrCreate(
                [
                    'provider' => $provider->value,
                    'provider_id' => $socialite->getId(),
                ],
                [
                    'provider' => $provider->value,
                    'provider_id' => $socialite->getId(),
                ]
            );
            

            if ($socialite->getAvatar() && $provider->value === 'google') {
                $user->clearMediaCollection('avatar');

                $user->addMediaFromUrl($socialite->getAvatar())
                    ->usingFileName('google-avatar-' . $user->id . '.jpg')
                    ->toMediaCollection('avatar');
            }

            Auth::login($user);

            return redirect()->intended(route('movie.index', [], false));

        } catch (\Exception $e) {
            Log::error('OAuth login failed', [
                'provider' => $provider->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
