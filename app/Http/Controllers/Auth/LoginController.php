<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// TODO: Google reCAPTCHA
// TODO: Two-factor authentication
/**
 * Tests @see LoginControllerTest
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function storeAjax(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return response()->json([
            'redirect' => session()?->pull('url.intended', route('profile.dashboard')),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
