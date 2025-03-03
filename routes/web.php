<?php

use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', [IndexController::class, 'index'])->name('index');
