<?php

use App\Http\Controllers\ActivityBudgetController;
use App\Http\Controllers\ActivityCommitteeController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\ActivityTimelineController;
use Illuminate\Support\Facades\Route;

// Kegiatan & kepanitiaan (akses dicek di controller/policy: ringkasan terbuka, detail sesuai peran)
Route::prefix('activities')->name('activities.')->group(function () {
    Route::get('/', [ActivityController::class, 'index'])->name('index');
    Route::get('/create', [ActivityController::class, 'create'])->name('create')->middleware('can:activities.create');
    Route::post('/', [ActivityController::class, 'store'])->name('store')->middleware('can:activities.create');
    Route::get('/{activity}', [ActivityController::class, 'show'])->name('show');
    Route::get('/{activity}/edit', [ActivityController::class, 'edit'])->name('edit');
    Route::put('/{activity}', [ActivityController::class, 'update'])->name('update');
    Route::delete('/{activity}', [ActivityController::class, 'destroy'])->name('destroy')->middleware('can:activities.delete');

    Route::put('/{activity}/proposal', [ActivityController::class, 'updateProposal'])->name('proposal.update');
    Route::get('/{activity}/proposal/file', [ActivityController::class, 'downloadProposal'])->name('proposal.file');
    Route::post('/{activity}/submit', [ActivityController::class, 'submit'])->name('submit');
    Route::post('/{activity}/decision', [ActivityController::class, 'decide'])->name('decision')->middleware('can:activities.approve');
    Route::post('/{activity}/complete', [ActivityController::class, 'complete'])->name('complete');
    Route::post('/{activity}/cancel', [ActivityController::class, 'cancel'])->name('cancel');

    Route::post('/{activity}/committee', [ActivityCommitteeController::class, 'store'])->name('committee.store');
    Route::post('/{activity}/timeline', [ActivityTimelineController::class, 'store'])->name('timeline.store');
    Route::post('/{activity}/budget', [ActivityBudgetController::class, 'store'])->name('budget.store');

    Route::put('/{activity}/report', [ActivityReportController::class, 'save'])->name('report.save');
    Route::post('/{activity}/report/submit', [ActivityReportController::class, 'submit'])->name('report.submit');
    Route::post('/{activity}/report/review', [ActivityReportController::class, 'review'])->name('report.review')->middleware('can:activities.approve');
    Route::get('/{activity}/report/file', [ActivityReportController::class, 'download'])->name('report.file');
});
Route::put('/committee-members/{committeeMember}', [ActivityCommitteeController::class, 'update'])->name('committee.update');
Route::delete('/committee-members/{committeeMember}', [ActivityCommitteeController::class, 'destroy'])->name('committee.destroy');
Route::put('/timelines/{timeline}', [ActivityTimelineController::class, 'update'])->name('timeline.update');
Route::patch('/timelines/{timeline}/status', [ActivityTimelineController::class, 'status'])->name('timeline.status');
Route::delete('/timelines/{timeline}', [ActivityTimelineController::class, 'destroy'])->name('timeline.destroy');
Route::put('/budget-items/{budgetItem}', [ActivityBudgetController::class, 'update'])->name('budget.update');
Route::patch('/budget-items/{budgetItem}/actual', [ActivityBudgetController::class, 'actual'])->name('budget.actual');
Route::delete('/budget-items/{budgetItem}', [ActivityBudgetController::class, 'destroy'])->name('budget.destroy');
