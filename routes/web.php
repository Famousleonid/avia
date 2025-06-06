<?php

use App\Http\Controllers\Admin\TdrController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Cabinet\ManualController;
use App\Http\Controllers\Cabinet\MaterialController;
use App\Http\Controllers\Cabinet\UserController;
use App\Http\Controllers\Admin\MainController;
use App\Http\Controllers\Cabinet\TrainingController;
use App\Http\Controllers\Cabinet\UnitController;
use App\Http\Controllers\Cabinet\WorkorderController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\Cabinet\CabinetController;
use App\Http\Controllers\General\MediaController;
use App\Http\Controllers\Mobile\MobileController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ManualCsvController;

Auth::routes(['verify' => true]);

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    return "Cache cleared successfully!";
});

Route::get('/', [FrontController::class, 'index'])->name('home')->middleware('mobile.redirect');

Route::prefix('mobile')->name('mobile.')->middleware('auth')->group(function () {

    Route::get('/', [MobileController::class, 'index'])->name('index');
    Route::post('/workorders/photo/{id}', [MediaController::class, 'store_photo_workorders'])->name('workorders.media.store');
    Route::delete('/workorders/photo/delete/{id}', [MediaController::class, 'delete_photo'])->name('mobile.workorders.photo.delete');
    Route::get('/workorders/photos/{id}', [MediaController::class, 'get_photos'])->name('mobile.workorders.photos');

    Route::get('/materials', [MobileController::class, 'materials'])->name('materials');
    Route::post('/materials/{id}/update-description', [MobileController::class, 'updateMaterialDescription'])->name('mobile.materials.updateDescription');

    Route::get('/components', [MobileController::class, 'components'])->name('components');
    Route::post('/component/store', [MobileController::class, 'componentStore'])->name('component.store');

    Route::get('/profile', [MobileController::class, 'profile'])->name('profile');
    Route::put('/profile/{id}',[MobileController::class, 'update_profile'])->name('update.profile');
    Route::post('/change_password/user/{id}/', [MobileController::class, 'changePassword'])->name('profile.changePassword');

});

// ---------------------- User Auth route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth'], 'prefix' => 'cabinet', 'as' =>'cabinet.'], function () {

    Route::get('/', [CabinetController::class, 'index'])->name('index');
    Route::resource('/trainings', TrainingController::class);
    Route::resource('/mains', MainController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/workorders', WorkorderController::class);
    Route::resource('/units', UnitController::class);
    Route::resource('/materials', MaterialController::class);
    Route::resource('/manuals',ManualController::class);
    Route::resource('/tdrs',TdrController::class);

    Route::post('/trainings/createTraining', [TrainingController::class, 'createTraining'])->name('trainings.createTraining');
    Route::get('trainings/form112/{id}', [TrainingController::class, 'showForm112'])->name('trainings.form112');
    Route::get('trainings/form132/{id}', [TrainingController::class, 'showForm132'])->name('trainings.form132');

    Route::get('/profile', [CabinetController::class, 'profile'])->name('profile');
    Route::post('profile/change_password/user/{id}/', [UserController::class, 'changePassword'])->name('profile.changePassword');
    Route::get('/progress', [CabinetController::class, 'progress'])->name('progress.index');
    Route::get('/workorders/approve/{id}/', [WorkorderController::class, 'approve'])->name('workorders.approve');
});

// ----------------------- Media route -----------------------------------------------------------------

Route::group(['middleware' => 'auth'],function () {
    Route::post('/users/avatar/{id}', [MediaController::class, 'store_avatar'])->name('avatar.media.store');
    Route::get('/image/show/thumb/{mediaId}/users/{modelId}/name/{mediaName}', [MediaController::class, 'showThumb'])->name('image.show.thumb');
    Route::get('/image/show/big/{mediaId}/users/{modelId}/name/{mediaName}', [MediaController::class, 'showBig'])->name('image.show.big');
});

