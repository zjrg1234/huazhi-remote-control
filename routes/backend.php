<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\WarZoneController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\AdminUserController;


Route::post('/login', [AdminController::class,'login']); //后台登陆
//    Route::post('/register', [AdminController::class,'register']);
Route::post('/logout', [AdminController::class,'logout']); //退出
//    Route::get('/user', [AdminController::class,'getAuthUser']);
//Route::post('/reset/google2fa',[AdminController::class,'resetGoogle2fa']); //重制谷歌 暂时先不要


Route::group(['middleware' => 'AuthToken'], function () {
    Route::group(['prefix' => 'special'], function () { //专区
        Route::post('/create',[WarZoneController::class,'create']);
        Route::post('/list',[WarZoneController::class,'list']);
    });

    Route::group(['prefix' => 'user'], function () { //用户管理
        Route::post('/details',[AdminUserController::class,'details']);
        Route::post('/list',[AdminUserController::class,'list']);//列表
        Route::post('/modify/balance',[AdminUserController::class,'modifyBalance']);//列表

    });



});
