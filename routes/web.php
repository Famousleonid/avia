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
use App\Http\Controllers\Admin\ReportController;
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
use App\Http\Controllers\Admin\VendorTrackingController;
use App\Http\Controllers\Admin\WorkorderController;
use App\Http\Controllers\Admin\WoBushingController;
use App\Http\Controllers\Admin\NdtCadCsvController;
use App\Http\Controllers\Admin\MachiningController;
use App\Http\Controllers\Admin\PaintController;
use App\Http\Controllers\Admin\ManualStdProcessController;
use App\Http\Controllers\Admin\NotificationEventRuleController;
use App\Http\Controllers\Admin\DateNotificationController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\General\MediaController;
use App\Http\Controllers\General\NotificationController;
use App\Http\Controllers\Mobile\MobileComponentController;
use App\Http\Controllers\Mobile\MobileController;
use App\Http\Controllers\Mobile\MobileProcessController;
use App\Http\Controllers\Mobile\MobileTaskController;
use App\Http\Controllers\ProfileController;
use App\Support\Device;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ManualCsvController;
use App\Http\Controllers\Admin\AiAgentController;
use App\Http\Controllers\Admin\DatabaseBackupController;

Auth::routes(['verify' => true, 'register' => false]);

Route::get('/clear', function () {
    abort_unless(auth()->user()?->roleIs('Admin'), 403);

    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');

    return 'Cache cleared successfully';
})->middleware(['auth', 'verified', 'desktop']);


Route::get('/front', [FrontController::class, 'index'])->name('front.index');
Route::get('/', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return view('front.index');
    }
    return redirect(Device::homePath($request));
})->name('home');

Route::middleware(['auth', 'verified', 'desktop'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/cabinet/profile', [ProfileController::class, 'edit'])->name('cabinet.profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
});

Route::middleware(['auth'])->get('/session/heartbeat', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'ok' => true,
        'user_id' => $request->user()?->id,
        'server_time' => now()->toIso8601String(),
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
})->name('session.heartbeat');

// ----------------------- Mobile route -----------------------------------------------------------------
Route::prefix('mobile')->name('mobile.')->middleware(['auth','verified'])->group(function () {

    // --- general pages  ---
    Route::get('/', [MobileController::class, 'index'])->name('index');
    Route::get('/show/{workorder}', [MobileController::class, 'show'])->name('show');
    Route::patch('/workorders/{workorder}/storage', [MobileController::class, 'updateStorage'])->name('workorders.storage.update');
    Route::get('/draft', [MobileController::class, 'createDraft'])->name('draft');
    Route::post('/workorders/draft', [MobileController::class, 'storeDraft'])->name('draft.store');
    Route::post('/draft/units/pending', [MobileController::class, 'storePendingDraftUnit'])->name('draft.units.pending.store');

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
    Route::get('/paint', [MobileController::class, 'paint'])->name('paint');
    Route::post('/paint/lost', [MobileController::class, 'storePaintLost'])->name('paint.lost.store');
    Route::delete('/paint/lost/{paint}', [MobileController::class, 'destroyPaintLost'])->name('paint.lost.destroy');

    Route::get('/machining', [MobileController::class, 'machining'])->name('machining');
    Route::get('/machining/{workorder}', [MobileController::class, 'machiningWorkorder'])->name('machining.workorder');
    Route::patch('/machining/steps/{machiningWorkStep}', [MobileController::class, 'updateMachiningWorkStepMobile'])
        ->name('machining.steps.update');

    // --- media ---
    Route::post('/workorders/photo/{workorder}', [MediaController::class, 'store_photo_workorders'])->name('workorders.media.store');
    Route::delete('/workorders/photo/delete/{id}', [MediaController::class, 'delete_photo'])->name('workorders.photo.delete');
    Route::get('/workorders/photos/{id}', [MediaController::class, 'get_photos'])->name('workorders.photos');


    // --- profile ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('update.profile');
    Route::post('/change_password/user', [ProfileController::class, 'changePassword'])->name('profile.changePassword');
});

