<?php

use App\Http\Controllers\MemberAssignmentController;
use App\Http\Controllers\MemberCardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\StructureController;
use Illuminate\Support\Facades\Route;

// Terbuka untuk semua anggota yang login
Route::get('/struktur', StructureController::class)->name('structure');
Route::get('/kartu-saya', [MemberCardController::class, 'mine'])->name('my-card');
Route::get('/members/{member}/card', [MemberCardController::class, 'show'])->name('members.card');
Route::get('/members/{member}/qr', [MemberCardController::class, 'qr'])->name('members.qr');

// Keanggotaan
Route::prefix('members')->name('members.')->group(function () {
    Route::get('/', [MemberController::class, 'index'])->name('index')->middleware('can:members.view');
    Route::get('/create', [MemberController::class, 'create'])->name('create')->middleware('can:members.create');
    Route::post('/', [MemberController::class, 'store'])->name('store')->middleware('can:members.create');
    Route::get('/{member}', [MemberController::class, 'show'])->name('show')->middleware('can:members.view');
    Route::get('/{member}/edit', [MemberController::class, 'edit'])->name('edit')->middleware('can:members.update');
    Route::put('/{member}', [MemberController::class, 'update'])->name('update')->middleware('can:members.update');
    Route::patch('/{member}/status', [MemberController::class, 'changeStatus'])->name('status')->middleware('can:members.update');
    Route::post('/{member}/regenerate-qr', [MemberController::class, 'regenerateQr'])->name('regenerate-qr')->middleware('can:members.update');
    Route::post('/{member}/account', [MemberController::class, 'activateAccount'])->name('account')->middleware('can:members.activate-account');
    Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy')->middleware('can:members.delete');
    Route::post('/{member}/assignments', [MemberAssignmentController::class, 'store'])->name('assignments.store')->middleware('can:members.assign');
});
Route::patch('/assignments/{assignment}/end', [MemberAssignmentController::class, 'end'])->name('assignments.end')->middleware('can:members.assign');
Route::delete('/assignments/{assignment}', [MemberAssignmentController::class, 'destroy'])->name('assignments.destroy')->middleware('can:members.assign');

// Data master organisasi
Route::prefix('organisasi')->name('organization.')->group(function () {
    Route::get('/', [OrganizationController::class, 'index'])->name('index')->middleware('can:organization.view');

    Route::middleware('can:organization.manage')->group(function () {
        Route::post('/divisi', [OrganizationController::class, 'storeDivision'])->name('divisions.store');
        Route::put('/divisi/{division}', [OrganizationController::class, 'updateDivision'])->name('divisions.update');
        Route::delete('/divisi/{division}', [OrganizationController::class, 'destroyDivision'])->name('divisions.destroy');

        Route::post('/jabatan', [OrganizationController::class, 'storePosition'])->name('positions.store');
        Route::put('/jabatan/{position}', [OrganizationController::class, 'updatePosition'])->name('positions.update');
        Route::delete('/jabatan/{position}', [OrganizationController::class, 'destroyPosition'])->name('positions.destroy');

        Route::post('/periode', [OrganizationController::class, 'storePeriod'])->name('periods.store');
        Route::put('/periode/{period}', [OrganizationController::class, 'updatePeriod'])->name('periods.update');
        Route::delete('/periode/{period}', [OrganizationController::class, 'destroyPeriod'])->name('periods.destroy');
    });
});
