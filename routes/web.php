<?php

use App\Http\Controllers\Cabinet\ManualController;
use App\Http\Controllers\Cabinet\MaterialController;
use App\Http\Controllers\Cabinet\UserController;
use App\Http\Controllers\Cabinet\MainController;
use App\Http\Controllers\Cabinet\ProgressController;
use App\Http\Controllers\Cabinet\TrainingController;
use App\Http\Controllers\Cabinet\UnitController;
use App\Http\Controllers\Cabinet\WorkorderController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\Cabinet\CabinetController;
use App\Http\Controllers\Cabinet\CustomerController;
use App\Http\Controllers\General\MediaController;
use App\Http\Controllers\Mobile\MobileController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes(['verify' => true]);

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    return "Cache cleared successfully!";

});

Route::get('/', [FrontController::class, 'index'])->name('home');
Route::get('/mobile', [MobileController::class,'index'])->name('mobile.index');;


// ---------------------- User Auth route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth'], 'prefix' => 'cabinet', 'as' =>'cabinet.'], function () {

    Route::get('/', [CabinetController::class, 'index'])->name('index');
    Route::resource('/trainings', TrainingController::class);
    Route::resource('/mains', MainController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/workorders', WorkorderController::class);
    Route::resource('/units', UnitController::class);
    Route::resource('/customers', CustomerController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/materials', MaterialController::class);
    Route::resource('/manuals',ManualController::class);

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
    Route::resource('/units',  \App\Http\Controllers\Admin\UnitController::class)->except('update');

    Route::get('/workorders/approve/{id}/', [\App\Http\Controllers\Admin\WorkorderController::class, 'approve'])->name('workorders.approve');
    Route::post('workorders/{workorder}/inspection', [\App\Http\Controllers\Admin\WorkorderController::class, 'updateInspect'])->name('workorders.inspection');

 //   Route::post('/units/{manualId}', [\App\Http\Controllers\Admin\UnitController::class, 'update'])->name('units.update');

    Route::resource('/tdrs',\App\Http\Controllers\Admin\TdrController::class);


    Route::get('/tdrs/inspection/{workorder_id}',[\App\Http\Controllers\Admin\TdrController::class, 'inspection'])
        ->name('tdrs.inspection');
    Route::get('tdrs/tdrForm/{id}', [\App\Http\Controllers\Admin\TdrController::class, 'tdrForm'])->name('tdrs.tdrForm');

    Route::resource('/components', \App\Http\Controllers\Admin\ComponentController::class);

    Route::post('/components/store_from_inspection', [\App\Http\Controllers\Admin\ComponentController::class, 'storeFromInspection'])
        ->name('components.storeFromInspection');

    Route::resource('/processes', \App\Http\Controllers\Admin\ProcessController::class);
    Route::resource('/process-names',\App\Http\Controllers\Admin\ProcessNameController::class);

});




