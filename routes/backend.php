<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\WarZoneController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\AdminUserController;
use App\Http\Controllers\Backend\AdminAgentController;
use App\Http\Controllers\Backend\ReservationController;
use App\Http\Controllers\Home\LoginController;
use App\Http\Controllers\Backend\PlatformConfigController;




Route::post('/login', [AdminController::class,'login']); //后台登陆
//    Route::post('/register', [AdminController::class,'register']);
Route::post('/logout', [AdminController::class,'logout']); //退出
//    Route::get('/user', [AdminController::class,'getAuthUser']);
//Route::post('/reset/google2fa',[AdminController::class,'resetGoogle2fa']); //重制谷歌 暂时先不要


Route::group(['middleware' => 'AuthToken'], function () {
    Route::group(['prefix' => 'special'], function () { //专区
//        Route::post('/create',[WarZoneController::class,'create']);
        Route::post('/list',[WarZoneController::class,'list']);
    });
    Route::group(['prefix' => 'user'], function () { //用户管理
        Route::post('/details',[AdminUserController::class,'details']);
        Route::post('/list',[AdminUserController::class,'list']);//列表
        Route::post('/balance/list',[AdminUserController::class,'modifyBalance']);//列表
        Route::post('/energy/list',[AdminUserController::class,'modifyEnergy']);//列表
        Route::post('/change/password', [AdminUserController::class, 'changePassword']);//修改密码
        Route::post('/change/balance', [AdminUserController::class, 'changeBalance']);//修改密码
        Route::post('/change/energy', [AdminUserController::class, 'changeEnergy']);//修改密码
        Route::post('/frozen',[AdminUserController::class,'frozen']);//冻结
        Route::post('/delete',[AdminUserController::class,'delete']);//删除
        Route::post('/change/balance/log',[AdminUserController::class,'changeBalanceLog']);//余额修改记录
        Route::post('/change/energy/log',[AdminUserController::class,'changeEnergyLog']);//能量修改记录

    });

    Route::group(['prefix' => 'agent'], function () { //代理商管理
        Route::post('/detail',[AdminAgentController::class,'detail']);//详情
        Route::post('/list',[AdminAgentController::class,'list']);//列表
        Route::post('/create',[AdminAgentController::class,'create']);//新增
        Route::post('/update',[AdminAgentController::class,'update']);//更新
        Route::post('/vehicle/delete',[AdminAgentController::class,'delete']);//更新
        Route::post('/vehicle/list',[AdminAgentController::class,'vehicleList']);//车辆列表
        Route::post('/vehicle/detail',[AdminAgentController::class,'vehicleDetail']);//车辆详情
        Route::post('/wallet/log',[AdminAgentController::class,'walletLog']);//余额记录
        Route::post('/change/password',[AdminAgentController::class,'changePassword']);//更改密码
        Route::post('/frozen',[AdminAgentController::class,'Frozen']);//冻结
        Route::post('/take/down',[AdminAgentController::class,'takeDown']);//下架
        Route::post('/delete',[AdminAgentController::class,'agentDelete']);//删除
        Route::post('/update/yesterday/turnover',[AdminAgentController::class,'updateYesterdayTurnover']);//更新昨日营业额
        Route::post('/venue/list',[AdminAgentController::class,'venueList']);//场地列表
        Route::post('/venue/take/down',[AdminAgentController::class,'venueTakeDown']);//下架
        Route::post('/venue/delete',[AdminAgentController::class,'venueDelete']);//删除
        Route::post('/venue/change/sort',[AdminAgentController::class,'venueChangeSort']);//修改排序
        Route::post('/venue/vehicle/list',[AdminAgentController::class,'venueVehicleList']);



    });
    //专区
    Route::post('/special/list',[AdminUserController::class,'specialList']);
    Route::post('/type/list',[WarZoneController::class,'typeList']);
    Route::post('/agent/type/list',[WarZoneController::class,'agentTypeLTst']);


    //预约记录
    Route::post('/reservation/record',[ReservationController::class,'reservationRecord']);
    Route::post('/complaint/record',[ReservationController::class,'complaintRecord']);
    Route::post('/complaint/update',[ReservationController::class,'complaintUpdate']);
    Route::post('/refund/record',[ReservationController::class,'refundRecord']);

    //平台配置
    //常见问题列表
    Route::post('/common/problem/list',[PlatformConfigController::class,'commonProblemList']);
    Route::post('/common/problem/create',[PlatformConfigController::class,'commonProblemCreate']);
    Route::post('/common/problem/update',[PlatformConfigController::class,'commonProblemUpdate']);
    Route::post('/common/problem/delete',[PlatformConfigController::class,'commonProblemDelete']);
    //协议管理
    Route::post('/protocol/manage/list',[PlatformConfigController::class,'protocolManageList']);
//    Route::post('/protocol/manage/create',[PlatformConfigController::class,'protocolManageCreate']);
    Route::post('/protocol/manage/update',[PlatformConfigController::class,'protocolManageUpdate']);
    //意见反馈
    Route::post('/feed/back/list',[PlatformConfigController::class,'feedBackList']);
    Route::post('/feed/back/update',[PlatformConfigController::class,'feedBackUpdate']);



    Route::post('/upload/picture', [LoginController::class, 'uploadPicture']);//上传图片



});