// ----------------------Auth route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth', 'verified', 'desktop']], function () {

    Route::get('/cabinet', [CabinetController::class, 'index'])->name('cabinet.index');
    Route::get('/paint', [PaintController::class, 'index'])->middleware('can:feature.paint')->name('paint.index');
    Route::post('/paint/reorder', [PaintController::class, 'reorder'])->middleware('can:feature.paint')->name('paint.reorder');
    Route::post('/paint/add', [PaintController::class, 'addToQueue'])->middleware('can:feature.paint')->name('paint.add');
    Route::post('/paint/position', [PaintController::class, 'setPosition'])->middleware('can:feature.paint')->name('paint.position');
    Route::post('/paint/lost', [PaintController::class, 'storeLost'])->middleware('can:feature.paint')->name('paint.lost.store');
    Route::delete('/paint/lost/{paint}', [PaintController::class, 'destroyLost'])->middleware('can:feature.paint')->name('paint.lost.destroy');

    Route::get('/machining', [MachiningController::class, 'index'])->middleware('can:feature.machining')->name('machining.index');
    Route::get('/machining/table-fragment', [MachiningController::class, 'tableFragment'])->middleware('can:feature.machining')->name('machining.table_fragment');
    Route::post('/machining/reorder', [MachiningController::class, 'reorder'])->middleware('can:feature.machining')->name('machining.reorder');
    Route::post('/machining/add', [MachiningController::class, 'addToQueue'])->middleware('can:feature.machining')->name('machining.add');
    Route::post('/machining/position', [MachiningController::class, 'setPosition'])->middleware('can:feature.machining')->name('machining.position');
    Route::patch('/machining/work-steps/{machiningWorkStep}', [MachiningController::class, 'updateMachiningWorkStep'])->middleware('can:feature.machining')->name('machining.work_steps.update');
    Route::patch('/tdr-processes/{tdrProcess}/working-steps-count', [MachiningController::class, 'updateTdrWorkingStepsCount'])->middleware('can:feature.machining')->name('machining.tdr_working_steps_count');
    Route::patch('/wo-bushing-batches/{woBushingBatch}/working-steps-count', [MachiningController::class, 'updateBatchWorkingStepsCount'])->middleware('can:feature.machining')->name('machining.batch_working_steps_count');
    Route::patch('/wo-bushing-processes/{woBushingProcess}/working-steps-count', [MachiningController::class, 'updateProcessWorkingStepsCount'])->middleware('can:feature.machining')->name('machining.process_working_steps_count');

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
    Route::get('/mains/{workorder}/photos', [MainController::class, 'photos'])->name('mains.photos');
    Route::patch('/mains/{workorder}/wo-bushing-process-group/{process}/repair-order', [MainController::class, 'updateWoBushingProcessGroupRepairOrder'])->name('mains.wo_bushing_group.repair_order');
    Route::patch('/mains/{workorder}/wo-bushing-process-group/{process}/dates', [MainController::class, 'updateWoBushingProcessGroupDates'])->name('mains.wo_bushing_group.dates');

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
        Route::get('tdrs/edit-form/{id}', [TdrController::class, 'editForm'])->name('tdrs.editForm');

        Route::get('tdrs/show/{id}', [TdrController::class, 'show'])->name('tdrs.show');

        Route::get('tdrs/show2/{id}', function ($id) {
            return redirect()->route('tdrs.show', ['id' => $id], 301);
        });
        Route::get('tdrs/processes/{workorder_id}',[TdrController::class, 'processes'])->name('tdrs.processes');
        Route::get('tdrs/{id}/group-forms/{processNameId}', [TdrController::class, 'showGroupForms'])->name('tdrs.show_group_forms');
        Route::get('/tdrs/inspection/{workorder_id}',[TdrController::class, 'inspection'])->name('tdrs.inspection');
        Route::get('tdrs/logCardForm/{id}', [TdrController::class, 'logCardForm'])->name('tdrs.logCardForm');
        Route::get('log_card/logCardForm/{id}', [LogCardController::class, 'logCardForm'])->name('log_card.logCardForm');
        Route::get('tdrs/woProcessForm/{id}', [TdrController::class, 'wo_Process_Form'])->name('tdrs.woProcessForm');
        Route::get('tdrs/woBoxTitle/{id}', [TdrController::class, 'wo_BoxTitle'])->name('tdrs.wo_BoxTitle');

    Route::get('tdrs/processes-partial/{workorder_id}', [TdrController::class, 'processesPartial'])->name('tdrs.processesPartial');
        Route::get('tdrs/log-card-partial/{workorder_id}', [LogCardController::class, 'partial'])->name('log_card.partial');
        Route::get('tdrs/transfers-partial/{workorder}', [TransferController::class, 'partial'])->name('transfers.partial');


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
    Route::patch('/units/{unit}/assign-manual', [UnitController::class, 'assignManual'])->name('units.assignManual');

    Route::get('wo_bushings/partial/{workorder_id}', [WoBushingController::class, 'partial'])->name('wo_bushings.partial');
    Route::resource('/wo_bushings', WoBushingController::class)->except(['create']);
    Route::get('/wo_bushings/create/{id}', [WoBushingController::class, 'create'])->name('wo_bushings.create');
    Route::post('/wo_bushings/get-bushings-from-manual', [WoBushingController::class, 'getBushingsFromManual'])->name('wo_bushings.getBushingsFromManual');
    Route::post('/wo_bushings/{woBushing}/batches', [WoBushingController::class, 'createBatch'])->name('wo_bushings.batches.create');
    Route::post('/wo_bushings/{woBushing}/batches/ungroup', [WoBushingController::class, 'ungroupBatch'])->name('wo_bushings.batches.ungroup');

    Route::get('processes/create/{manual_id}', [ProcessController::class, 'create'])->name('processes.create');
    Route::get('processes/edit/{id}', [ProcessController::class, 'edit'])->name('processes.edit');
    Route::get('/get-processes', [ProcessController::class, 'getProcesses'])->name('processes.getProcesses');

    Route::patch('/wo-bushing-processes/{woBushingProcess}/repair-order', [MainController::class, 'updateWoBushingProcessRepairOrder'])->name('wo_bushing_processes.updateRepairOrder');
    Route::patch('/wo-bushing-processes/{woBushingProcess}/dates', [MainController::class, 'updateWoBushingProcessDate'])->name('wo_bushing_processes.updateDate');
    Route::patch('/wo-bushing-batches/{woBushingBatch}/repair-order', [MainController::class, 'updateWoBushingBatchRepairOrder'])->name('wo_bushing_batches.updateRepairOrder');
    Route::patch('/wo-bushing-batches/{woBushingBatch}/dates', [MainController::class, 'updateWoBushingBatchDate'])->name('wo_bushing_batches.updateDate');

    Route::patch('/tdr-processes/{tdrProcess}/dates', [TdrProcessController::class, 'updateDate'])->name('tdrprocesses.updateDate');
    Route::patch('/tdrs/{tdr}/traveler-group/dates', [TdrProcessController::class, 'updateTravelerGroupDates'])->name('tdrprocesses.updateTravelerGroupDates');
    Route::patch('/tdrs/{tdr}/traveler-group/repair-order', [MainController::class, 'updateTravelerGroupRepairOrder'])->name('tdrprocesses.updateTravelerGroupRepairOrder');
    Route::patch('/tdr-processes/{tdrProcess}/repair-order', [MainController::class, 'updateRepairOrder'])->name('tdrprocesses.updateRepairOrder');
    Route::patch('/tdr-processes/{tdrProcess}/ignore-row', [MainController::class, 'updateIgnoreRow'])->name('tdrprocesses.updateIgnoreRow');

    Route::get('/extra_processes/create/{id}', [ExtraProcessController::class, 'create'])->name('extra_process.create');
    Route::get('/extra_processes/create_processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'createProcesses'])->name('extra_processes.create_processes');
    Route::post('/extra_processes/store_processes', [ExtraProcessController::class, 'storeProcesses'])->name('extra_processes.store_processes');
    Route::get('/extra_processes/processes/{workorderId}/{componentId}', [ExtraProcessController::class, 'processes'])->name('extra_processes.processes');
    Route::get('/extra_processes/processes-partial/{workorderId}/{componentId}', [ExtraProcessController::class, 'processesPartial'])->name('extra_processes.processesPartial');
    Route::post('/extra_processes/update-order', [ExtraProcessController::class, 'updateOrder'])->name('extra_processes.update-order');
    Route::get('/extra_processes/show_all/{id}', [ExtraProcessController::class, 'showAll'])->name('extra_processes.show_all');
    Route::get('/extra_processes/partial/{workorder_id}', [ExtraProcessController::class, 'extraProcessesPartial'])->name('extra_processes.partial');
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
    Route::get('/vendor-tracking', [VendorTrackingController::class, 'index'])->name('vendor-tracking.index');

    Route::post('/components/store_from_inspection', [ComponentController::class, 'storeFromInspection'])->name('components.storeFromInspection');
    Route::post('/components/store_from_extra', [ComponentController::class, 'storeFromExtra'])->name('components.storeFromExtra');

    Route::get('tdr-processes/processesForm/{id}', [TdrProcessController::class, 'processesForm'])->name('tdr-processes.processesForm');
    Route::get('tdr-processes/travelForm/{id}', [TdrProcessController::class, 'travelForm'])->name('tdr-processes.travelForm');

    Route::get('/tdr/{tdrId}/create-processes', [TdrProcessController::class, 'createProcesses'])->name('tdr-processes.createProcesses');
    Route::get('/tdr/{tdrId}/processes', [TdrProcessController::class, 'processes'])->name('tdr-processes.processes');
    Route::get('/tdr/{tdrId}/processes-body', [TdrProcessController::class, 'processesBodyPartial'])->name('tdr-processes.processesBody');
    Route::get('tdr-processes/edit-form/{id}', [TdrProcessController::class, 'editFormPartial'])->name('tdr-processes.editForm');
    Route::get('/tdr/{tdrId}/package-forms', [TdrProcessController::class, 'packageForms'])->name('tdr-processes.packageForms');
    Route::get('/tdr/{tdrId}/traveler', [TdrProcessController::class, 'traveler'])->name('tdr-processes.traveler');
    Route::get('/get-process/{processNameId}', [TdrProcessController::class, 'getProcess'])->name('tdr-processes.get-process');
    Route::post('/tdr-processes/update-order', [TdrProcessController::class, 'updateOrder'])->name('tdr-processes.update-order');
    Route::post('/tdr/{tdrId}/traveler-group', [TdrProcessController::class, 'travelerGroup'])->name('tdr-processes.traveler-group');
    Route::post('/tdr/{tdrId}/traveler-ungroup', [TdrProcessController::class, 'travelerUngroup'])->name('tdr-processes.traveler-ungroup');

    Route::get('wo_bushings/processesForm/{id}/{processNameId}', [WoBushingController::class, 'processesForm'])->name('wo_bushings.processesForm');

    Route::get('wo_bushings/specProcessForm/{id}', [WoBushingController::class, 'specProcessForm'])->name('wo_bushings.specProcessForm');

//    Route::get('api/get-components-by-manual', [TdrController::class, 'getComponentsByManual'])->name('api.get-components-by-manual');

    // NDT/CAD CSV Management Routes
    Route::get('/{workorder}/ndt-cad-csv/partial', [NdtCadCsvController::class, 'partial'])->name('ndt-cad-csv.partial');
    Route::get('/{workorder}/ndt-cad-csv', [NdtCadCsvController::class, 'index'])->name('ndt-cad-csv.index');
    Route::post('/{workorder}/ndt-cad-csv/ndt-components', [NdtCadCsvController::class, 'updateNdtComponents'])->name('ndt-cad-csv.update-ndt');
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

    Route::get('/rm_reports/create/{id}', fn($id) => redirect()->route('rm_reports.show', $id))->name('rm_reports.create');
    Route::get('/rm_reports/partial/{workorder_id}', [RmReportController::class, 'partial'])->name('rm_reports.partial');
    Route::resource('/rm_reports', RmReportController::class)->except('create', 'edit');
    Route::get('/rm_reports/{id}/edit', fn($id) => redirect()->route('rm_reports.show', $id))->name('rm_reports.edit');
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

    Route::get('manuals/{manual}/std-processes/components-for-add', [ManualStdProcessController::class, 'componentsForAdd'])->name('manuals.std-processes.components-for-add');
    Route::post('manuals/{manual}/std-processes', [ManualStdProcessController::class, 'store'])->name('manuals.std-processes.store');
    Route::post('manuals/{manual}/std-processes/reimport-from-csv', [ManualStdProcessController::class, 'reimportFromCsv'])->name('manuals.std-processes.reimport-from-csv');
    Route::put('manuals/{manual}/std-processes/{stdProcess}', [ManualStdProcessController::class, 'update'])->name('manuals.std-processes.update');
    Route::delete('manuals/{manual}/std-processes/{stdProcess}', [ManualStdProcessController::class, 'destroy'])->name('manuals.std-processes.destroy');

    // Notification

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/settings', [NotificationController::class, 'show'])->name('notifications.settings.show');
    Route::post('/notifications/settings', [NotificationController::class, 'save'])->name('notifications.settings.save');
    Route::delete('/notifications', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');



    Route::get('/admin/activity', [ActivityLogController::class, 'index'])->name('admin.activity.index');
    Route::post('/admin/activity/purge', [ActivityLogController::class, 'purge'])->name('admin.activity.purge');
    Route::post('/admin/database-backup', [DatabaseBackupController::class, 'store'])->name('admin.database.backup');
    Route::post('/reports/table/pdf', [ReportController::class, 'tablePdf'])->name('reports.table.pdf');
    Route::patch('/workorders/{workorder}/storage', [WorkorderController::class, 'updateStorage'])->name('workorders.storage.update');

});

