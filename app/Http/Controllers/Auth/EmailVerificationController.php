<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function create(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('profile.dashboard'));
        }

        return view('auth.verify-email');
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function store(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('profile.dashboard'));
        }

        $request->fulfill();

        return redirect()->intended(route('profile.dashboard').'?verified=1');
    }

    /**
     * Send a new email verification notification.
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('profile.dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
