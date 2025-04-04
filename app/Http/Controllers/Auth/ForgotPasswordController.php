<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests @see ForgotPasswordControllerTest
 */
class ForgotPasswordController extends Controller
{
    /**
     * Display the forgot password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link to the given email address.
     */
    public function storeAjax(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'errors' => [
                    'email' => [trans($status)],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => trans($status)]);
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
    public function storeResetAjax(ResetPasswordRequest $request): JsonResponse
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
            return response()->json([
                'errors' => ['password' => [trans($status)]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Auth::loginUsingId(User::where('email', $request->input('email'))->value('id'));

        $request->session()->regenerate();

        return response()->json(['redirect' => route('profile.dashboard')]);
    }
}
