<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdPlayerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostbackController;
use App\Http\Controllers\UserPanelController;
use App\Http\Controllers\WithdrawalController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

/* ---------------------------------------------------------------------
 | Public
 * ------------------------------------------------------------------- */

Route::get('/', function () {
    return view('home', [
        'siteName' => Setting::get('site_name', 'WatchEarn'),
        'tagline' => Setting::get('site_tagline', 'Watch. Earn. Withdraw.'),
    ]);
})->name('home');

/* ---------------------------------------------------------------------
 | CPAGrip postback (public, protected by the shared secret key)
 * ------------------------------------------------------------------- */

Route::match(['get', 'post'], '/postback/cpagrip', [PostbackController::class, 'cpagrip'])
    ->name('postback.cpagrip');

/* ---------------------------------------------------------------------
 | Auth
 * ------------------------------------------------------------------- */

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/verify-email', [AuthController::class, 'showVerify'])->name('verification.notice');
Route::post('/verify-email', [AuthController::class, 'verify'])->name('verification.verify');
Route::post('/verify-email/resend', [AuthController::class, 'resendCode'])->name('verification.resend');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('logout');

/* ---------------------------------------------------------------------
 | User panel (verified + active accounts only)
 * ------------------------------------------------------------------- */

Route::middleware(['auth', 'active.account', 'verified.email'])->group(function () {
    Route::get('/dashboard', [UserPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/tasks', [UserPanelController::class, 'tasks'])->name('tasks');
    Route::get('/offerwall', [UserPanelController::class, 'offerwall'])->name('offerwall');

    Route::get('/tasks/{task}/watch', [AdPlayerController::class, 'start'])->name('tasks.watch');
    Route::get('/watch/embed/{token}', [AdPlayerController::class, 'embed'])->name('tasks.embed');
    Route::post('/tasks/{task}/complete', [AdPlayerController::class, 'complete'])->name('tasks.complete');

    Route::get('/wallet', [UserPanelController::class, 'wallet'])->name('wallet');
    Route::get('/referrals', [UserPanelController::class, 'referrals'])->name('referrals');

    Route::get('/withdraw', [WithdrawalController::class, 'index'])->name('withdraw.index');
    Route::post('/withdraw', [WithdrawalController::class, 'store'])->name('withdraw.store');
});

/* ---------------------------------------------------------------------
 | Admin panel
 * ------------------------------------------------------------------- */

Route::middleware(['auth', 'active.account', 'admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}', [AdminController::class, 'userShow'])->name('users.show');
        Route::post('/users/{user}/adjust-balance', [AdminController::class, 'userAdjustBalance'])->name('users.adjust');
        Route::post('/users/{user}/toggle-status', [AdminController::class, 'userToggleStatus'])->name('users.toggle');

        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/password', [AdminController::class, 'updatePassword'])->name('settings.password');

        Route::get('/cpagrip', [AdminController::class, 'cpagrip'])->name('cpagrip');
        Route::post('/cpagrip', [AdminController::class, 'updateCpagrip'])->name('cpagrip.update');
        Route::post('/cpagrip/regenerate-secret', [AdminController::class, 'regenerateSecret'])->name('cpagrip.secret');

        Route::get('/tasks', [AdminController::class, 'tasks'])->name('tasks');
        Route::get('/tasks/create', [AdminController::class, 'taskCreate'])->name('tasks.create');
        Route::post('/tasks', [AdminController::class, 'taskStore'])->name('tasks.store');
        Route::get('/tasks/{task}/edit', [AdminController::class, 'taskEdit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [AdminController::class, 'taskUpdate'])->name('tasks.update');
        Route::delete('/tasks/{task}', [AdminController::class, 'taskDestroy'])->name('tasks.destroy');

        Route::get('/withdrawals', [AdminController::class, 'withdrawals'])->name('withdrawals');
        Route::post('/withdrawals/{withdrawal}/approve', [AdminController::class, 'withdrawalApprove'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [AdminController::class, 'withdrawalReject'])->name('withdrawals.reject');
    });

/* ---------------------------------------------------------------------
 | Favicon - the site logo (blue rounded square, white play mark),
 | served through Laravel because Vercel routes every request here.
 * ------------------------------------------------------------------- */
Route::get('/favicon.svg', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#1d55f1"/><path d="M26 20.5v23l19-11.5z" fill="#ffffff"/></svg>';
    return response($svg, 200, [
        'Content-Type' => 'image/svg+xml',
        'Cache-Control' => 'public, max-age=604800',
    ]);
});
Route::get('/favicon.ico', fn () => redirect('/favicon.svg'));
