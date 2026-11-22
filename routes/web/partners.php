<?php

use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerProductController;
use App\Http\Controllers\PartnerPromotionController;
use App\Http\Controllers\PartnershipController;
use Illuminate\Support\Facades\Route;

// Kemitraan & UMKM (akses dicek lewat izin & policy)
Route::prefix('partners')->name('partners.')->group(function () {
    Route::get('/', [PartnerController::class, 'index'])->name('index');
    Route::get('/create', [PartnerController::class, 'create'])->name('create')->middleware('can:partners.manage');
    Route::post('/', [PartnerController::class, 'store'])->name('store')->middleware('can:partners.manage');

    // Jalur statis didefinisikan sebelum '/{partner}'
    Route::get('/promotions', [PartnerPromotionController::class, 'index'])->name('promotions.index');
    Route::put('/promotions/{promotion}', [PartnerPromotionController::class, 'update'])->name('promotions.update')->middleware('can:partners.manage');
    Route::post('/promotions/{promotion}/status', [PartnerPromotionController::class, 'status'])->name('promotions.status')->middleware('can:content.publish');
    Route::delete('/promotions/{promotion}', [PartnerPromotionController::class, 'destroy'])->name('promotions.destroy')->middleware('can:partners.manage');

    Route::get('/products/{product}/photo', [PartnerProductController::class, 'photo'])->name('products.photo');
    Route::put('/products/{product}', [PartnerProductController::class, 'update'])->name('products.update')->middleware('can:partners.manage');
    Route::delete('/products/{product}', [PartnerProductController::class, 'destroy'])->name('products.destroy')->middleware('can:partners.manage');

    Route::get('/partnerships', [PartnershipController::class, 'index'])->name('partnerships.index')->middleware('can:partners.view');
    Route::get('/partnerships/create', [PartnershipController::class, 'create'])->name('partnerships.create')->middleware('can:partners.manage');
    Route::post('/partnerships', [PartnershipController::class, 'store'])->name('partnerships.store')->middleware('can:partners.manage');
    Route::get('/partnerships/{partnership}', [PartnershipController::class, 'show'])->name('partnerships.show')->middleware('can:partners.view');
    Route::get('/partnerships/{partnership}/document', [PartnershipController::class, 'document'])->name('partnerships.document')->middleware('can:partners.view');
    Route::get('/partnerships/{partnership}/edit', [PartnershipController::class, 'edit'])->name('partnerships.edit')->middleware('can:partners.manage');
    Route::put('/partnerships/{partnership}', [PartnershipController::class, 'update'])->name('partnerships.update')->middleware('can:partners.manage');
    Route::post('/partnerships/{partnership}/status', [PartnershipController::class, 'status'])->name('partnerships.status')->middleware('can:partners.manage');
    Route::delete('/partnerships/{partnership}', [PartnershipController::class, 'destroy'])->name('partnerships.destroy')->middleware('can:partners.manage');

    Route::get('/{partner}', [PartnerController::class, 'show'])->name('show');
    Route::get('/{partner}/logo', [PartnerController::class, 'logo'])->name('logo');
    Route::get('/{partner}/edit', [PartnerController::class, 'edit'])->name('edit')->middleware('can:partners.manage');
    Route::put('/{partner}', [PartnerController::class, 'update'])->name('update')->middleware('can:partners.manage');
    Route::post('/{partner}/status', [PartnerController::class, 'status'])->name('status')->middleware('can:partners.manage');
    Route::delete('/{partner}', [PartnerController::class, 'destroy'])->name('destroy')->middleware('can:partners.manage');
    Route::post('/{partner}/products', [PartnerProductController::class, 'store'])->name('products.store')->middleware('can:partners.manage');
    Route::post('/{partner}/promotions', [PartnerPromotionController::class, 'store'])->name('promotions.store')->middleware('can:partners.manage');
});
