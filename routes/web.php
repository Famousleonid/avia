<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CabinetController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\ConditionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DirectoryController;
use App\Http\Controllers\Admin\ExtraProcessController;
use App\Http\Controllers\Admin\GeneralTaskController;
use App\Http\Controllers\Admin\LogCardController;
use App\Http\Controllers\Admin\ManualProcessController;
use App\Http\Controllers\Admin\ProcessController;
use App\Http\Controllers\Admin\RmReportController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TdrController;
use App\Http\Controllers\Admin\TdrProcessController;
use App\Http\Controllers\Admin\TransferController;
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
use App\Http\Controllers\General\NotificationController;
use App\Http\Controllers\Mobile\MobileComponentController;
use App\Http\Controllers\Mobile\MobileController;
use App\Http\Controllers\Mobile\MobileProcessController;
use App\Http\Controllers\Mobile\MobileTaskController;
use App\Support\Device;
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

Route::get('/front', [FrontController::class, 'index'])->name('front.index');
Route::get('/', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return view('front.index');
    }
    return Device::isMobile($request) ? redirect('/mobile') : redirect('/cabinet');
})->name('home');

// ----------------------- Mobile route -----------------------------------------------------------------
Route::prefix('mobile')->name('mobile.')->middleware(['auth','verified'])->group(function () {

    // --- general pages  ---
    Route::get('/', [MobileController::class, 'index'])->name('index');
    Route::get('/show/{workorder}', [MobileController::class, 'show'])->name('show');
    Route::get('/draft', [MobileController::class, 'createDraft'])->name('draft');
    Route::post('/workorders/draft', [MobileController::class, 'storeDraft'])->name('draft.store');

    // --- tasks ---
    Route::get('/tasks/{workorder}', [MobileTaskController::class, 'tasks'])->name('tasks');
    Route::post('/tasks/by-workorder', [MobileTaskController::class, 'getTasksByWorkorder'])->name('tasks.byWorkorder');
    Route::post('/tasks/store', [MobileTaskController::class, 'storeMain'])->name('tasks.store');
    Route::post('/tasks/update-dates', [MobileTaskController::class, 'updateMainDates'])->name('tasks.updateDates');

    // --- components ---
    Route::get('/components/{workorder}', [MobileComponentController::class, 'components'])->name('components');
    Route::post('/component/store', [MobileComponentController::class, 'componentStore'])->name('component.store');
    Route::patch('/components/{component}', [MobileComponentController::class, 'update'])->name('components.update');
    Route::post('/components/quick-store', [MobileComponentController::class, 'quickStore'])->name('components.quickStore');
    Route::post('/workorders/components/attach', [MobileComponentController::class, 'storeAttach'])->name('workorders.components.attach');
    Route::patch('/workorders/components/attach/{tdr}', [MobileComponentController::class,'updateAttach'])->name('workorders.components.attach.update');
    Route::delete('/workorders/components/attach/{tdr}', [MobileComponentController::class, 'destroyAttach'])->name('workorders.components.attach.destroy');

    Route::post('/components/{component}/photo', [MobileComponentController::class, 'updatePhoto'])->name('components.updatePhoto');

    // --- process ---
    Route::get('/process/{workorder}', [MobileProcessController::class, 'process'])->name('process');

    // --- materials (оставляем в MobileController, либо позже вынесем в MobileMaterialController) ---
    Route::get('/materials', [MobileController::class, 'materials'])->name('materials');
    Route::post('/materials/{id}/update-description', [MobileController::class, 'updateMaterialDescription'])
        ->name('materials.updateDescription'); // фикс имени (без mobile.mobile...)

    // --- media ---
    Route::post('/workorders/photo/{workorder}', [MediaController::class, 'store_photo_workorders'])->name('workorders.media.store');
    Route::delete('/workorders/photo/delete/{id}', [MediaController::class, 'delete_photo'])->name('workorders.photo.delete');
    Route::get('/workorders/photos/{id}', [MediaController::class, 'get_photos'])->name('workorders.photos');


    // --- profile (оставляем в MobileController) ---
    Route::get('/profile', [MobileController::class, 'profile'])->name('profile');
    Route::put('/profile/{id}', [MobileController::class, 'update_profile'])->name('update.profile');
    Route::post('/change_password/user/{id}', [MobileController::class, 'changePassword'])->name('profile.changePassword');
});

