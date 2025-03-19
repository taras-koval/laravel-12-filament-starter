<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Display the forgot password reset link request view.
     */
    public function create() : View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link to the given email address.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withInput($request->only('email'))->withErrors(['email' => trans($status)]);
        }

        return back()->with('status', trans($status));
    }

    /**
     * Display the password reset view.
     */
    public function createReset(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function storeReset(ResetPasswordRequest $request) : RedirectResponse
    {
        $status = Password::reset(
            credentials: $request->only('email', 'password', 'password_confirmation', 'token'),
            callback: static function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->validated('password')),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => trans($status)]);
        }

        if (! Auth::attempt(credentials: $request->only('email', 'password'), remember: true)) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->route('profile.dashboard');
    }
}
