<?php

use App\Http\Controllers\FinanceCategoryController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FinanceDisputeController;
use App\Http\Controllers\FinanceTransactionController;
use Illuminate\Support\Facades\Route;

// Keuangan transparan (akses dicek lewat izin & policy)
Route::prefix('finance')->name('finance.')->group(function () {
    Route::get('/', [FinanceController::class, 'index'])->name('index');
    Route::get('/reports/{report}', [FinanceController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/submit', [FinanceController::class, 'submit'])->name('reports.submit')->middleware('can:finance.record');
    Route::post('/reports/{report}/review', [FinanceController::class, 'review'])->name('reports.review')->middleware('can:finance.review');
    Route::post('/reports/{report}/approve', [FinanceController::class, 'approve'])->name('reports.approve')->middleware('can:finance.approve');
    Route::post('/reports/{report}/publish', [FinanceController::class, 'publish'])->name('reports.publish')->middleware('can:finance.publish');
    Route::post('/reports/{report}/disputes', [FinanceDisputeController::class, 'store'])->name('disputes.store');

    Route::get('/transactions', [FinanceTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [FinanceTransactionController::class, 'create'])->name('transactions.create')->middleware('can:finance.record');
    Route::post('/transactions', [FinanceTransactionController::class, 'store'])->name('transactions.store')->middleware('can:finance.record');
    Route::get('/transactions/{transaction}', [FinanceTransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/edit', [FinanceTransactionController::class, 'edit'])->name('transactions.edit')->middleware('can:finance.record');
    Route::put('/transactions/{transaction}', [FinanceTransactionController::class, 'update'])->name('transactions.update')->middleware('can:finance.record');
    Route::delete('/transactions/{transaction}', [FinanceTransactionController::class, 'destroy'])->name('transactions.destroy')->middleware('can:finance.record');
    Route::get('/transactions/{transaction}/proof', [FinanceTransactionController::class, 'proof'])->name('transactions.proof');

    Route::middleware('can:finance.record')->group(function () {
        Route::get('/categories', [FinanceCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [FinanceCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [FinanceCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [FinanceCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::get('/disputes', [FinanceDisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/{dispute}', [FinanceDisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{dispute}/clarify', [FinanceDisputeController::class, 'clarify'])->name('disputes.clarify')->middleware('can:finance.record');
    Route::post('/disputes/{dispute}/review', [FinanceDisputeController::class, 'review'])->name('disputes.review')->middleware('can:finance.review');
    Route::post('/disputes/{dispute}/decide', [FinanceDisputeController::class, 'decide'])->name('disputes.decide')->middleware('can:finance.approve');
    Route::post('/disputes/{dispute}/withdraw', [FinanceDisputeController::class, 'withdraw'])->name('disputes.withdraw');
    Route::get('/dispute-files/{file}', [FinanceDisputeController::class, 'file'])->name('disputes.file');
});