// ----------------------Admin route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth', 'isAdmin'], 'prefix' => 'admin', 'as' =>'admin.'], function () {

    Route::get('/', [\App\Http\Controllers\Admin\AdminController::class, 'index'])->name('index');
    Route::get('/logs', [\App\Http\Controllers\Admin\AdminController::class, 'activity'])->name('log.activity');
    Route::resource('/users', \App\Http\Controllers\Admin\UserController::class);
    Route::resource('/manuals',\App\Http\Controllers\Admin\ManualController::class);
    Route::resource('/planes',\App\Http\Controllers\Admin\PlaneController::class);
    Route::resource('/builders', \App\Http\Controllers\Admin\BuilderController::class);
    Route::resource('/scopes',  \App\Http\Controllers\Admin\ScopeController::class);
    Route::resource('/materials',  \App\Http\Controllers\Admin\MaterialController::class);
    Route::resource('/roles',  \App\Http\Controllers\Admin\RoleController::class);
    Route::resource('/teams',  \App\Http\Controllers\Admin\TeamController::class);
    Route::resource('/customers',  \App\Http\Controllers\Admin\CustomerController::class);
    Route::resource('/tasks',  \App\Http\Controllers\Admin\TaskController::class);
    Route::resource('/general-tasks',  \App\Http\Controllers\Admin\GeneralTaskController::class);
    Route::resource('/workorders',  \App\Http\Controllers\Admin\WorkorderController::class);
    Route::resource('/mains',  \App\Http\Controllers\Admin\MainController::class);
    Route::resource('/units',  \App\Http\Controllers\Admin\UnitController::class)->except('update');
    Route::resource('/tdrs',\App\Http\Controllers\Admin\TdrController::class);
    Route::resource('/components', \App\Http\Controllers\Admin\ComponentController::class);
    Route::resource('/processes', \App\Http\Controllers\Admin\ProcessController::class);
    Route::resource('/tdr-processes',\App\Http\Controllers\Admin\TdrProcessController::class);
    Route::resource('/process-names',\App\Http\Controllers\Admin\ProcessNameController::class);
    Route::resource('/trainings', \App\Http\Controllers\Admin\TrainingController::class);
    Route::resource('/manual_processes', \App\Http\Controllers\Admin\ManualProcessController::class);
    Route::resource('/conditions',\App\Http\Controllers\Admin\ConditionController::class);

    Route::get('/workorders/approve/{id}/', [\App\Http\Controllers\Admin\WorkorderController::class, 'approve'])->name('workorders.approve');
    Route::post('workorders/{workorder}/inspection', [\App\Http\Controllers\Admin\WorkorderController::class, 'updateInspect'])->name('workorders.inspection');
    Route::get('/progress', [\App\Http\Controllers\Admin\MainController::class, 'progress'])->name('progress.index');
    Route::get('/workorders/{id}/photos', [\App\Http\Controllers\Admin\WorkorderController::class, 'photos'])->name('workorders.photos');
    Route::get('/workorders/download/{id}/all', [\App\Http\Controllers\Admin\WorkorderController::class, 'downloadAllGrouped'])->name('workorders.downloadAllGrouped');
    Route::delete('/workorders/photo/delete/{id}', [MediaController::class, 'delete_photo'])->name('admin.workorders.photo.delete');


    Route::get('/tdrs/processes/{workorder_id}',[\App\Http\Controllers\Admin\TdrController::class, 'processes'])->name('tdrs.processes');
    Route::get('/tdrs/inspection/{workorder_id}',[\App\Http\Controllers\Admin\TdrController::class, 'inspection'])->name('tdrs.inspection');
    Route::get('tdrs/tdrForm/{id}', [\App\Http\Controllers\Admin\TdrController::class, 'tdrForm'])->name('tdrs.tdrForm');
    Route::get('tdrs/prlForm/{id}', [\App\Http\Controllers\Admin\TdrController::class, 'prlForm'])->name('tdrs.prlForm');
    Route::get('tdrs/specProcessForm/{id}', [\App\Http\Controllers\Admin\TdrController::class, 'specProcessForm'])->name('tdrs.specProcessForm');
    Route::get('tdrs/ndtForm/{id}', [\App\Http\Controllers\Admin\TdrController::class, 'ndtForm'])->name('tdrs.ndtForm');

    // Для component inspection

    Route::get('/tdrs/inspection/unit/{workorder_id}', [TdrController::class, 'inspectionUnit'])->name('tdrs.inspection.unit');

    // Для unit inspection

    Route::get('/tdrs/inspection/component/{workorder_id}', [TdrController::class, 'inspectionComponent'])->name('tdrs.inspection.component');


    Route::post('/components/store_from_inspection', [\App\Http\Controllers\Admin\ComponentController::class, 'storeFromInspection'])->name('components.storeFromInspection');
    Route::get('/get-processes', [\App\Http\Controllers\Admin\ProcessController::class, 'getProcesses'])->name('processes.getProcesses');
    Route::get('tdr-processes/processesForm/{id}', [\App\Http\Controllers\Admin\TdrProcessController::class, 'processesForm'])->name('tdr-processes.processesForm');

    // Уникальный путь для createProcesses
    Route::get('/tdr/{tdrId}/create-processes', [\App\Http\Controllers\Admin\TdrProcessController::class, 'createProcesses'])->name('tdr-processes.createProcesses');
    Route::get('/tdr/{tdrId}/processes', [\App\Http\Controllers\Admin\TdrProcessController::class, 'processes'])->name('tdr-processes.processes');
    Route::get('/get-process/{processNameId}', [\App\Http\Controllers\Admin\TdrProcessController::class, 'getProcess'])->name('tdr-processes.get-process');
    Route::get('tdrs/{workorder_id}/ndt-std', [TdrController::class, 'ndtStd'])->name('tdrs.ndtStd');


    Route::get('trainings/form112/{id}', [\App\Http\Controllers\Admin\TrainingController::class, 'showForm112'])->name('trainings.form112');
    Route::get('trainings/form132/{id}', [\App\Http\Controllers\Admin\TrainingController::class, 'showForm132'])->name('trainings.form132');
    Route::post('/trainings/createTraining', [\App\Http\Controllers\Admin\TrainingController::class, 'createTraining'])->name('trainings.createTraining');
    Route::post('/trainings/delete-all', [\App\Http\Controllers\Admin\TrainingController::class, 'deleteAll'])->name('trainings.deleteAll');

    // CSV файлы для мануалов
    Route::prefix('manuals/{manual}/csv')->name('manuals.csv.')->group(function () {
        Route::post('/', [ManualCsvController::class, 'store'])->name('store');
        Route::get('/{file}', [ManualCsvController::class, 'view'])->name('view');
        Route::delete('/{file}', [ManualCsvController::class, 'delete'])->name('delete');
    });

});




