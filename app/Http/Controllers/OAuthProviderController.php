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
            Log::info('Redirecting to provider', ['provider' => $provider->value]);
            
            return Socialite::driver($provider->value)
                ->stateless()
                ->scopes(['email', 'profile'])
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Failed to redirect to provider', [
                'provider' => $provider->value,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('login')->with('error', 'Unable to connect to ' . $provider->value . '. Please try again.');
        }
    }

    public function store(OAuthProviderEnum $provider, Request $request)
    {
        try {
            Log::info('OAuth callback received', [
                'provider' => $provider->value,
                'request_data' => $request->all(),
                'has_code' => $request->has('code'),
                'has_error' => $request->has('error'),
            ]);
            
            if ($request->has('error')) {
                Log::error('OAuth provider returned error', [
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description'),
                ]);
                return redirect()->route('login')->with('error', 'Authentication failed: ' . $request->get('error_description', 'Unknown error'));
            }

            if (!$request->has('code')) {
                Log::error('OAuth callback missing code parameter', ['provider' => $provider->value]);
                return redirect()->route('login')->with('error', 'Authentication failed: Missing authorization code');
            }

            $socialite = Socialite::driver($provider->value)->stateless()->user();
            
            Log::info('Socialite user retrieved', [
                'email' => $socialite->getEmail(),
                'id' => $socialite->getId(),
                'name' => $socialite->getName()
            ]);

            if (empty($socialite->getEmail())) {
                Log::error('No email provided by OAuth provider', ['provider' => $provider->value]);
                return redirect()->route('login')->with('error', 'Unable to retrieve email from ' . $provider->value);
            }

            $user = User::firstOrCreate(
                ['email' => $socialite->getEmail()],
                [
                    'name' => $socialite->getName() ?? explode('@', $socialite->getEmail())[0],
                    
                ]
            );
            Log::info('User created/found', ['user_id' => $user->id]);

            $providerData = [
                'provider' => $provider->value,
                'provider_id' => $socialite->getId(),
                'user_id' => $user->id,
            ];

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
            
            Log::info('Provider linked to user', [
                'user_id' => $user->id, 
                'provider' => $provider->value,
                'provider_id' => $socialite->getId()
            ]);

            if ($socialite->getAvatar() && $provider->value === 'google') {
                $user->clearMediaCollection('avatar');

                $user->addMediaFromUrl($socialite->getAvatar())
                    ->usingFileName('google-avatar-' . $user->id . '.jpg')
                    ->toMediaCollection('avatar');
            }

            Auth::login($user);
            Log::info('User logged in successfully', ['user_id' => $user->id]);
            
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
