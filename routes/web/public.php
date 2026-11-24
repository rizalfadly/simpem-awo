<?php

use App\Http\Controllers\PublicMediaController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

// ---------- Website publik (tanpa login) ----------
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/', [PublicSiteController::class, 'home'])->name('site.home');
    Route::get('/tentang', [PublicSiteController::class, 'about'])->name('site.about');
    Route::get('/struktur-pengurus', [PublicSiteController::class, 'structure'])->name('site.structure');
    Route::get('/kegiatan', [PublicSiteController::class, 'activitiesIndex'])->name('site.activities');
    Route::get('/kegiatan/{slug}', [PublicSiteController::class, 'activity'])->name('site.activity');
    Route::get('/berita', [PublicSiteController::class, 'newsIndex'])->name('site.news');
    Route::get('/berita/{slug}', [PublicSiteController::class, 'newsShow'])->name('site.news.show');
    Route::get('/galeri', [PublicSiteController::class, 'gallery'])->name('site.gallery');
    Route::get('/galeri/{album}', [PublicSiteController::class, 'album'])->whereNumber('album')->name('site.album');
    Route::get('/dokumen', [PublicSiteController::class, 'documentsIndex'])->name('site.documents');
    Route::get('/dokumen/{document}/unduh', [PublicSiteController::class, 'download'])->whereNumber('document')->name('site.documents.download');
    Route::get('/mitra', [PublicSiteController::class, 'partnersIndex'])->name('site.partners');
    Route::get('/mitra/{slug}', [PublicSiteController::class, 'partner'])->name('site.partner');
    Route::get('/kontak', [PublicSiteController::class, 'contact'])->name('site.contact');
});
Route::post('/kontak', [PublicSiteController::class, 'send'])->middleware('throttle:5,1')->name('site.contact.send');

// Berkas publik: setiap permintaan memeriksa ulang bahwa induknya memang dipilih publik.
Route::middleware('throttle:600,1')->prefix('publik')->group(function () {
    Route::get('/media/{media}', [PublicMediaController::class, 'media'])->whereNumber('media');
    Route::get('/berita/{post}/sampul', [PublicMediaController::class, 'newsCover'])->whereNumber('post');
    Route::get('/mitra/{partner}/logo', [PublicMediaController::class, 'partnerLogo'])->whereNumber('partner');
    Route::get('/mitra/produk/{product}/foto', [PublicMediaController::class, 'productPhoto'])->whereNumber('product');
    Route::get('/situs/{kind}', [PublicMediaController::class, 'site']);
});