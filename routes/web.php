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
    Route::get('/profile', [CabinetController::class, 'profile'])->name('profile');
    Route::get('trainings/form112/{id}', [TrainingController::class, 'showForm112'])->name('trainings.form112');
    Route::get('trainings/form132/{id}', [TrainingController::class, 'showForm132'])->name('trainings.form132');

    Route::resource('/trainings', TrainingController::class);
    Route::resource('/mains', MainController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/workorders', WorkorderController::class);
    Route::resource('/units', UnitController::class);
    Route::resource('/customers', CustomerController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/materials', MaterialController::class);
    Route::resource('/manuals',ManualController::class);

    Route::post('profile/change_password/user/{id}/', [UserController::class, 'changePassword'])->name('profile.changePassword');
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::post('/progress/technik', [ProgressController::class, 'technik'])->name('progress.technik');
    Route::get('/materials-search', [MaterialController::class, 'search'])->name('materials.search');
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
    Route::resource('/units',  \App\Http\Controllers\Admin\UnitController::class);


    Route::resource('/units',\App\Http\Controllers\Admin\UnitController::class);
//    Route::get('units/{manualId}', [\App\Http\Controllers\Admin\UnitController::class,'getUnitsByManual'])->name('admin.units.byManual');


});



