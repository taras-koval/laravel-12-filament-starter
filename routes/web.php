<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\Profile\AccountController;
use App\Http\Controllers\Profile\DashboardController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::middleware(['auth', 'verified'])->prefix('profile')->name('profile.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Account
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/account', [AccountController::class, 'update'])->withoutMiddleware('verified')->middleware('throttle:5,1')->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.update-password');
    Route::delete('/account', [AccountController::class, 'destroy'])->middleware('password.confirm')->name('account.destroy');
});
