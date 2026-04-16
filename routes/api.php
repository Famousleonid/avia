<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArchiveController;

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

Route::middleware('auth:sanctum')->get('/users', function (Request $request) {
    return $request->user();
});

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
