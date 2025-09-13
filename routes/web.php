<?php

use App\Http\Controllers\Admin\CabinetController;
use App\Http\Controllers\Admin\BuilderController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\ConditionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ExtraProcessController;
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
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\WorkorderController;
use App\Http\Controllers\Admin\WoBushingController;
use App\Http\Controllers\Admin\NdtCadCsvController;
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
Route::group(['middleware' => ['auth', 'isAdmin'], 'prefix' => 'admin'], function () {
    Route::resource('/users', UserController::class);
    Route::resource('/tdrs', TdrController::class);
    Route::resource('/workorders', WorkorderController::class);
    Route::resource('/manuals', ManualController::class);

    Route::resource('/units', UnitController::class)->except(['update']);
    Route::post('/units/{manualId}', [UnitController::class, 'update'])->name('units.update');

    Route::resource('/builders', BuilderController::class);
    Route::resource('/categories', CategoryController::class);
    Route::resource('/codes', CodeController::class);
    Route::resource('/necessaries', NecessaryController::class);
    Route::resource('/components', ComponentController::class);

    Route::post('/components/upload-csv', [ComponentController::class, 'uploadCsv'])->name('components.upload-csv');
    Route::get('/components/download-csv-template', [ComponentController::class, 'downloadCsvTemplate'])->name('components.download-csv-template');
    Route::get('/components/view-csv/{manual_id}/{file_id}', [ComponentController::class, 'viewCsv'])->name('components.view-csv');
    Route::resource('/log_card', LogCardController::class);

    Route::resource('/process-names',ProcessNameController::class);
    Route::resource('/processes', ProcessController::class);
    Route::get('/get-processes', [ProcessController::class, 'getProcesses'])->name('processes.getProcesses');
    Route::resource('/tdr-processes',TdrProcessController::class);
    Route::resource('/manual_processes', ManualProcessController::class);

    Route::resource('/wo_bushings', WoBushingController::class)->except(['create']);
    Route::get('/wo_bushings/create/{id}', [WoBushingController::class, 'create'])->name('wo_bushings.create');


    // Route for LogCard creation with workorder ID
    Route::get('/extra_processes/create/{id}', [ExtraProcessController::class, 'create'])->name('extra_process.create');
    Route::get('/extra_processes/create_processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'createProcesses'])->name('extra_processes.create_processes');
    Route::post('/extra_processes/store_processes', [ExtraProcessController::class, 'storeProcesses'])->name('extra_processes.store_processes');
    Route::get('/extra_processes/processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'processes'])->name('extra_processes.processes');
    Route::post('/extra_processes/update-order', [ExtraProcessController::class, 'updateOrder'])->name('extra_processes.update-order');
    Route::get('/extra_processes/show_all/{id}', [ExtraProcessController::class, 'showAll'])->name('extra_processes.show_all');
    Route::get('/extra_processes/{id}', [ExtraProcessController::class, 'show'])->name('extra_processes.show');
    Route::get('/extra_processes/{id}/form/{processNameId}', [ExtraProcessController::class, 'showForm'])->name('extra_processes.show_form');
    Route::get('/extra_processes/{id}/group-forms/{processNameId}', [ExtraProcessController::class, 'showGroupForms'])->name('extra_processes.show_group_forms');
    Route::resource('/extra_processes', ExtraProcessController::class)->except(['create']);

    Route::get('log_card/create/{id}', [LogCardController::class, 'create'])->name('log_card.create');
    Route::get('log_card/edit/{id}', [LogCardController::class, 'edit'])->name('log_card.edit');
    Route::get('log_card/show/{id}', [LogCardController::class, 'show'])->name('log_card.show');

    //tdrs workorder route
    Route::get('tdrs/create/{id}', [TdrController::class, 'create'])->name('tdrs.create');
    Route::get('tdrs/edit/{id}', [TdrController::class, 'edit'])->name('tdrs.edit');
    Route::get('tdrs/show/{id}', [TdrController::class, 'show'])->name('tdrs.show');
    Route::get('tdrs/processes/{workorder_id}',[TdrController::class, 'processes'])->name('tdrs.processes');

    Route::get('processes/create/{manual_id}', [ProcessController::class, 'create'])->name('processes.create');
    Route::get('processes/edit/{id}', [ProcessController::class, 'edit'])->name('processes.edit');

    // Vendors routes
    Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');

    //workorder route
    Route::get('workorders/create/{id}', [WorkorderController::class, 'create'])->name('workorders.create');

    Route::get('tdrs/logCardForm/{id}', [TdrController::class, 'logCardForm'])->name('tdrs.logCardForm');
    Route::get('log_card/logCardForm/{id}', [LogCardController::class, 'logCardForm'])->name('log_card.logCardForm');

    Route::get('tdrs/woProcessForm/{id}', [TdrController::class, 'wo_Process_Form'])->name('tdrs.woProcessForm');
    // Для component inspection
    Route::get('/tdrs/inspection/unit/{workorder_id}', [TdrController::class, 'inspectionUnit'])->name('tdrs.inspection.unit');

    // Для unit inspection
    Route::get('/tdrs/inspection/component/{workorder_id}', [TdrController::class, 'inspectionComponent'])->name('tdrs.inspection.component');

    Route::post('/components/store_from_inspection', [ComponentController::class, 'storeFromInspection'])->name('components.storeFromInspection');
    Route::post('/components/store_from_extra', [ComponentController::class, 'storeFromExtra'])->name('components.storeFromExtra');
    Route::get('tdr-processes/processesForm/{id}', [TdrProcessController::class, 'processesForm'])->name('tdr-processes.processesForm');

    // Уникальный путь для createProcesses
    Route::get('/tdr/{tdrId}/create-processes', [TdrProcessController::class, 'createProcesses'])->name('tdr-processes.createProcesses');
    Route::get('/tdr/{tdrId}/processes', [TdrProcessController::class, 'processes'])->name('tdr-processes.processes');
    Route::get('/get-process/{processNameId}', [TdrProcessController::class, 'getProcess'])->name('tdr-processes.get-process');
    Route::post('/tdr-processes/update-order', [TdrProcessController::class, 'updateOrder'])->name('tdr-processes.update-order');

    // WoBushings processesForm route
    Route::get('wo_bushings/processesForm/{id}/{processNameId}', [WoBushingController::class, 'processesForm'])->name('wo_bushings.processesForm');

    // WoBushings specProcessForm route
    Route::get('wo_bushings/specProcessForm/{id}', [WoBushingController::class, 'specProcessForm'])->name('wo_bushings.specProcessForm');
    Route::get('tdrs/{workorder_id}/ndt-std', [TdrController::class, 'ndtStd'])->name('tdrs.ndtStd');
    Route::get('tdrs/{workorder_id}/cad-std', [TdrController::class, 'cadStd'])->name('tdrs.cadStd');
    Route::get('tdrs/{workorder_id}/machining-form', [TdrController::class, 'machiningForm'])->name('tdrs.machiningForm');
    Route::get('tdrs/{workorder_id}/ndt-form', [TdrController::class, 'ndtForm'])->name('tdrs.ndtForm');
    Route::get('tdrs/{workorder_id}/passivation-form', [TdrController::class, 'passivationForm'])->name('tdrs.passivationForm');
    Route::get('tdrs/{workorder_id}/cad-form', [TdrController::class, 'cadForm'])->name('tdrs.cadForm');
    Route::get('tdrs/{workorder_id}/xylan-form', [TdrController::class, 'xylanForm'])->name('tdrs.xylanForm');
    Route::get('tdrs/{workorder_id}/spec-process', [TdrController::class, 'specProcess'])->name('tdrs.specProcess');
    Route::post('tdrs/store-processes', [TdrController::class, 'storeProcesses'])->name('tdrs.storeProcesses');

    // NDT/CAD CSV Management Routes
    Route::get('/{workorder}/ndt-cad-csv', [NdtCadCsvController::class, 'index'])->name('ndt-cad-csv.index');
    Route::post('/{workorder}/ndt-cad-csv/ndt-components', [NdtCadCsvController::class, 'updateNdtComponents'])->name('.ndt-cad-csv.update-ndt');
    Route::post('/{workorder}/ndt-cad-csv/cad-components', [NdtCadCsvController::class, 'updateCadComponents'])->name('ndt-cad-csv.update-cad');
    Route::post('/{workorder}/ndt-cad-csv/add-ndt', [NdtCadCsvController::class, 'addNdtComponent'])->name('ndt-cad-csv.add-ndt');
    Route::post('/{workorder}/ndt-cad-csv/add-cad', [NdtCadCsvController::class, 'addCadComponent'])->name('ndt-cad-csv.add-cad');
    Route::post('/{workorder}/ndt-cad-csv/remove-ndt', [NdtCadCsvController::class, 'removeNdtComponent'])->name('ndt-cad-csv.remove-ndt');
    Route::post('/{workorder}/ndt-cad-csv/remove-cad', [NdtCadCsvController::class, 'removeCadComponent'])->name('ndt-cad-csv.remove-cad');
    Route::post('/{workorder}/ndt-cad-csv/edit-ndt', [NdtCadCsvController::class, 'editNdtComponent'])->name('ndt-cad-csv.edit-ndt');
    Route::post('/{workorder}/ndt-cad-csv/edit-cad', [NdtCadCsvController::class, 'editCadComponent'])->name('ndt-cad-csv.edit-cad');
    Route::post('/{workorder}/ndt-cad-csv/import', [NdtCadCsvController::class, 'importFromCsv'])->name('ndt-cad-csv.import');
    Route::post('/{workorder}/ndt-cad-csv/reload-from-manual', [NdtCadCsvController::class, 'reloadFromManual'])->name('ndt-cad-csv.reload-from-manual');
    Route::post('/{workorder}/ndt-cad-csv/force-load-from-manual', [NdtCadCsvController::class, 'forceLoadFromManual'])->name('ndt-cad-csv.force-load-from-manual');
    Route::get('/{workorder}/ndt-cad-csv/components', [NdtCadCsvController::class, 'getComponents'])->name('ndt-cad-csv.components');
    Route::get('/{workorder}/ndt-cad-csv/cad-processes', [NdtCadCsvController::class, 'getCadProcesses'])->name('ndt-cad-csv.cad-processes');
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
    Route::get('/get-processes', [ProcessController::class, 'getProcesses'])->name('processes.getProcesses');
    Route::resource('/tdr-processes',TdrProcessController::class);
    Route::resource('/process-names',ProcessNameController::class);
    Route::resource('/trainings', TrainingController::class);
    Route::resource('/manual_processes', ManualProcessController::class);
    Route::resource('/conditions',ConditionController::class);

    Route::get('/extra_processes/create/{id}', [ExtraProcessController::class, 'create'])->name('extra_process.create');
Route::get('/extra_processes/create_processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'createProcesses'])->name('extra_processes.create_processes');
Route::post('/extra_processes/store_processes', [ExtraProcessController::class, 'storeProcesses'])->name('extra_processes.store_processes');
Route::get('/extra_processes/processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'processes'])->name('extra_processes.processes');
Route::get('/extra_processes/show_all/{id}', [ExtraProcessController::class, 'showAll'])->name('extra_processes.show_all');
Route::get('/extra_processes/{id}', [ExtraProcessController::class, 'show'])->name('extra_processes.show');
Route::get('/extra_processes/{id}/form/{processNameId}', [ExtraProcessController::class, 'showForm'])->name('extra_processes.show_form');
Route::get('/extra_processes/{id}/group-forms/{processNameId}', [ExtraProcessController::class, 'showGroupForms'])->name('extra_processes.show_group_forms');
Route::resource('/extra_processes', ExtraProcessController::class)->except(['create']);

    Route::get('/rm_reports/create/{id}',[RmReportController::class,'create'])->name('rm_reports.create');
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
    Route::get('tdrs/specProcessFormEmp/{id}', [TdrController::class, 'specProcessFormEmp'])->name('tdrs.specProcessFormEmp');
    Route::get('tdrs/ndtForm/{id}', [TdrController::class, 'ndtForm'])->name('tdrs.ndtForm');

    Route::get('tdrs/logCardForm/{id}', [TdrController::class, 'logCardForm'])->name('tdrs.logCardForm');
    Route::get('log_card/logCardForm/{id}', [LogCardController::class, 'logCardForm'])->name('log_card.logCardForm');

    Route::get('tdrs/woProcessForm/{id}', [TdrController::class, 'wo_Process_Form'])->name('tdrs.woProcessForm');
    // Для component inspection
    Route::get('/tdrs/inspection/unit/{workorder_id}', [TdrController::class, 'inspectionUnit'])->name('tdrs.inspection.unit');

    // Для unit inspection
    Route::get('/tdrs/inspection/component/{workorder_id}', [TdrController::class, 'inspectionComponent'])->name('tdrs.inspection.component');

    Route::post('/components/store_from_inspection', [ComponentController::class, 'storeFromInspection'])->name('components.storeFromInspection');
    Route::post('/components/store_from_extra', [ComponentController::class, 'storeFromExtra'])->name('components.storeFromExtra');
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




