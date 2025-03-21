<?php

namespace App\Http\Controllers\Auth\OAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function redirect(string $driver) : RedirectResponse
    {
        return Socialite::driver($driver)->redirect();
    }

    public function callback(string $driver) : RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver($driver)->user();
        } catch (Exception $exception) {
            Log::error("OAuth Error ($driver): " . $exception->getMessage());

            // TODO: translation
            return redirect()->route('login')->withErrors([
                'email' => 'Authentication via ' . ucfirst($driver) . ' failed. Please try again.',
            ]);
        }

        if (!$socialiteUser->getEmail()) {
            // TODO: translation
            return redirect()->route('login')->withErrors([
                'email' => 'Email not received from ' . ucfirst($driver) . '.',
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => $socialiteUser->getEmail()],
            [
                'name' => $socialiteUser->getName(),
                'provider_id' => $socialiteUser->getId(),
                'provider_type' => $driver,
                'provider_token' => $socialiteUser->token,
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user);

        return redirect()->route('profile.dashboard');
    }
}
