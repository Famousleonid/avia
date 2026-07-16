<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Android\AndroidApiController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\Mobile\MobileApiController;
use App\Http\Controllers\Api\QuantumRoSyncController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('archive')
    ->middleware('archive.token')
    ->group(function () {
        Route::get('/pending-media', [ArchiveController::class, 'pendingMedia'])
            ->name('archive.pending-media');
        Route::get('/download/{media}', [ArchiveController::class, 'download'])
            ->name('archive.download');
        Route::post('/mark-synced', [ArchiveController::class, 'markSynced'])
            ->name('archive.mark-synced');
    });

// Shared route map of the mobile clients. Registered twice: the iOS contour
// (/api/mobile/*, MobileApiController — source of truth) and the Android
// contour (/api/android/*, AndroidApiController extends the iOS one and
// overrides only platform-specific behavior). URIs/names of the iOS contour
// are unchanged.
$registerMobileClientRoutes = function (string $controller) {
    Route::get('/public/app-config', [$controller, 'publicAppConfig'])->name('public.app-config');
    Route::post('/auth/login', [$controller, 'login'])->name('auth.login');

    Route::middleware('mobile.api')->group(function () use ($controller) {
        Route::get('/me', [$controller, 'me'])->name('me');
        Route::get('/bootstrap', [$controller, 'bootstrap'])->name('bootstrap');
        Route::post('/auth/logout', [$controller, 'logout'])->name('auth.logout');
        Route::get('/profile', [$controller, 'profile'])->name('profile.show');
        Route::put('/profile', [$controller, 'updateProfile'])->name('profile.update');
        Route::post('/profile/password', [$controller, 'updatePassword'])->name('profile.password.update');

        Route::get('/media/{media}/thumb', [$controller, 'mediaFile'])
            ->defaults('variant', 'thumb')
            ->name('media.thumb');
        Route::get('/media/{media}/file', [$controller, 'mediaFile'])
            ->defaults('variant', 'file')
            ->name('media.file');

        Route::get('/workorders', [$controller, 'workorders'])->name('workorders.index');
        Route::get('/workorders/{workorderId}', [$controller, 'workorder'])->name('workorders.show');
        Route::patch('/workorders/{workorderId}/storage', [$controller, 'updateStorage'])->name('workorders.storage.update');
        Route::patch('/workorders/{workorderId}/arrival-box', [$controller, 'updateArrivalBox'])->name('workorders.arrival-box.update');
        Route::get('/workorders/{workorderId}/media', [$controller, 'workorderMedia'])->name('workorders.media.index');
        Route::post('/workorders/{workorderId}/media', [$controller, 'storeWorkorderMedia'])->name('workorders.media.store');
        Route::delete('/workorders/{workorderId}/media/{media}', [$controller, 'deleteWorkorderMedia'])->name('workorders.media.destroy');

        Route::get('/draft/options', [$controller, 'draftOptions'])->name('draft.options');
        Route::post('/drafts', [$controller, 'storeDraft'])->name('drafts.store');
        Route::post('/draft-units', [$controller, 'storeDraftUnit'])->name('draft-units.store');

        Route::get('/workorders/{workorderId}/tasks', [$controller, 'tasks'])->name('workorders.tasks.index');
        Route::put('/workorders/{workorderId}/tasks/{task}/dates', [$controller, 'updateTaskDates'])->name('workorders.tasks.dates');

        Route::get('/workorders/{workorderId}/components', [$controller, 'components'])->name('workorders.components.index');
        Route::post('/workorders/{workorderId}/components', [$controller, 'storeComponent'])->name('workorders.components.store');
        Route::patch('/components/{component}', [$controller, 'updateComponent'])->name('components.update');
        Route::post('/components/{component}/photo', [$controller, 'storeComponentPhoto'])->name('components.photo.store');
        Route::post('/workorders/{workorderId}/component-attachments', [$controller, 'storeComponentAttachment'])->name('workorders.component-attachments.store');
        Route::patch('/component-attachments/{tdr}', [$controller, 'updateComponentAttachment'])->name('component-attachments.update');
        Route::delete('/component-attachments/{tdr}', [$controller, 'deleteComponentAttachment'])->name('component-attachments.destroy');

        Route::get('/workorders/{workorderId}/processes', [$controller, 'processes'])->name('workorders.processes.index');
        Route::patch('/tdr-processes/{tdrProcess}/dates', [$controller, 'updateTdrProcessDates'])->name('tdr-processes.dates.update');

        Route::get('/materials', [$controller, 'materials'])->name('materials.index');
        Route::patch('/materials/{material}', [$controller, 'updateMaterial'])->name('materials.update');

        Route::get('/paint', [$controller, 'paint'])->name('paint.index');
        Route::post('/paint/lost', [$controller, 'storePaintLost'])->name('paint.lost.store');
        Route::delete('/paint/lost/{paint}', [$controller, 'deletePaintLost'])->name('paint.lost.destroy');
        Route::post('/paint/messages', [$controller, 'sendPaintOwnerMessage'])->name('paint.messages.store');

        Route::get('/machining', [$controller, 'machining'])->name('machining.index');
        Route::get('/machining/workorders/{workorderId}', [$controller, 'machiningWorkorder'])->name('machining.workorders.show');
        Route::patch('/machining/steps/{machiningWorkStep}', [$controller, 'updateMachiningStep'])->name('machining.steps.update');
        Route::post('/machining/workorders/{workorderId}/photos', [$controller, 'storeMachiningWorkorderPhoto'])->name('machining.workorders.photos.store');
        Route::get('/machining/workorders/{workorderId}/photos', [$controller, 'machiningWorkorderPhotos'])->name('machining.workorders.photos.index');
        Route::post('/machining/workorders/{workorderId}/doc-pdfs', [$controller, 'storeMachiningWorkorderDocPdf'])->name('machining.workorders.doc-pdfs.store');
        Route::get('/machining/workorders/{workorderId}/pdfs', [$controller, 'machiningWorkorderPdfs'])->name('machining.workorders.pdfs.index');
        Route::delete('/machining/workorders/{workorderId}/media/{media}', [$controller, 'deleteMachiningWorkorderMedia'])->name('machining.workorders.media.destroy');
    });
};

Route::prefix('mobile')->name('api.mobile.')
    ->group(fn () => $registerMobileClientRoutes(MobileApiController::class));

Route::prefix('android')->name('api.android.')
    ->group(fn () => $registerMobileClientRoutes(AndroidApiController::class));

Route::prefix('quantum')->name('api.quantum.')->group(function () {
    Route::get('/ro-sync/state', [QuantumRoSyncController::class, 'state'])->name('ro-sync.state');
    Route::post('/ro-sync', [QuantumRoSyncController::class, 'store'])->name('ro-sync.store');
});
