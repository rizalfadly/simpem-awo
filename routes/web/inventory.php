<?php

use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetLoanController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

// Inventaris & aset (akses dicek lewat izin & policy)
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/create', [InventoryController::class, 'create'])->name('create')->middleware('can:assets.manage');
    Route::post('/', [InventoryController::class, 'store'])->name('store')->middleware('can:assets.manage');

    // Peminjaman (didefinisikan sebelum '/{asset}' agar kata "loans" tidak dianggap id barang)
    Route::get('/loans', [AssetLoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [AssetLoanController::class, 'create'])->name('loans.create');
    Route::get('/availability', [AssetLoanController::class, 'availability'])->name('availability');
    Route::post('/loans', [AssetLoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/{loan}', [AssetLoanController::class, 'show'])->name('loans.show');
    Route::post('/loans/{loan}/approve', [AssetLoanController::class, 'approve'])->name('loans.approve')->middleware('can:assets.loans');
    Route::post('/loans/{loan}/reject', [AssetLoanController::class, 'reject'])->name('loans.reject')->middleware('can:assets.loans');
    Route::post('/loans/{loan}/cancel', [AssetLoanController::class, 'cancel'])->name('loans.cancel');
    Route::post('/loans/{loan}/handover', [AssetLoanController::class, 'handover'])->name('loans.handover')->middleware('can:assets.loans');
    Route::post('/loans/{loan}/return', [AssetLoanController::class, 'giveBack'])->name('loans.return')->middleware('can:assets.loans');

    Route::middleware('can:assets.manage')->group(function () {
        Route::get('/categories', [AssetCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AssetCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [AssetCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AssetCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::get('/{asset}', [InventoryController::class, 'show'])->name('show');
    Route::get('/{asset}/photo', [InventoryController::class, 'photo'])->name('photo');
    Route::get('/{asset}/edit', [InventoryController::class, 'edit'])->name('edit')->middleware('can:assets.manage');
    Route::put('/{asset}', [InventoryController::class, 'update'])->name('update')->middleware('can:assets.manage');
    Route::post('/{asset}/retire', [InventoryController::class, 'retire'])->name('retire')->middleware('can:assets.manage');
    Route::post('/{asset}/reactivate', [InventoryController::class, 'reactivate'])->name('reactivate')->middleware('can:assets.manage');
    Route::delete('/{asset}', [InventoryController::class, 'destroy'])->name('destroy')->middleware('can:assets.manage');
});
