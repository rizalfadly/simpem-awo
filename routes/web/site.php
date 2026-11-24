<?php

use App\Http\Controllers\SiteContentController;
use App\Http\Controllers\SiteMessageController;
use App\Http\Controllers\SiteSettingsController;
use Illuminate\Support\Facades\Route;

// Website publik (sisi admin): pengaturan, kurasi konten, pesan masuk
Route::prefix('site')->name('site.admin.')->group(function () {
    Route::get('/settings', [SiteSettingsController::class, 'edit'])->name('settings')->middleware('can:site.manage');
    Route::put('/settings', [SiteSettingsController::class, 'update'])->name('settings.update')->middleware('can:site.manage');
    Route::get('/content', [SiteContentController::class, 'index'])->name('content')->middleware('can:content.publish');
    Route::post('/content/{type}/{id}', [SiteContentController::class, 'toggle'])->whereNumber('id')->name('content.toggle')->middleware('can:content.publish');
    Route::get('/messages', [SiteMessageController::class, 'index'])->name('messages')->middleware('can:site.manage');
    Route::post('/messages/{message}/handle', [SiteMessageController::class, 'handle'])->name('messages.handle')->middleware('can:site.manage');
    Route::delete('/messages/{message}', [SiteMessageController::class, 'destroy'])->name('messages.destroy')->middleware('can:site.manage');
});
