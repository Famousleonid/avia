<?php

use App\Http\Controllers\Admin\CabinetController;
use App\Http\Controllers\Admin\BuilderController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\ConditionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\GeneralTaskController;
use App\Http\Controllers\Admin\LogCardController;
use App\Http\Controllers\Admin\ManualProcessController;
use App\Http\Controllers\Admin\PlaneController;
use App\Http\Controllers\Admin\ProcessController;
use App\Http\Controllers\Admin\ProcessNameController;
use App\Http\Controllers\Admin\RmReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ScopeController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TdrController;
use App\Http\Controllers\Admin\TdrProcessController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\ManualController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MainController;
use App\Http\Controllers\Admin\TrainingController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\WorkorderController;
use App\Http\Controllers\Front\FrontController;
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

// ----------------------- Mobile route -----------------------------------------------------------------
Route::prefix('mobile')->name('mobile.')->middleware(['auth','verified'])->group(function () {
    Route::get('/mobile', [MobileController::class, 'index'])->name('index');
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

// ----------------------- Media route -----------------------------------------------------------------
Route::group(['middleware' => 'auth'],function () {
    Route::post('/users/avatar/{id}', [MediaController::class, 'store_avatar'])->name('avatar.media.store');
    Route::get('/image/show/thumb/{mediaId}/users/{modelId}/name/{mediaName}', [MediaController::class, 'showThumb'])->name('image.show.thumb');
    Route::get('/image/show/big/{mediaId}/users/{modelId}/name/{mediaName}', [MediaController::class, 'showBig'])->name('image.show.big');
});

// ----------------------Admin route ------------------------------------------------------------------------
Route::group(['middleware' => ['auth', 'isAdmin'] ], function () {

});
// ---------------------- Cabinet route ------------------------------------------------------------------------
Route::group(['middleware' => ['auth'] ], function () {

    Route::get('/cabinet', [CabinetController::class, 'index'])->name('cabinet.index');
    Route::get('/logs', [CabinetController::class, 'activity'])->name('log.activity');
    Route::resource('/users', UserController::class);
    Route::resource('/manuals',ManualController::class);
    Route::resource('/planes',PlaneController::class);
    Route::resource('/builders', BuilderController::class);
    Route::resource('/scopes',  ScopeController::class);
    Route::resource('/materials',  MaterialController::class);
    Route::resource('/roles',  RoleController::class);
    Route::resource('/teams',  TeamController::class);
    Route::resource('/customers',  CustomerController::class);
    Route::resource('/tasks',  TaskController::class);
    Route::resource('/general-tasks',  GeneralTaskController::class);
    Route::resource('/workorders',  WorkorderController::class);
    Route::resource('/mains',  MainController::class);
    Route::resource('/units',  UnitController::class)->except('update');
    Route::resource('/tdrs',TdrController::class);
    Route::resource('/components', ComponentController::class);
    Route::resource('/processes', ProcessController::class);
    Route::resource('/tdr-processes',TdrProcessController::class);
    Route::resource('/process-names',ProcessNameController::class);
    Route::resource('/trainings', TrainingController::class);
    Route::resource('/manual_processes', ManualProcessController::class);
    Route::resource('/conditions',ConditionController::class);

    Route::get('/rm_reports/create/{id}',[RmReportController::class,'create'])->name('rm_reports.create');
//    Route::get('/rm_reports/create/{id}',[RmReportController::class,'wo_store'])->name('rm_reports.wo_store');

    Route::resource('/rm_reports', RmReportController::class)->except('create');
    Route::delete('/rm_reports/multiple', [RmReportController::class, 'destroyMultiple'])->name('rm_reports.destroy.multiple');
    Route::post('/rm_reports/save-to-workorder', [RmReportController::class, 'saveToWorkorder'])->name('rm_reports.save.to.workorder');
    Route::get('rm_reports/rmRecordForm/{id}',[RmReportController::class,'rmRecordForm'])->name('rm_reports.rmRecordForm');
    Route::get('/rm_reports/get-record/{id}', [RmReportController::class, 'getRecord'])->name('rm_reports.getRecord');
    Route::put('/rm_reports/update-record/{id}', [RmReportController::class, 'updateRecord'])->name('rm_reports.updateRecord');

    // Отдельный роут для create с параметром id
    Route::get('/log_card/create/{id}', [LogCardController::class, 'create'])->name('log_card.create');
    Route::resource('/log_card', LogCardController::class)->except('create');

    Route::get('/workorders/approve/{id}/', [WorkorderController::class, 'approve'])->name('workorders.approve');
    Route::post('workorders/{workorder}/inspection', [WorkorderController::class, 'updateInspect'])->name('workorders.inspection');
    Route::get('/progress', [MainController::class, 'progress'])->name('progress.index');
    Route::get('/workorders/{id}/photos', [WorkorderController::class, 'photos'])->name('workorders.photos');
    Route::get('/workorders/download/{id}/all', [WorkorderController::class, 'downloadAllGrouped'])->name('workorders.downloadAllGrouped');
    Route::delete('/workorders/photo/delete/{id}', [MediaController::class, 'delete_photo'])->name('admin.workorders.photo.delete');

    Route::get('/tdrs/processes/{workorder_id}',[TdrController::class, 'processes'])->name('tdrs.processes');
    Route::get('/tdrs/inspection/{workorder_id}',[TdrController::class, 'inspection'])->name('tdrs.inspection');
    Route::get('tdrs/tdrForm/{id}', [TdrController::class, 'tdrForm'])->name('tdrs.tdrForm');
    Route::get('tdrs/prlForm/{id}', [TdrController::class, 'prlForm'])->name('tdrs.prlForm');
    Route::get('tdrs/specProcessForm/{id}', [TdrController::class, 'specProcessForm'])->name('tdrs.specProcessForm');
    Route::get('tdrs/ndtForm/{id}', [TdrController::class, 'ndtForm'])->name('tdrs.ndtForm');

    Route::get('tdrs/logCardForm/{id}', [TdrController::class, 'logCardForm'])->name('tdrs.logCardForm');
    Route::get('log_card/logCardForm/{id}', [LogCardController::class, 'logCardForm'])->name('log_card.logCardForm');

    // Для component inspection
    Route::get('/tdrs/inspection/unit/{workorder_id}', [TdrController::class, 'inspectionUnit'])->name('tdrs.inspection.unit');

    // Для unit inspection
    Route::get('/tdrs/inspection/component/{workorder_id}', [TdrController::class, 'inspectionComponent'])->name('tdrs.inspection.component');

    Route::post('/components/store_from_inspection', [ComponentController::class, 'storeFromInspection'])->name('components.storeFromInspection');
    Route::get('/get-processes', [ProcessController::class, 'getProcesses'])->name('processes.getProcesses');
    Route::get('tdr-processes/processesForm/{id}', [TdrProcessController::class, 'processesForm'])->name('tdr-processes.processesForm');

    // Уникальный путь для createProcesses
    Route::get('/tdr/{tdrId}/create-processes', [TdrProcessController::class, 'createProcesses'])->name('tdr-processes.createProcesses');
    Route::get('/tdr/{tdrId}/processes', [TdrProcessController::class, 'processes'])->name('tdr-processes.processes');
    Route::get('/get-process/{processNameId}', [TdrProcessController::class, 'getProcess'])->name('tdr-processes.get-process');
    Route::get('tdrs/{workorder_id}/ndt-std', [TdrController::class, 'ndtStd'])->name('tdrs.ndtStd');
    Route::get('tdrs/{workorder_id}/cad-std', [TdrController::class, 'cadStd'])->name('tdrs.cadStd');

    Route::get('trainings/form112/{id}', [TrainingController::class, 'showForm112'])->name('trainings.form112');
    Route::get('trainings/form132/{id}', [TrainingController::class, 'showForm132'])->name('trainings.form132');
    Route::post('/trainings/createTraining', [TrainingController::class, 'createTraining'])->name('trainings.createTraining');
    Route::post('/trainings/delete-all', [TrainingController::class, 'deleteAll'])->name('trainings.deleteAll');

    // CSV файлы для мануалов
    Route::prefix('manuals/{manual}/csv')->name('manuals.csv.')->group(function () {
        Route::post('/', [ManualCsvController::class, 'store'])->name('store');
        Route::get('/{file}', [ManualCsvController::class, 'view'])->name('view');
        Route::delete('/{file}', [ManualCsvController::class, 'delete'])->name('delete');
    });

});




