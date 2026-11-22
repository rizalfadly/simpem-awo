<?php

use App\Http\Controllers\ActivityBudgetController;
use App\Http\Controllers\ActivityCommitteeController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\ActivityTimelineController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetLoanController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceCategoryController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FinanceDisputeController;
use App\Http\Controllers\FinanceTransactionController;
use App\Http\Controllers\MemberAssignmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MemberCardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerProductController;
use App\Http\Controllers\PartnerPromotionController;
use App\Http\Controllers\PartnershipController;
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
