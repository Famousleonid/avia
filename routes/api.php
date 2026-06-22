<?php

use Illuminate\Support\Facades\Route;
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

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::get('/public/app-config', [MobileApiController::class, 'publicAppConfig'])->name('public.app-config');
    Route::post('/auth/login', [MobileApiController::class, 'login'])->name('auth.login');

    Route::middleware('mobile.api')->group(function () {
        Route::get('/me', [MobileApiController::class, 'me'])->name('me');
        Route::get('/bootstrap', [MobileApiController::class, 'bootstrap'])->name('bootstrap');
        Route::post('/auth/logout', [MobileApiController::class, 'logout'])->name('auth.logout');
        Route::get('/profile', [MobileApiController::class, 'profile'])->name('profile.show');
        Route::put('/profile', [MobileApiController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/password', [MobileApiController::class, 'updatePassword'])->name('profile.password.update');

        Route::get('/media/{media}/thumb', [MobileApiController::class, 'mediaFile'])
            ->defaults('variant', 'thumb')
            ->name('media.thumb');
        Route::get('/media/{media}/file', [MobileApiController::class, 'mediaFile'])
            ->defaults('variant', 'file')
            ->name('media.file');

        Route::get('/workorders', [MobileApiController::class, 'workorders'])->name('workorders.index');
        Route::get('/workorders/{workorderId}', [MobileApiController::class, 'workorder'])->name('workorders.show');
        Route::patch('/workorders/{workorderId}/storage', [MobileApiController::class, 'updateStorage'])->name('workorders.storage.update');
        Route::patch('/workorders/{workorderId}/arrival-box', [MobileApiController::class, 'updateArrivalBox'])->name('workorders.arrival-box.update');
        Route::get('/workorders/{workorderId}/media', [MobileApiController::class, 'workorderMedia'])->name('workorders.media.index');
        Route::post('/workorders/{workorderId}/media', [MobileApiController::class, 'storeWorkorderMedia'])->name('workorders.media.store');
        Route::delete('/workorders/{workorderId}/media/{media}', [MobileApiController::class, 'deleteWorkorderMedia'])->name('workorders.media.destroy');

        Route::get('/draft/options', [MobileApiController::class, 'draftOptions'])->name('draft.options');
        Route::post('/drafts', [MobileApiController::class, 'storeDraft'])->name('drafts.store');
        Route::post('/draft-units', [MobileApiController::class, 'storeDraftUnit'])->name('draft-units.store');

        Route::get('/workorders/{workorderId}/tasks', [MobileApiController::class, 'tasks'])->name('workorders.tasks.index');
        Route::put('/workorders/{workorderId}/tasks/{task}/dates', [MobileApiController::class, 'updateTaskDates'])->name('workorders.tasks.dates');

        Route::get('/workorders/{workorderId}/components', [MobileApiController::class, 'components'])->name('workorders.components.index');
        Route::post('/workorders/{workorderId}/components', [MobileApiController::class, 'storeComponent'])->name('workorders.components.store');
        Route::patch('/components/{component}', [MobileApiController::class, 'updateComponent'])->name('components.update');
        Route::post('/components/{component}/photo', [MobileApiController::class, 'storeComponentPhoto'])->name('components.photo.store');
        Route::post('/workorders/{workorderId}/component-attachments', [MobileApiController::class, 'storeComponentAttachment'])->name('workorders.component-attachments.store');
        Route::patch('/component-attachments/{tdr}', [MobileApiController::class, 'updateComponentAttachment'])->name('component-attachments.update');
        Route::delete('/component-attachments/{tdr}', [MobileApiController::class, 'deleteComponentAttachment'])->name('component-attachments.destroy');

        Route::get('/workorders/{workorderId}/processes', [MobileApiController::class, 'processes'])->name('workorders.processes.index');
        Route::patch('/tdr-processes/{tdrProcess}/dates', [MobileApiController::class, 'updateTdrProcessDates'])->name('tdr-processes.dates.update');

        Route::get('/materials', [MobileApiController::class, 'materials'])->name('materials.index');
        Route::patch('/materials/{material}', [MobileApiController::class, 'updateMaterial'])->name('materials.update');

        Route::get('/paint', [MobileApiController::class, 'paint'])->name('paint.index');
        Route::post('/paint/lost', [MobileApiController::class, 'storePaintLost'])->name('paint.lost.store');
        Route::delete('/paint/lost/{paint}', [MobileApiController::class, 'deletePaintLost'])->name('paint.lost.destroy');
        Route::post('/paint/messages', [MobileApiController::class, 'sendPaintOwnerMessage'])->name('paint.messages.store');

        Route::get('/machining', [MobileApiController::class, 'machining'])->name('machining.index');
        Route::get('/machining/workorders/{workorderId}', [MobileApiController::class, 'machiningWorkorder'])->name('machining.workorders.show');
        Route::patch('/machining/steps/{machiningWorkStep}', [MobileApiController::class, 'updateMachiningStep'])->name('machining.steps.update');
        Route::post('/machining/workorders/{workorderId}/photos', [MobileApiController::class, 'storeMachiningWorkorderPhoto'])->name('machining.workorders.photos.store');
        Route::get('/machining/workorders/{workorderId}/photos', [MobileApiController::class, 'machiningWorkorderPhotos'])->name('machining.workorders.photos.index');
        Route::post('/machining/workorders/{workorderId}/doc-pdfs', [MobileApiController::class, 'storeMachiningWorkorderDocPdf'])->name('machining.workorders.doc-pdfs.store');
        Route::get('/machining/workorders/{workorderId}/pdfs', [MobileApiController::class, 'machiningWorkorderPdfs'])->name('machining.workorders.pdfs.index');
        Route::delete('/machining/workorders/{workorderId}/media/{media}', [MobileApiController::class, 'deleteMachiningWorkorderMedia'])->name('machining.workorders.media.destroy');
    });
});

Route::prefix('quantum')->name('api.quantum.')->group(function () {
    Route::get('/ro-sync/state', [QuantumRoSyncController::class, 'state'])->name('ro-sync.state');
    Route::post('/ro-sync', [QuantumRoSyncController::class, 'store'])->name('ro-sync.store');
});
