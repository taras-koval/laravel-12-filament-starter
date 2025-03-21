<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\OAuth\ProviderController;
use App\Http\Controllers\Auth\PasswordConfirmationController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('forgot-password');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:3,1');

    Route::get('reset-password/{token}', [ForgotPasswordController::class, 'createReset'])->name('password.reset');
    Route::post('reset-password', [ForgotPasswordController::class, 'storeReset'])->name('password.store');

    // OAuth
    Route::get('/auth/{driver}/redirect', [ProviderController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{driver}/callback', [ProviderController::class, 'callback'])->name('auth.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationController::class, 'create'])->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'store'])
        ->middleware(['signed', 'throttle:3,1'])->name('verification.verify');

    Route::post('verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware(['throttle:3,1'])->name('verification.send');

    Route::get('confirm-password', [PasswordConfirmationController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [PasswordConfirmationController::class, 'store'])->middleware('throttle:6,1');

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
