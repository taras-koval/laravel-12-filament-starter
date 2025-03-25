<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuth\ProviderController;
use App\Http\Controllers\Auth\PasswordConfirmationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\Profile\AccountController;
use App\Http\Controllers\Profile\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::middleware(['auth', 'verified'])->prefix('profile')->name('profile.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Account
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/account', [AccountController::class, 'updateAjax'])->withoutMiddleware('verified')->middleware('throttle:5,1')->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePasswordAjax'])->name('account.update-password');
    Route::delete('/account', [AccountController::class, 'destroy'])->middleware('password.confirm')->name('account.destroy');
});


/**
 * Authentication
 */
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'storeAjax']);

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'storeAjax']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('forgot-password');
    Route::post('forgot-password', [ForgotPasswordController::class, 'storeAjax'])->middleware('throttle:3,1');

    Route::get('reset-password/{token}', [ForgotPasswordController::class, 'createReset'])->name('password.reset');
    Route::post('reset-password', [ForgotPasswordController::class, 'storeResetAjax'])->name('password.store');

    // OAuth
    Route::get('/auth/{driver}/redirect', [ProviderController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{driver}/callback', [ProviderController::class, 'callback'])->name('auth.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationController::class, 'create'])->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'store'])
        ->middleware('signed')->name('verification.verify');

    Route::post('verify-email/resend', [EmailVerificationController::class, 'resendAjax'])
        ->middleware('throttle:3,1')->name('verification.send');

    Route::get('confirm-password', [PasswordConfirmationController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [PasswordConfirmationController::class, 'store'])->middleware('throttle:6,1');

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
