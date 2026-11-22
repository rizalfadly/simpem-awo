<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// Publik: tujuan QR Code pada kartu anggota
Route::get('/verifikasi/{token}', VerificationController::class)->middleware('throttle:60,1')->name('verify');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // Modul-modul lain dipisah per file di routes/web/.
    // Semuanya otomatis mewarisi middleware 'auth' + EnsureUserIsActive dari grup ini.
    Route::group([], __DIR__.'/web/members.php');
    Route::group([], __DIR__.'/web/activities.php');
    Route::group([], __DIR__.'/web/documentation.php');
    Route::group([], __DIR__.'/web/finance.php');
    Route::group([], __DIR__.'/web/inventory.php');
    Route::group([], __DIR__.'/web/partners.php');
    Route::group([], __DIR__.'/web/admin.php');
});
