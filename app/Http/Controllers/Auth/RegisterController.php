<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

// TODO: Google reCAPTCHA
/**
 * Tests @see RegisterControllerTest
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function storeAjax(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);
        $user->assignRole(UserRoleEnum::USER);

        event(new Registered($user));

        Auth::login($user);

        $redirect = $user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()
            ? route('verification.notice')
            : route('profile.dashboard');

        return response()->json(['redirect' => $redirect]);
    }
}
