<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Tests\Feature\Profile\AccountControllerTest;

/**
 * Tests @see AccountControllerTest
 */
class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('profile.dashboard', ['user' => $request->user()]);
    }

    public function edit(Request $request): View
    {
        return view('profile.account', ['user' => $request->user()]);
    }

    public function updateAjax(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;

            if ($user instanceof MustVerifyEmail) {
                $user->sendEmailVerificationNotification();
            }
        }

        $user->save();

        return response()->json([
            'status' => 'profile-updated',
            'message' => __('Profile updated successfully.'),
        ]);
    }

    public function updatePasswordAjax(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json([
            'status' => 'password-updated',
            'message' => __('Password updated successfully.'),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
