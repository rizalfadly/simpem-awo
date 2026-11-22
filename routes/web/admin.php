<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Akun pengguna (akun baru dibuat dari halaman Anggota)
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:users.view');
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