Route::middleware(['auth', 'verified', 'desktop'])->prefix('admin')->group(function () {
    Route::get('/ai-agent/history', [AiAgentController::class, 'history'])->name('admin.ai.history');
    Route::post('/ai-agent/chat', [AiAgentController::class, 'chat'])->name('admin.ai.chat');
    Route::post('/ai-agent/reset', [AiAgentController::class, 'reset'])->name('admin.ai.reset');
    Route::resource('/notification-rules', NotificationEventRuleController::class)
        ->except(['show', 'create', 'edit'])
        ->names('admin.notification-rules');
    Route::resource('/date-notifications', DateNotificationController::class)
        ->except(['show', 'create', 'edit'])
        ->names('admin.date-notifications');
    Route::get('/tests', [\App\Http\Controllers\Admin\TestDashboardController::class, 'index'])->name('admin.tests.index');
    Route::post('/tests/{suite}/run', [\App\Http\Controllers\Admin\TestDashboardController::class, 'run'])->name('admin.tests.run');
});




Route::middleware(['auth', 'verified', 'desktop'])->prefix('admin/messages')->group(function () {

    Route::get('/users', [\App\Http\Controllers\Admin\MessageController::class, 'users'])->name('admin.messages.users');
    Route::post('/send', [\App\Http\Controllers\Admin\MessageController::class, 'send'])->name('admin.messages.send');


});


Route::middleware(['auth', 'verified', 'desktop'])
    ->prefix('admin')
    ->group(function () {

        Route::patch('{directory}/toggle/{id}/{field}', [DirectoryController::class, 'toggle'])->name('directories.toggle');

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