// ----------------------Auth route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth']], function () {

    Route::get('/cabinet', [CabinetController::class, 'index'])->name('cabinet.index');
    Route::get('/image/show/thumb/{mediaId}/{modelId}/{mediaName}', [MediaController::class, 'showThumb'])->name('image.show.thumb');
    Route::get('/image/show/big/{mediaId}/{modelId}/{mediaName}',[MediaController::class, 'showBig'])->name('image.show.big');
    Route::patch('/workorders/media/{media}/move', [MediaController::class, 'move_workorder_media'])->name('workorders.media.move');
    Route::post('/workorders/{workorder}/media/upload', [MediaController::class, 'upload_workorder_media'])->name('workorders.media.upload');
    // Route::patch('/workorders/{workorder}/media/reorder', [MediaController::class, 'reorder_workorder_media'])->name('workorders.media.reorder');
    Route::get('/workorders/download/{id}/group/{group}', [WorkorderController::class, 'downloadGroup'])->name('workorders.downloadGroup');

    Route::post('/users/avatar/{id}', [MediaController::class, 'store_avatar'])->name('avatar.media.store');
    Route::get('workorders-logs', [\App\Http\Controllers\Admin\WorkorderController::class, 'logs'])->name('workorders.logs');
    Route::get('/workorders/{workorder}/logs-json', [WorkorderController::class, 'logsForWorkorder'])->name('workorders.logs-json');
    Route::get('/workorders/check-number', [WorkorderController::class, 'checkNumber'])->name('workorders.checkNumber');

    Route::resource('/users', UserController::class);
    Route::resource('/mains',  MainController::class)->except(['show']);
    Route::get('/mains/{workorder}', [MainController::class, 'show'])->name('mains.show');

  //  Route::patch('/mains/general-task/{workorder}/{generalTask}', [MainController::class, 'updateGeneralTaskDates'])->name('mains.updateGeneralTaskDates');
    Route::get('/main-rows/{main}/activity', [MainController::class, 'activity'])->name('mains.activity');
    Route::resource('/workorders', WorkorderController::class);

    Route::post('/workorders/{workorder}/approve', [WorkorderController::class, 'approveAjax'])->name('workorders.approve.ajax');
        Route::post('workorders/{workorder}/inspection', [WorkorderController::class, 'updateInspect'])->name('workorders.inspection');
        Route::get('/workorders/{id}/photos', [WorkorderController::class, 'photos'])->name('workorders.photos');
        Route::get('/workorders/download/{id}/all', [WorkorderController::class, 'downloadAllGrouped'])->name('workorders.downloadAllGrouped');
        Route::delete('/workorders/photo/delete/{id}', [MediaController::class, 'delete_photo'])->name('workorders.photo.delete');
        Route::post('/admin/workorders/recalc-stages', [\App\Http\Controllers\Admin\WorkorderController::class, 'recalcStages'])->name('workorders.recalcStages');
        Route::patch('/workorders/{workorder}/notes', [WorkorderController::class, 'updateNotes'])->name('workorders.notes.update');
        Route::get('/workorders/{workorder}/notes/logs', [WorkorderController::class, 'notesLogs'])->name('workorders.notes.logs');

        // PDF Library routes
        Route::get('/workorders/{id}/pdfs', [WorkorderController::class, 'pdfs'])->name('workorders.pdfs');
        Route::post('/workorders/pdf/{id}', [MediaController::class, 'store_pdf_workorders'])->name('workorders.pdf.store');
        Route::get('/workorders/{workorderId}/pdf/{mediaId}/show', [MediaController::class, 'showPdf'])->name('workorders.pdf.show');
        Route::get('/workorders/{workorderId}/pdf/{mediaId}/download', [MediaController::class, 'downloadPdf'])->name('workorders.pdf.download');
        Route::delete('/workorders/pdf/delete/{id}', [MediaController::class, 'delete_pdf'])->name('workorders.pdf.delete');

        Route::resource('/tdrs', TdrController::class)->except('create','edit', 'show');

        Route::get('tdrs/create/{id}', [TdrController::class, 'create'])->name('tdrs.create');
        Route::get('tdrs/edit/{id}', [TdrController::class, 'edit'])->name('tdrs.edit');
        Route::get('tdrs/show/{id}', [TdrController::class, 'show'])->name('tdrs.show');
        Route::get('tdrs/processes/{workorder_id}',[TdrController::class, 'processes'])->name('tdrs.processes');
        Route::get('tdrs/{id}/group-forms/{processNameId}', [TdrController::class, 'showGroupForms'])->name('tdrs.show_group_forms');
        Route::get('/tdrs/inspection/{workorder_id}',[TdrController::class, 'inspection'])->name('tdrs.inspection');
        Route::get('tdrs/logCardForm/{id}', [TdrController::class, 'logCardForm'])->name('tdrs.logCardForm');
        Route::get('log_card/logCardForm/{id}', [LogCardController::class, 'logCardForm'])->name('log_card.logCardForm');
        Route::get('tdrs/woProcessForm/{id}', [TdrController::class, 'wo_Process_Form'])->name('tdrs.woProcessForm');
        Route::get('/tdrs/inspection/unit/{workorder_id}', [TdrController::class, 'inspectionUnit'])->name('tdrs.inspection.unit');
        Route::get('/tdrs/inspection/component/{workorder_id}', [TdrController::class, 'inspectionComponent'])->name('tdrs.inspection.component');
        Route::get('tdrs/{workorder_id}/ndt-std', [TdrController::class, 'ndtStd'])->name('tdrs.ndtStd');
        Route::get('tdrs/{workorder_id}/cad-std', [TdrController::class, 'cadStd'])->name('tdrs.cadStd');
        Route::get('tdrs/{workorder_id}/paint-std', [TdrController::class, 'paintStd'])->name('tdrs.paintStd');
        Route::get('tdrs/{workorder_id}/stress-std', [TdrController::class, 'stressStd'])->name('tdrs.stressStd');
        Route::get('tdrs/{workorder_id}/machining-form', [TdrController::class, 'machiningForm'])->name('tdrs.machiningForm');
        Route::get('tdrs/{workorder_id}/ndt-form', [TdrController::class, 'ndtForm'])->name('tdrs.ndtForm');
        Route::get('tdrs/{workorder_id}/passivation-form', [TdrController::class, 'passivationForm'])->name('tdrs.passivationForm');
        Route::get('tdrs/{workorder_id}/cad-form', [TdrController::class, 'cadForm'])->name('tdrs.cadForm');
        Route::get('tdrs/{workorder_id}/xylan-form', [TdrController::class, 'xylanForm'])->name('tdrs.xylanForm');
        Route::get('tdrs/{workorder_id}/spec-process', [TdrController::class, 'specProcess'])->name('tdrs.specProcess');
        Route::post('tdrs/store-processes', [TdrController::class, 'storeProcesses'])->name('tdrs.storeProcesses');
        Route::post('tdrs/store-unit-inspections', [TdrController::class, 'storeUnitInspections'])->name('tdrs.store.unit-inspections');
        Route::get('tdrs/get-components-by-manual', [TdrController::class, 'getComponentsByManual'])->name('tdrs.get-components-by-manual');
        Route::get('tdrs/tdrForm/{id}', [TdrController::class, 'tdrForm'])->name('tdrs.tdrForm');
        Route::get('tdrs/prlForm/{id}', [TdrController::class, 'prlForm'])->name('tdrs.prlForm');
        Route::get('tdrs/specProcessForm/{id}', [TdrController::class, 'specProcessForm'])->name('tdrs.specProcessForm');
    Route::get('tdrs/specProcessFormEmp/{id}', [TdrController::class, 'specProcessFormEmp'])->name('tdrs.specProcessFormEmp');
        Route::post('tdrs/update-part-field/{id}', [TdrController::class, 'updatePartField'])->name('tdrs.updatePartField');

        Route::get('transfers/{workorder}', [TransferController::class, 'show'])->name('transfers.show');
        Route::get('transfers/transferForm/{id}', [TransferController::class, 'transferForm'])->name('transfers.transferForm');
        Route::get('transfers/transfersForm/{source_wo}', [TransferController::class, 'transfersForm'])->name('transfers.transfersForm');
        Route::post('transfers/create/{id}', [TransferController::class, 'create'])->name('transfers.create');
        Route::patch('transfers/{id}/sn', [TransferController::class, 'updateSn'])->name('transfers.updateSn');
        Route::delete('transfers/delete-by-tdr/{id}', [TransferController::class, 'deleteByTdr'])->name('transfers.deleteByTdr');

    Route::resource('/manuals', ManualController::class);
    Route::resource('/conditions',  ConditionController::class);
    Route::resource('/materials',  MaterialController::class);
    Route::patch('/materials/{material}/inline', [MaterialController::class, 'inlineUpdate'])->name('materials.inline');


        Route::get('trainings/show-all', [TrainingController::class, 'showAll'])->name('trainings.showAll');
        Route::get('trainings/form112/{id}', [TrainingController::class, 'showForm112'])->name('trainings.form112');
        Route::get('trainings/form132/{id}', [TrainingController::class, 'showForm132'])->name('trainings.form132');
        Route::post('/trainings/createTraining', [TrainingController::class, 'createTraining'])->name('trainings.createTraining');
        Route::post('/trainings/updateToToday', [TrainingController::class, 'updateToToday'])->name('trainings.updateToToday');
        Route::post('/trainings/delete-all', [TrainingController::class, 'deleteAll'])->name('trainings.deleteAll');
        Route::post('/trainings/exists', [TrainingController::class, 'exists'])->name('trainings.exists');
        Route::resource('/trainings', TrainingController::class);


    // Components CSV routes - must be before resource route
    Route::post('/components/upload-csv', [ComponentController::class, 'uploadCsv'])->name('components.upload-csv');
    Route::get('/components/download-csv-template', [ComponentController::class, 'downloadCsvTemplate'])->name('components.download-csv-template');
    Route::get('/components/csv-components', [ComponentController::class, 'csvComponents'])->name('components.csv-components');
    Route::get('/components/view-csv/{manual_id}/{file_id}', [ComponentController::class, 'viewCsv'])->name('components.view-csv');
    Route::get('/components/edit-csv/{manual_id}/{file_id}', [ComponentController::class, 'editCsv'])->name('components.edit-csv');
    Route::post('/components/update-csv/{manual_id}/{file_id}', [ComponentController::class, 'updateCsv'])->name('components.update-csv');
    Route::delete('/components/delete-csv/{manual_id}/{file_id}', [ComponentController::class, 'deleteCsv'])->name('components.delete-csv');
    Route::get('/components/download-csv/{manual_id}/{file_id}', [ComponentController::class, 'downloadCsv'])->name('components.download-csv');

    // Components editing from inspection
    Route::get('/components/{component}/json', [ComponentController::class, 'showJson'])->name('components.showJson');
    Route::post('/components/{component}/update-from-inspection', [ComponentController::class, 'updateFromInspection'])->name('components.updateFromInspection');

    Route::patch('/components/{component}/single', [ComponentController::class, 'updateSingle'])->name('components.updateSingle');
    Route::resource('/components', ComponentController::class);
    Route::resource('/processes', ProcessController::class);
    Route::resource('/tdr-processes',TdrProcessController::class);
    Route::resource('/manual_processes', ManualProcessController::class);
    Route::resource('/log_card', LogCardController::class);
    Route::resource('/customers',  CustomerController::class);
    Route::resource('/tasks',  TaskController::class);
    Route::resource('/general-tasks',  GeneralTaskController::class);

    Route::resource('/units', UnitController::class)->except(['update']);
    Route::post('/units/{manualId}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{unit}/single', [UnitController::class, 'destroySingle'])->name('units.destroySingle');
    Route::patch('/units/{unit}/single', [UnitController::class, 'updateSingle'])->name('units.updateSingle');

    Route::resource('/wo_bushings', WoBushingController::class)->except(['create']);
    Route::get('/wo_bushings/create/{id}', [WoBushingController::class, 'create'])->name('wo_bushings.create');
    Route::post('/wo_bushings/get-bushings-from-manual', [WoBushingController::class, 'getBushingsFromManual'])->name('wo_bushings.getBushingsFromManual');

    Route::get('processes/create/{manual_id}', [ProcessController::class, 'create'])->name('processes.create');
    Route::get('processes/edit/{id}', [ProcessController::class, 'edit'])->name('processes.edit');
    Route::get('/get-processes', [ProcessController::class, 'getProcesses'])->name('processes.getProcesses');

    Route::patch('/tdr-processes/{tdrProcess}/dates', [TdrProcessController::class, 'updateDate'])->name('tdrprocesses.updateDate');
    Route::patch('/tdr-processes/{tdrProcess}/repair-order', [MainController::class, 'updateRepairOrder'])->name('tdrprocesses.updateRepairOrder');

    Route::get('/extra_processes/create/{id}', [ExtraProcessController::class, 'create'])->name('extra_process.create');
    Route::get('/extra_processes/create_processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'createProcesses'])->name('extra_processes.create_processes');
    Route::post('/extra_processes/store_processes', [ExtraProcessController::class, 'storeProcesses'])->name('extra_processes.store_processes');
    Route::get('/extra_processes/processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'processes'])->name('extra_processes.processes');
    Route::post('/extra_processes/update-order', [ExtraProcessController::class, 'updateOrder'])->name('extra_processes.update-order');
    Route::get('/extra_processes/show_all/{id}', [ExtraProcessController::class, 'showAll'])->name('extra_processes.show_all');
    Route::get('/extra_processes/{id}', [ExtraProcessController::class, 'show'])->name('extra_processes.show');
    Route::get('/extra_processes/{id}/form/{processNameId}', [ExtraProcessController::class, 'showForm'])->name('extra_processes.show_form');
    Route::get('/extra_processes/{id}/group-forms/{processNameId}', [ExtraProcessController::class, 'showGroupForms'])->name('extra_processes.show_group_forms');
    Route::get('/extra_processes/{id}/edit_component', [ExtraProcessController::class, 'editComponent'])->name('extra_processes.edit_component');
    Route::put('/extra_processes/{id}/update_component', [ExtraProcessController::class, 'updateComponent'])->name('extra_processes.update_component');
    Route::resource('/extra_processes', ExtraProcessController::class)->except(['create']);

    Route::get('log_card/create/{id}', [LogCardController::class, 'create'])->name('log_card.create');
    Route::get('log_card/edit/{id}', [LogCardController::class, 'edit'])->name('log_card.edit');
    Route::get('log_card/show/{id}', [LogCardController::class, 'show'])->name('log_card.show');

    Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');

    Route::post('/components/store_from_inspection', [ComponentController::class, 'storeFromInspection'])->name('components.storeFromInspection');
    Route::post('/components/store_from_extra', [ComponentController::class, 'storeFromExtra'])->name('components.storeFromExtra');

    Route::get('tdr-processes/processesForm/{id}', [TdrProcessController::class, 'processesForm'])->name('tdr-processes.processesForm');
    Route::get('tdr-processes/travelForm/{id}', [TdrProcessController::class, 'travelForm'])->name('tdr-processes.travelForm');

    Route::get('/tdr/{tdrId}/create-processes', [TdrProcessController::class, 'createProcesses'])->name('tdr-processes.createProcesses');
    Route::get('/tdr/{tdrId}/processes', [TdrProcessController::class, 'processes'])->name('tdr-processes.processes');
    Route::get('/tdr/{tdrId}/package-forms', [TdrProcessController::class, 'packageForms'])->name('tdr-processes.packageForms');
    Route::get('/tdr/{tdrId}/traveler', [TdrProcessController::class, 'traveler'])->name('tdr-processes.traveler');
    Route::get('/get-process/{processNameId}', [TdrProcessController::class, 'getProcess'])->name('tdr-processes.get-process');
    Route::post('/tdr-processes/update-order', [TdrProcessController::class, 'updateOrder'])->name('tdr-processes.update-order');

    Route::get('wo_bushings/processesForm/{id}/{processNameId}', [WoBushingController::class, 'processesForm'])->name('wo_bushings.processesForm');

    Route::get('wo_bushings/specProcessForm/{id}', [WoBushingController::class, 'specProcessForm'])->name('wo_bushings.specProcessForm');

    Route::get('api/get-components-by-manual', [TdrController::class, 'getComponentsByManual'])->name('api.get-components-by-manual');

    // NDT/CAD CSV Management Routes
    Route::get('/{workorder}/ndt-cad-csv', [NdtCadCsvController::class, 'index'])->name('ndt-cad-csv.index');
    Route::post('/{workorder}/ndt-cad-csv/ndt-components', [NdtCadCsvController::class, 'updateNdtComponents'])->name('.ndt-cad-csv.update-ndt');
    Route::post('/{workorder}/ndt-cad-csv/cad-components', [NdtCadCsvController::class, 'updateCadComponents'])->name('ndt-cad-csv.update-cad');
    Route::post('/{workorder}/ndt-cad-csv/stress-components', [NdtCadCsvController::class, 'updateStressComponents'])->name('ndt-cad-csv.update-stress');
    Route::post('/{workorder}/ndt-cad-csv/add-ndt', [NdtCadCsvController::class, 'addNdtComponent'])->name('ndt-cad-csv.add-ndt');
    Route::post('/{workorder}/ndt-cad-csv/add-cad', [NdtCadCsvController::class, 'addCadComponent'])->name('ndt-cad-csv.add-cad');
    Route::post('/{workorder}/ndt-cad-csv/add-stress', [NdtCadCsvController::class, 'addStressComponent'])->name('ndt-cad-csv.add-stress');
    Route::post('/{workorder}/ndt-cad-csv/add-paint', [NdtCadCsvController::class, 'addPaintComponent'])->name('ndt-cad-csv.add-paint');
    Route::post('/{workorder}/ndt-cad-csv/remove-ndt', [NdtCadCsvController::class, 'removeNdtComponent'])->name('ndt-cad-csv.remove-ndt');
    Route::post('/{workorder}/ndt-cad-csv/remove-cad', [NdtCadCsvController::class, 'removeCadComponent'])->name('ndt-cad-csv.remove-cad');
    Route::post('/{workorder}/ndt-cad-csv/remove-stress', [NdtCadCsvController::class, 'removeStressComponent'])->name('ndt-cad-csv.remove-stress');
    Route::post('/{workorder}/ndt-cad-csv/remove-paint', [NdtCadCsvController::class, 'removePaintComponent'])->name('ndt-cad-csv.remove-paint');
    Route::post('/{workorder}/ndt-cad-csv/edit-ndt', [NdtCadCsvController::class, 'editNdtComponent'])->name('ndt-cad-csv.edit-ndt');
    Route::post('/{workorder}/ndt-cad-csv/edit-cad', [NdtCadCsvController::class, 'editCadComponent'])->name('ndt-cad-csv.edit-cad');
    Route::post('/{workorder}/ndt-cad-csv/edit-stress', [NdtCadCsvController::class, 'editStressComponent'])->name('ndt-cad-csv.edit-stress');
    Route::post('/{workorder}/ndt-cad-csv/edit-paint', [NdtCadCsvController::class, 'editPaintComponent'])->name('ndt-cad-csv.edit-paint');
    Route::post('/{workorder}/ndt-cad-csv/import', [NdtCadCsvController::class, 'importFromCsv'])->name('ndt-cad-csv.import');
    Route::post('/{workorder}/ndt-cad-csv/reload-from-manual', [NdtCadCsvController::class, 'reloadFromManual'])->name('ndt-cad-csv.reload-from-manual');
    Route::post('/{workorder}/ndt-cad-csv/force-load-from-manual', [NdtCadCsvController::class, 'forceLoadFromManual'])->name('ndt-cad-csv.force-load-from-manual');
    Route::get('/{workorder}/ndt-cad-csv/components', [NdtCadCsvController::class, 'getComponents'])->name('ndt-cad-csv.components');
    Route::get('/{workorder}/ndt-cad-csv/cad-processes', [NdtCadCsvController::class, 'getCadProcesses'])->name('ndt-cad-csv.cad-processes');
    Route::get('/{workorder}/ndt-cad-csv/stress-processes', [NdtCadCsvController::class, 'getStressProcesses'])->name('ndt-cad-csv.stress-processes');
    Route::get('/{workorder}/ndt-cad-csv/paint-processes', [NdtCadCsvController::class, 'getPaintProcesses'])->name('ndt-cad-csv.paint-processes');

    Route::get('/rm_reports/create/{id}',[RmReportController::class,'create'])->name('rm_reports.create');
    Route::resource('/rm_reports', RmReportController::class)->except('create');
    Route::delete('/rm_reports/multiple', [RmReportController::class, 'destroyMultiple'])->name('rm_reports.destroy.multiple');
    Route::post('/rm_reports/save-to-workorder', [RmReportController::class, 'saveToWorkorder'])->name('rm_reports.save.to.workorder');
    Route::get('rm_reports/rmRecordForm/{id}',[RmReportController::class,'rmRecordForm'])->name('rm_reports.rmRecordForm');
    Route::get('/rm_reports/get-record/{id}', [RmReportController::class, 'getRecord'])->name('rm_reports.getRecord');
    Route::put('/rm_reports/update-record/{id}', [RmReportController::class, 'updateRecord'])->name('rm_reports.updateRecord');

    Route::get('/api/get-components-by-manual', [TdrController::class, 'getComponentsByManual'])->name('api.get-components-by-manual');

    // CSV файлы для мануалов
    Route::prefix('manuals/{manual}/csv')->name('manuals.csv.')->group(function () {
        Route::post('/', [ManualCsvController::class, 'store'])->name('store');
        Route::get('/{file}/data', [ManualCsvController::class, 'data'])->name('data');
        Route::get('/{file}', [ManualCsvController::class, 'view'])->name('view');
        Route::delete('/{file}', [ManualCsvController::class, 'delete'])->name('delete');
    });

    // Notification

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/settings', [NotificationController::class, 'show'])->name('notifications.settings.show');
    Route::post('/notifications/settings', [NotificationController::class, 'save'])->name('notifications.settings.save');

    Route::get('/admin/activity', [ActivityLogController::class, 'index'])->name('admin.activity.index');

});


Route::middleware(['auth'])->prefix('admin/messages')->group(function () {

    Route::get('/users', [\App\Http\Controllers\Admin\MessageController::class, 'users'])->name('admin.messages.users');
    Route::post('/send', [\App\Http\Controllers\Admin\MessageController::class, 'send'])->name('admin.messages.send');


});


Route::middleware(['auth'])
    ->prefix('admin')
    ->group(function () {

        foreach (array_keys(config('directories')) as $slug) {

            Route::resource($slug, DirectoryController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->parameters([$slug => 'id']) // чтобы было {id}, а не {role}/{plane}
                ->names([
                    'index'   => "{$slug}.index",
                    'store'   => "{$slug}.store",
                    'update'  => "{$slug}.update",
                    'destroy' => "{$slug}.destroy",
                ]);
        }
    });
