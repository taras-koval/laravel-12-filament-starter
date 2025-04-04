<?php

namespace App\Http\Controllers\Auth\OAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Tests @see ProviderControllerTest
 */
class ProviderController extends Controller
{
    public function redirect(string $driver): RedirectResponse
    {
        return Socialite::driver($driver)->redirect();
    }

    public function callback(string $driver): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver($driver)->user();
        } catch (Exception $exception) {
            Log::error("OAuth Error ($driver): {$exception->getMessage()}");

            return redirect()->route('login')->withErrors([
                'email' => __('Authentication via :driver failed. Please try again.', ['driver' => ucfirst($driver)]),
            ]);
        }

        if (!$socialiteUser->getEmail()) {
            return redirect()->route('login')->withErrors([
                'email' => __('Email not received from :driver.', ['driver' => ucfirst($driver)]),
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

        if (!$user->avatar) {
            $user->update(['avatar' => $socialiteUser->getAvatar()]);
        }

        Auth::login($user);

        return redirect()->route('profile.dashboard');
    }
}
