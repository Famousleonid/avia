<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminWorkorderController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\GeneralTaskController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TechnikController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Cabinet\CabinetController;
use App\Http\Controllers\Cabinet\MediaController;
use App\Http\Controllers\Cabinet\UnderwayController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\Main\ComponentMainController;
use App\Http\Controllers\Main\MainController;
use App\Http\Controllers\Mobile\MobileController;
use App\Http\Controllers\Workorder\WorkorderController;
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

Route::group(['middleware' => ['auth']], function () {
    Route::resource('/user', UserController::class);
});

// ----------------------User route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth'], 'prefix' => 'cabinet'], function () {

    Route::resource('/workorder', WorkorderController::class)->except(['show']);
    Route::resource('/unit', UnitController::class);
    Route::resource('/main', MainController::class)->except(['show', 'index']);
    Route::resource('/component_main', ComponentMainController::class);

    Route::get('/customer/index', [\App\Http\Controllers\Cabinet\CustomerController::class, 'index'])->name('cabinet.customer.index');
    Route::get('/customer/create', [\App\Http\Controllers\Cabinet\CustomerController::class, 'create'])->name('cabinet.customer.create');
    Route::get('/customer/store', [\App\Http\Controllers\Cabinet\CustomerController::class, 'store'])->name('cabinet.customer.store');
    Route::get('/customer/edit/{id}', [\App\Http\Controllers\Cabinet\CustomerController::class, 'edit'])->name('cabinet.customer.edit');
    Route::put('/customer/update/{id}', [\App\Http\Controllers\Cabinet\CustomerController::class, 'update'])->name('cabinet.customer.update');
    Route::delete('/customer/delete/{id}', [\App\Http\Controllers\Cabinet\CustomerController::class, 'destroy'])->name('cabinet.customer.destroy');


    Route::get('/main/{workorder_id}', [MainController::class, 'index'])->name('main.index');
    Route::get('/main/components/{workorder_id}', [ComponentMainController::class, 'index'])->name('component.workorders');

    Route::get('/', [CabinetController::class, 'index'])->name('cabinet.index');
    Route::post('profile/change_password/user/{id}/', [UserController::class, 'changePassword'])->name('profile.changePassword');

    Route::get('/underway', [UnderwayController::class, 'index'])->name('underway.index');
    Route::post('/underway/technik', [UnderwayController::class, 'technik'])->name('underway.technik');

    Route::get('/workorders', [CabinetController::class, 'workorders'])->name('cabinet.workorders');

    Route::get('/profile', [CabinetController::class, 'profile'])->name('cabinet.profile');
    Route::get('/workorders/approve/{id}/', [CabinetController::class, 'approve'])->name('cabinet.workorders.approve');
    Route::get('/workorders/paper/{id}/', [CabinetController::class, 'paper'])->name('cabinet.workorders.paper');
    Route::get('/materials', [CabinetController::class, 'materials'])->name('cabinet.materials');
    Route::get('/techniks', [CabinetController::class, 'techniks'])->name('cabinet.techniks.view');

    // ----------------------- Media route -----------------------------------------------------------------

    Route::post('/user/avatar/{id}', [MediaController::class, 'store_avatar'])->name('avatar.media.store');
    Route::post('/mobile/user/avatar/{id}', [MediaController::class, 'mobile_store_avatar'])->name('mobile.avatar.media.store');

    Route::get('/image/show/thumb/{mediaId}/user/{modelId}/name/{mediaName}', [MediaController::class, 'showThumb'])->name('image.show.thumb');
    Route::get('/image/show/big/{mediaId}/user/{modelId}/name/{mediaName}', [MediaController::class, 'showBig'])->name('image.show.big');

    // ----------------------- Mobile route -----------------------------------------------------------------

    Route::get('/mobile/workorders', [MobileController::class, 'index'])->name('mobile.workorder.index');
    Route::post('/mobile/show/workorder/', [MobileController::class, 'show_wo'])->name('mobile.show.workorder');
    // Route::get('/mobile/create/{wo_id}', [MobileController::class, 'create'])->name('photos.create');
    Route::post('/mobile/store/', [MobileController::class, 'store'])->name('photos.store');
    Route::get('/mobile', [MobileController::class, 'index'])->name('mobile.index');
    Route::get('/mobile/profile', [MobileController::class, 'profile'])->name('mobile.profile');
    Route::get('/mobile/materials', [MobileController::class, 'materials'])->name('mobile.materials');
    Route::get('/image/{mediaId}/workorder/{modelId}/name/{mediaName}/show_thumb', [MobileController::class, 'photoShowThumb'])->name('photo.show.thumb');

    // ----------------------- Excel route -----------------------------------------------------------------

    Route::get('/ndt-excel/{workorder_id}', [ExcelController::class, 'ndtExport'])->name('ndt.excel.export');
    Route::get('/cad-excel/{workorder_id}', [ExcelController::class, 'cadExport'])->name('cad.excel.export');
});

// ----------------------Admin route ------------------------------------------------------------------------

Route::group(['middleware' => ['auth', 'isAdmin'], 'prefix' => 'admin'], function () {

    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::resource('/component', ComponentController::class);
    Route::resource('/task', TaskController::class);
    Route::resource('/general_task', GeneralTaskController::class);
    Route::resource('/techniks', TechnikController::class);
    Route::resource('/customers', CustomerController::class);
    Route::resource('/admin-workorders', AdminWorkorderController::class);
    Route::get('/logs', [AdminController::class, 'activity'])->name('log.activity');
});



