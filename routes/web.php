<?php

use App\Http\Controllers\Admin\AdminController;

use App\Http\Controllers\Admin\BuilderController;
use App\Http\Controllers\Admin\ManualController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\PlaneController;
use App\Http\Controllers\Admin\ScopeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Cabinet\MainController;
use App\Http\Controllers\Cabinet\ProgressController;
use App\Http\Controllers\Cabinet\TechnikController;
use App\Http\Controllers\Cabinet\TrainingController;
use App\Http\Controllers\Cabinet\UnitController;
use App\Http\Controllers\Cabinet\WorkorderController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\Cabinet\CabinetController;
use App\Http\Controllers\Admin\GeneralTaskController;
use App\Http\Controllers\Cabinet\CustomerController;
use App\Http\Controllers\General\MediaController;
use App\Http\Controllers\Admin\TaskController;
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
    return "clean Ok";
});

Route::get('/', [FrontController::class, 'index'])->name('home');
Route::get('/mobile', [MobileController::class,'index'])->name('mobile.index');;

// ---------------------- User Auth route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth'], 'prefix' => 'cabinet'], function () {

    Route::get('/', [CabinetController::class, 'index'])->name('cabinet.index');
    Route::get('/profile', [CabinetController::class, 'profile'])->name('cabinet.profile');
    Route::get('trainings/form112/{id}', [TrainingController::class, 'showForm112'])->name('training.form112');
    Route::get('trainings/form132/{id}', [TrainingController::class, 'showForm132'])->name('training.form132');

    Route::resource('/training', TrainingController::class);
    Route::resource('/main', MainController::class);
    Route::resource('/user', UserController::class);
    Route::resource('/workorder', WorkorderController::class);
    Route::resource('/unit', UnitController::class);
    Route::resource('/customer', CustomerController::class);
    Route::resource('/technik', TechnikController::class);
    Route::resource('/materials', MaterialController::class);
    Route::resource('/manuals-user',ManualController::class);

    Route::post('profile/change_password/user/{id}/', [UserController::class, 'changePassword'])->name('profile.changePassword');
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::post('/progress/technik', [ProgressController::class, 'technik'])->name('progress.technik');


    // ----------------------- Media route -----------------------------------------------------------------

    Route::post('/user/avatar/{id}', [MediaController::class, 'store_avatar'])->name('avatar.media.store');
    Route::get('/image/show/thumb/{mediaId}/user/{modelId}/name/{mediaName}', [MediaController::class, 'showThumb'])->name('image.show.thumb');
    Route::get('/image/show/big/{mediaId}/user/{modelId}/name/{mediaName}', [MediaController::class, 'showBig'])->name('image.show.big');

});

// ----------------------Admin route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth', 'isAdmin'], 'prefix' => 'admin'], function () {

    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/logs', [AdminController::class, 'activity'])->name('log.activity');
    Route::resource('/task', TaskController::class);
    Route::resource('/general_task', GeneralTaskController::class);
    Route::resource('/user', UserController::class);
    Route::resource('/customers', CustomerController::class);
    Route::resource('/manuals',ManualController::class);

    Route::post('/planes/store',[PlaneController::class, 'store'])->name('planes.store');
    Route::post('/builders/store', [BuilderController::class,'store'])->name('builders.store');
    Route::post('/scopes/store',  [ScopeController::class,'store'])->name('scopes.store');
});



