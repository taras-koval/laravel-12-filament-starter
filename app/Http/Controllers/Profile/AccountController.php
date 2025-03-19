<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

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

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
            $request->user()->sendEmailVerificationNotification();
        }

        $request->user()->save();

        return back()->with('status', 'profile-updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('status', 'password-updated');
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
