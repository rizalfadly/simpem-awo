<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

// Dokumentasi & publikasi (akses dicek lewat policy di controller)
Route::prefix('albums')->name('albums.')->group(function () {
    Route::get('/', [AlbumController::class, 'index'])->name('index');
    Route::get('/create', [AlbumController::class, 'create'])->name('create');
    Route::post('/', [AlbumController::class, 'store'])->name('store');
    Route::get('/{album}', [AlbumController::class, 'show'])->name('show');
    Route::get('/{album}/edit', [AlbumController::class, 'edit'])->name('edit');
    Route::put('/{album}', [AlbumController::class, 'update'])->name('update');
    Route::delete('/{album}', [AlbumController::class, 'destroy'])->name('destroy');
    Route::post('/{album}/status', [AlbumController::class, 'status'])->name('status');
    Route::post('/{album}/cover', [AlbumController::class, 'cover'])->name('cover');
    Route::post('/{album}/photos', [MediaController::class, 'storePhotos'])->name('photos.store');
    Route::post('/{album}/videos', [MediaController::class, 'storeVideo'])->name('videos.store');
});
Route::put('/media-items/{media}', [MediaController::class, 'update'])->name('media.update');
Route::delete('/media-items/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
Route::get('/media-items/{media}/file', [MediaController::class, 'file'])->name('media.file');
Route::get('/media-items/{media}/thumb', [MediaController::class, 'thumb'])->name('media.thumb');

Route::prefix('news')->name('news.')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('index');
    Route::get('/create', [NewsController::class, 'create'])->name('create')->middleware('can:news.manage');
    Route::post('/', [NewsController::class, 'store'])->name('store')->middleware('can:news.manage');
    Route::get('/{post}', [NewsController::class, 'show'])->name('show');
    Route::get('/{post}/edit', [NewsController::class, 'edit'])->name('edit');
    Route::put('/{post}', [NewsController::class, 'update'])->name('update');
    Route::delete('/{post}', [NewsController::class, 'destroy'])->name('destroy');
    Route::post('/{post}/status', [NewsController::class, 'status'])->name('status');
    Route::get('/{post}/cover', [NewsController::class, 'cover'])->name('cover');
});

Route::prefix('archive')->name('archive.')->group(function () {
    Route::get('/', [ArchiveController::class, 'index'])->name('index');
    Route::get('/create', [ArchiveController::class, 'create'])->name('create')->middleware('can:archive.manage');
    Route::post('/', [ArchiveController::class, 'store'])->name('store')->middleware('can:archive.manage');
    Route::get('/{document}/edit', [ArchiveController::class, 'edit'])->name('edit')->middleware('can:archive.manage');
    Route::put('/{document}', [ArchiveController::class, 'update'])->name('update')->middleware('can:archive.manage');
    Route::delete('/{document}', [ArchiveController::class, 'destroy'])->name('destroy')->middleware('can:archive.manage');
    Route::get('/{document}/download', [ArchiveController::class, 'download'])->name('download');
});
