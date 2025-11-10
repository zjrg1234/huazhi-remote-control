<?php

use App\Http\Controllers\Home\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Home\VehicleController;
use App\Http\Controllers\Home\VenueController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'checkAesEntry'], function () { //所有接口走加解密
    Route::post('/login/loginIn', [LoginController::class, 'login']);//前台登陆
    Route::post('/login/save', [LoginController::class, 'register']);//前台注册
    Route::post('/get/login/code', [LoginController::class, 'getLoginCode']);//获取验证码
    Route::group(['middleware'=>'CheckToken'], function () { //登陆后的接口走token校验
        Route::post('/login/logout', [LoginController::class, 'logout']);//前台登陆
        //代理->前台->车辆管理页面
        Route::post('/vehicle/list', [VehicleController::class, 'vehicleList']);//车辆列表
        Route::post('/delete/vehicle', [VehicleController::class, 'deleteVehicle']);//删除车辆
        Route::post('/binding/venue', [VehicleController::class, 'bindingVenue']);//车辆绑定场地
        Route::post('/down/venue', [VehicleController::class, 'downVenue']);//车辆绑定场地

        //代理->前台->我的场地
        Route::post('/venue/list', [VenueController::class, 'venueList']);//场地列表
        Route::post('/create/venue', [VenueController::class, 'createVenue']);//场地列表
        Route::post('/venue/detail', [VenueController::class, 'venueDetail']);//场地详情
        Route::post('/update/venue', [VenueController::class, 'updateVenue']);//场地详情

        Route::post('/upload/picture', [LoginController::class, 'uploadPicture']);//上传图片
    });
    Route::post('/test/udp', [LoginController::class, 'udp']);
});

