<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FormReviewController;
use App\Http\Controllers\FormReviewRedirectController;
use App\Http\Controllers\Approval\OfficerApprovalController;
use App\Http\Controllers\Approval\ManagerApprovalController;
use App\Http\Controllers\Approval\AvpApprovalController;
use App\Http\Controllers\ActionItemController;
use App\Http\Controllers\ReviewStepController;
use App\Http\Controllers\KmController;
use App\Http\Middleware\RoleAccess;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\Approval\SirkulirUploadController;

Route::get('/', fn () => redirect('/login'));

// === Dashboard ===
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// === Group Middleware Auth ===
Route::middleware(['auth'])->group(function () {

    // === Profile ===
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === Document ===
    Route::get('/document', [DocumentController::class, 'index'])->name('document');
    Route::get('/document/create', [DocumentController::class, 'create'])->name('document.create');
    Route::post('/document/store', [DocumentController::class, 'store'])->name('document.store');
    Route::get('/document/download/{id}', [DocumentController::class, 'downloadWithWatermark'])->name('document.download');
    Route::get('/document/{id}', [DocumentController::class, 'show'])->name('document.show');
    Route::get('/document/{id}/edit', [DocumentController::class, 'edit'])
        ->middleware(RoleAccess::class)
        ->name('document.edit');
    Route::put('/document/{id}', [DocumentController::class, 'update'])->name('document.update');
    Route::get('/document/update-version/{id}/{is_edit}', [DocumentController::class, 'edit'])->name('document.updateVersion');
    Route::delete('/document/{id}', [DocumentController::class, 'destroy'])->name('document.destroy');
    Route::post('/document/check-nomor', [DocumentController::class, 'checkNomor'])->name('document.checkNomor');


    // === Form Review ===
    Route::get('/form_review', [FormReviewRedirectController::class, 'redirect'])->name('form_review.index');

    Route::get('/review', [FormReviewController::class, 'index'])->name('form_review.raw');
    Route::get('/review/create', [FormReviewController::class, 'create'])->name('form_review.create');
    Route::post('/review/store', [FormReviewController::class, 'store'])->name('form_review.store');
    Route::get('/review/{id}', [FormReviewController::class, 'show'])->name('form_review.show');
    Route::get('/review/{id}/edit', [FormReviewController::class, 'edit'])->name('form_review.edit');
    Route::put('/review/{id}', [FormReviewController::class, 'update'])->name('form_review.update');
    Route::delete('/review/{id}', [FormReviewController::class, 'destroy'])->name('form_review.destroy');

    Route::get('/form-review/download/{id}', [FormReviewController::class, 'downloadFile'])->name('form_review.download');

    // Lihat & Generate PDF (dipakai Officer/Manager/AVP)
    Route::get('/form-review/{id}/pdf', [FormReviewController::class, 'viewPdf'])->name('form_review.pdf');
    Route::post('/form-review/{id}/generate-pdf', [FormReviewController::class, 'generatePdf'])->name('form_review.generate_pdf');

    /**
     * === Upload “Add File” oleh BPO dari halaman index (modal) ===
     * NOTE:
     *   - Nama route yang dipanggil di Blade: form_review.bpo.upload  (pakai TITIK)
     *   - Diletakkan DI LUAR prefix('approval') agar URL-nya /review/{id}/bpo-upload
     *   - Kalau pakai spatie/laravel-permission, aktifkan middleware('role:bpo')
     */
    Route::post('/review/{id}/bpo-upload', [FormReviewController::class, 'uploadByBpo'])
        // ->middleware('role:bpo')
        ->name('form_review.bpo.upload');

    /** === STREAM / LIHAT file BPO (supaya tombol "Lihat" tidak 404) === */
    Route::get('/review/bpo-file/{file}', [FormReviewController::class, 'streamBpoFile'])
        ->name('form_review.bpo_file');

    // === Approval ===
    Route::prefix('approval')->group(function () {

        // Officer
        Route::get('/officer', [OfficerApprovalController::class, 'index'])->name('approval.officer.index');
        Route::get('/officer/{id}', [OfficerApprovalController::class, 'show'])->name('approval.officer.show');
        Route::post('/officer/{id}/setujui', [OfficerApprovalController::class, 'setujui'])->name('approval.officer.setujui');
        Route::post('/officer/{id}/approve', [OfficerApprovalController::class, 'approve'])->name('approval.officer.approve');
        Route::post('/officer/{id}/tolak', [OfficerApprovalController::class, 'tolak'])->name('approval.officer.tolak');

        // Manager
        Route::get('/manager', [ManagerApprovalController::class, 'index'])->name('approval.manager.index');
        Route::get('/manager/{id}', [ManagerApprovalController::class, 'show'])->name('approval.manager.show');
        Route::post('/manager/{id}/setujui', [ManagerApprovalController::class, 'setujui'])->name('approval.manager.setujui');
        Route::post('/manager/{id}/approve', [ManagerApprovalController::class, 'approve'])->name('approval.manager.approve');
        Route::post('/manager/{id}/tolak', [ManagerApprovalController::class, 'tolak'])->name('approval.manager.tolak');

        // AVP
        Route::get('/avp', [AvpApprovalController::class, 'index'])->name('approval.avp.index');
        Route::get('/avp/{id}', [AvpApprovalController::class, 'show'])->name('approval.avp.show');
        Route::post('/avp/{id}/setujui', [AvpApprovalController::class, 'setujui'])->name('approval.avp.setujui');
        Route::post('/avp/{id}/approve', [AvpApprovalController::class, 'approve'])->name('approval.avp.approve');
        Route::post('/avp/{id}/tolak', [AvpApprovalController::class, 'tolak'])->name('approval.avp.tolak');

        // === Upload sirkulir (Officer/Manager/AVP) ===
        Route::post('/officer/{id}/sirkulir-upload', [SirkulirUploadController::class, 'upload'])
            ->name('approval.officer.sirkulir.upload')
            ->defaults('role', 'officer');

        Route::post('/manager/{id}/sirkulir-upload', [SirkulirUploadController::class, 'upload'])
            ->name('approval.manager.sirkulir.upload')
            ->defaults('role', 'manager');

        Route::post('/avp/{id}/sirkulir-upload', [SirkulirUploadController::class, 'upload'])
            ->name('approval.avp.sirkulir.upload')
            ->defaults('role', 'avp');

        /**
         * (Opsional) route lama dengan nama underscore — biar tidak putus
         * URL: /approval/review/{id}/bpo-upload
         */
        Route::post('/review/{id}/bpo-upload', [FormReviewController::class, 'uploadByBpo'])
            // ->middleware('role:bpo')
            ->name('form_review.bpo_upload');
    });

    // === Action Item ===
    Route::prefix('action-items')->name('ai.')->group(function () {
    Route::get('/',       [ActionItemController::class, 'index'])->name('index');
    Route::get('/create', [ActionItemController::class, 'create'])->name('create');
    Route::post('/',      [ActionItemController::class, 'store'])->name('store');
    Route::get('/{ai}',   [ActionItemController::class, 'show'])->name('show');
    Route::patch('{ai}/cancel', [ActionItemController::class, 'cancel'])->name('cancel');
    Route::patch('{ai}/hold',   [ActionItemController::class, 'hold'])->name('hold');
    Route::patch('{ai}/resume', [ActionItemController::class, 'resume'])->name('resume');

        Route::middleware('role:bpo')->group(function () {
            Route::patch('/{ai}/start-progress', [ActionItemController::class, 'startProgress'])->name('startProgress');
            Route::post('/{ai}/lampiran',        [ActionItemController::class, 'uploadLampiranAndRequestClose'])->name('lampiran');
            Route::post('/{ai}/request-status',  [ActionItemController::class, 'requestStatus'])->name('requestStatus');
        });
    });

    // === Review Step (Approve/Reject) ===
    Route::middleware('role:officer|manager|avp')->group(function () {
        Route::post('review-step/{step}/approve', [ReviewStepController::class, 'approve'])->name('review_step.approve');
        Route::post('review-step/{step}/reject',  [ReviewStepController::class, 'reject'])->name('review_step.reject');
    });

    // === KM ===
    Route::get('/km', [KmController::class, 'index'])->name('km.index');
});

// === Auth Routes ===
require __DIR__ . '/auth.php';
