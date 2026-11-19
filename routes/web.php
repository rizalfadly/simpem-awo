<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

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

    // Akun pengguna
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:users.view');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('can:users.create');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:users.create');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('can:users.update');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('can:users.update');
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active')->middleware('can:users.update');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password')->middleware('can:users.reset-password');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:users.delete');
    });

    // Role & permission
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('can:roles.view');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('can:roles.manage');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('can:roles.manage');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('can:roles.manage');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('can:roles.manage');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('can:roles.manage');
    });

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index')->middleware('can:audit.view');
});
