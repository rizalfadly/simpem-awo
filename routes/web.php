<?php

use App\Http\Controllers\ActivityBudgetController;
use App\Http\Controllers\ActivityCommitteeController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\ActivityTimelineController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberAssignmentController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MemberCardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// Publik: tujuan QR Code pada kartu anggota
Route::get('/verifikasi/{token}', VerificationController::class)->middleware('throttle:60,1')->name('verify');

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
});
