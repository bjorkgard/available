<?php

use App\Http\Controllers\Congregations\CongregationController;
use App\Http\Controllers\Congregations\InvitationAcceptController;
use App\Http\Controllers\Congregations\KingdomHallController;
use App\Http\Controllers\Congregations\MemberController;
use App\Http\Controllers\Congregations\SetupWizardController;
use App\Http\Middleware\EnsureCongregationMembership;
use App\Http\Middleware\EnsureHasKingdomHall;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('setup', [SetupWizardController::class, 'show'])->name('setup.show');
    Route::post('setup', [SetupWizardController::class, 'store'])->name('setup.store');
});

Route::prefix('{current_congregation}')
    ->middleware(['auth', 'verified', EnsureCongregationMembership::class, EnsureHasKingdomHall::class])
    ->group(function () {
        Route::inertia('dashboard', 'dashboard')->name('dashboard');

        Route::middleware(EnsureCongregationMembership::class.':admin')->group(function () {
            Route::get('members', [MemberController::class, 'index'])->name('members.index');
            Route::post('members/invite', [MemberController::class, 'invite'])->name('members.invite');
            Route::delete('members/invitations/{invitation}', [MemberController::class, 'destroyInvitation'])->name('members.invitations.destroy');
            Route::put('members/{member}', [MemberController::class, 'update'])->name('members.update');
            Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

            Route::get('congregation', [CongregationController::class, 'edit'])->name('congregation.edit');
            Route::patch('congregation', [CongregationController::class, 'update'])->name('congregation.update');
        });

        Route::middleware(EnsureCongregationMembership::class.':superadmin')->group(function () {
            Route::get('kingdom-hall', [KingdomHallController::class, 'show'])->name('kingdom-hall.show');
            Route::put('kingdom-hall', [KingdomHallController::class, 'update'])->name('kingdom-hall.update');
            Route::delete('kingdom-hall', [KingdomHallController::class, 'destroy'])->name('kingdom-hall.destroy');
            Route::post('kingdom-hall/congregations', [KingdomHallController::class, 'addCongregation'])->name('kingdom-hall.add-congregation');
        });

        Route::post('move', [CongregationController::class, 'move'])->name('congregation.move');
        Route::delete('/', [CongregationController::class, 'destroy'])->name('congregation.destroy');
    });

Route::get('invitations/{invitation}/accept', [InvitationAcceptController::class, 'accept'])->name('invitations.accept');
Route::post('invitations/{invitation}/accept', [InvitationAcceptController::class, 'store'])->name('invitations.accept.store');

require __DIR__.'/settings.php';
