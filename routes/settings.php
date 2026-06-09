<?php

use App\Http\Controllers\Settings\CongregationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SessionController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/sessions', [SessionController::class, 'edit'])->name('sessions.edit');
    Route::delete('settings/sessions', [SessionController::class, 'destroy'])
        ->middleware('throttle:6,1')
        ->name('sessions.destroy');

    Route::get('settings/congregations', [CongregationController::class, 'index'])->name('congregations.index');
    Route::get('settings/congregations/{congregation}', [CongregationController::class, 'edit'])->name('congregations.edit');
    Route::patch('settings/congregations/{congregation}', [CongregationController::class, 'update'])->name('congregations.update');
});
