<?php

namespace App\Http\Service;


use App\Models\AgentVenue;
use App\Models\AgentWallet;
use App\Models\AgentWalletLog;
use App\Models\AlarmVehcle;
use App\Models\Cuser;
use App\Models\CuserAgent;
use App\Models\CuserWallet;
use App\Models\CuserWalletLog;
use App\Models\DrivingRecord;
use App\Models\ReponseData;
use App\Models\Vehicle;
use App\Models\VehicleConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use function AlibabaCloud\Client\json;

class VehicleService
{
    protected $setvice;
    protected $OrdinaryVehicleType = [ //普通车辆
        100,
        200,
        300,
        400,
        500,
        600
    ];

    protected $ExcavatorVehicleType = [ //挖机
        700,
        800,
        900
    ];
    public function __construct()
    {
        $this->setvice = new LoginService();
    }
    public function vehicleList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'agent_id' => $request['agent_id'] ?? null,
//            'type' => $request['type'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2001,'代理id必传!');
        }
        $exists = CuserAgent::where('id', $data['agent_id'])->exists();
        if(!$exists){
            return ReponseData::reponseFormat(2004,'未查询到该代理!');
        }
//        if(!$data['type']){
//            return ReponseData::reponseFormat(2003,'状态必传!');
//        }
//        if($data['type'] != 1){
//            $list = Vehicle::select('id','vehicle_name','vehicle_image','vehicle_introduction','vehicle_battery','top_speed','status')->where(['agent_id'=>$data['agent_id'],'venue_id'=>0])->get();
//        }else{
            $list = Vehicle::select('id','venue_id','venue_name','vehicle_name','vehicle_type','vehicle_image','vehicle_introduction','vehicle_battery','top_speed','vehicle_state','receiver_id','vehicle_sorting','status','is_private','vehicle_billing_config')
                ->where('agent_id',$data['agent_id'])
                ->orderby('vehicle_sorting','asc')
                ->get();
//        }
        $venueIds = Vehicle::select('id','venue_id','venue_name','vehicle_name','vehicle_type','vehicle_image','vehicle_introduction','vehicle_battery','top_speed','vehicle_state','receiver_id','vehicle_sorting','status','is_private','vehicle_billing_config')
            ->where('agent_id',$data['agent_id'])
            ->pluck('venue_id');
        $venueNameData = AgentVenue::query()
            ->whereIn('id', $venueIds)
            ->pluck('venue_name', 'id')
            ->toArray();
        $respList = [
            'on_allocate'=>[],
            'off_allocate'=>[],
        ];
        foreach($list as $value){
            if($value['venue_id'] != 0){
                $value['venue_name'] = $venueNameData[$value['venue_id']] ?? '';
                $respList['on_allocate'][] = $value;
            }else{
                $value['venue_name'] = $venueNameData[$value['venue_id']] ?? '';
                $respList['off_allocate'][] = $value;
            }
            $value['vehicle_billing_config'] = json_decode($value['vehicle_billing_config'], true);
        }
        return ReponseData::reponseFormatList(200,'获取成功',$respList);

    }

    public function bindingVenue($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'vehicle_id' => $request['id'],
            'venue_id' => $request['venue_id'],
            'type' => $request['type'] ?? null,
        ];
        if(!$data['type']){
            return ReponseData::reponseFormat(2004,'type必传!');

        }
        $venue = AgentVenue::where('id', $data['venue_id'])->first();
//        if(!$venue){
//            return ReponseData::reponseFormat(2004,'未查询到该场地!');
//        }
        $vehicle = Vehicle::where('id', $data['vehicle_id'])->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆!');
        }
        if($data['type'] == 1){
            if($venue){
                $vehicle['venue_id'] = $data['venue_id'];
                $vehicle['venue_name'] = $venue['venue_name'];
            }else{
                return ReponseData::reponseFormat(2004,'未查询到该场地!');
            }
            $vehicle->save();
            $message = '车辆绑定场地成功!';
        }else{
            $vehicle['venue_id'] = 0;
            $vehicle['venue_name'] = '';
            $vehicle->save();
            $message = '车辆解绑场地成功!';
        }

        return ReponseData::reponseFormat(200,$message);
    }

    public function deleteVehicle($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $vehicleId = $request['id'] ?? null;
        $agent_id =  $request['agent_id'] ?? null;
        if(!$agent_id){
            return ReponseData::reponseFormat(2000,'代理id必传');
        }
        if(!$vehicleId){
            return ReponseData::reponseFormat(2004,'车辆id必传!');
        }
        $vehicle = Vehicle::where('id', $vehicleId)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆或已被删除!');
        }
        $vehicle->delete();
        VehicleConfig::where('vehicle_id', $vehicleId)->delete();


        return ReponseData::reponseFormat(200,'车辆删除成功!');

    }

    public function downVehicle($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $vehicleId = $request['id'] ?? null;
        $type = $request['type'] ?? null;
        if(!$vehicleId){
            return ReponseData::reponseFormat(2004,'车辆id必传!');
        }
        if(!$type){
            return ReponseData::reponseFormat(2004,'type必传!');
        }
        $vehicle = Vehicle::where('id', $vehicleId)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆或已被删除!');
        }
        if($type == 1){
            $alarm = AlarmVehcle::where('vehicle_id', $vehicleId)->where('status',0)->first();
            if($alarm){
                return ReponseData::reponseFormat(2000,'车辆有告警报修未处理,请先处理哦！');
            }
            $vehicle->update(['status'=>1]);
            $message = '车辆上架成功!';
        }else{
            $vehicle->update(['status'=>0]);
            $message = '车辆下架成功!';


        }

        return ReponseData::reponseFormat(200,$message);

    }

    public function unbindVehicle($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'vehicle_id' => $request['id'] ?? null,
//            'venue_id' => $request['venue_id'],
        ];
//        $venue = AgentVenue::where('id', $data['venue_id'])->first();
//        if(!$venue){
//            return ReponseData::reponseFormat(2004,'未查询到该场地!');
//        }
        if(!$data['vehicle_id']){
            return ReponseData::reponseFormat(2002,'id必传!');
        }
        $vehicle = Vehicle::where('id', $data['vehicle_id'])->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆!');
        }
        $vehicle['venue_id'] = 0;
        $vehicle->save();

        return ReponseData::reponseFormat(200,'车辆解绑成功!');

    }

//    public function downVenue($request)
//    {
//        $request = $this->setvice->decrypt($request['data']);
//        $data = [
//            'vehicle_id' => $request['vehicle_id'],
//        ];
//        $vehicle = Vehicle::where('id', $data['vehicle_id'])->first();
//        if(!$vehicle){
//            return ReponseData::reponseFormat(2002,'未查询到该车辆!');
//        }
//        $vehicle['status'] = 0;
//        $vehicle->save();
//        return ReponseData::reponseFormat(200,'车辆下架成功!');
//    }

    public function addVehicle($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'vehicle_image' => $request['vehicle_image'] ?? null,
            'battery' => $request['battery'] ?? null,
            'vehicle_name' => $request['vehicle_name'] ?? null,
            'vehicle_introduction' => $request['vehicle_introduction'] ?? '',
            'top_speed' => $request['top_speed'] ?? '',
            'front_camera' => $request['front_camera'] ?? null,
            'right_rear_camera' =>  $request['right_rear_camera'] ?? '',
            'transmitter_id' => $request['transmitter_id'] ?? '',
            'receiver_id' => $request['receiver_id'] ?? null,
            'vehicle_type' => $request['vehicle_type'] ?? null,
            'vehicle_sorting' => $request['vehicle_sorting'] ?? '1',
            'agent_id' => $request['agent_id'] ?? null,
            'forward_type' => $request['type'] ?? 1,
            'camera_type' => $request['camera_type'] ?? 1,
            'is_private' => $request['is_private'] ?? 0,
        ];
        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'代理id必传!');
        }
        if(!$data['vehicle_image']){
            return ReponseData::reponseFormat(2000,'车辆图片必填!');
        }
        if(!$data['battery']){
            return ReponseData::reponseFormat(2000,'车辆电池必填!');
        }
        if(!$data['vehicle_name']){
            return ReponseData::reponseFormat(2000,'车辆名称必填!');
        }
        if(!$data['front_camera']){
            return ReponseData::reponseFormat(2000,'前摄像头必填!');
        }
        if(!$data['vehicle_type']){
            return ReponseData::reponseFormat(2000,'车辆类型必填!');
        }
        $config = [
            'cumulative_time_card' => $request['cumulative_time_card'] ?? null, //累计驾驶时间卡
            'play_card' => $request['play_card'] ?? null, //畅玩卡
            'standard_card' => $request['standard_card'] ?? null, //标准卡
            'private_billing' => $request['private_billing'] ?? null, //私享计费
        ];
        $vehicleBillingConfig  = [
            'private_billing_open'=>$request['private_billing_open'] ?? 0,
            'cumulative_time_card_open'=>$data['cumulative_time_card_open'] ?? 0,
            'play_card_open'=>$data['play_card_open'] ?? 0,
            'standard_card_open'=>$data['standard_card_open'] ?? 0,
        ];

        if($data['is_private'] == 1){
            if($config['private_billing']){
                $vehicleBillingConfig['private_billing'] = $config['private_billing'];
            }else{
                $vehicleBillingConfig['private_billing'] = [];
            }
            $vehicleBillingConfig['cumulative_time_card'] = [];
            $vehicleBillingConfig['play_card'] = [];
            $vehicleBillingConfig['standard_card'] = [];
        }else{
            if($config['cumulative_time_card']){
                $vehicleBillingConfig['cumulative_time_card'] = $config['cumulative_time_card'];
            }else{
                $vehicleBillingConfig['cumulative_time_card'] = [];
            }

            if($config['play_card']){
                $vehicleBillingConfig['play_card'] = $config['play_card'];
            }else{
                $vehicleBillingConfig['play_card'] = [];
            }

            if($config['standard_card']){
                $vehicleBillingConfig['standard_card'] = $config['standard_card'];
            }else{
                $vehicleBillingConfig['standard_card'] = [];
            }
            $vehicleBillingConfig['private_billing'] = $config['private_billing'];

        }
        if($data['receiver_id'] != 0){
            $exists = Vehicle::where('receiver_id', $data['receiver_id'])->exists();
            if($exists){
                return ReponseData::reponseFormat(2000,'该车辆接收机已存在,请联系客服确认');
            }
        }
        $data['vehicle_battery'] = '';
        $vehicleConfig = [
            'rear_camera_type' => 0,
//            'operation_mode' => 0,
            'auto_easy_operation_value' => 1,
        ];
        $value = [
            'high_value'=>[
                'mini_value'=>1,
                'max_value'=>2000,
                'current_value'=>1500,
            ],
            'high_trim'=>[//微调
                'mini_value'=>1, //低
                'max_value'=>1000,//高
                'current_value'=>800 //  当前位置
            ],
            'high_rate'=>[//值固定死  高比例
                'mini_value'=>1, //低
                'max_value'=>100,//高
                'current_value'=>50 //  当前位置
            ],
            'low_value'=>[
                'mini_value'=>1,
                'max_value'=>2000,
                'current_value'=>500,
            ],
            'low_trim'=>[//微调
                'mini_value'=>1, //低
                'max_value'=>1000,//高
                'current_value'=>800 //  当前位置
            ],
            'low_rate'=>[//值固定死  低微比例
                'mini_value'=>1, //低
                'max_value'=>100,//高
                'current_value'=>50 //  当前位置
            ],
            'center_value'=>[
                'mini_value'=>1,
                'max_value'=>2000,
                'current_value'=>1000,
            ],
            'ch_multiple'=>[
                'close'=>1000,
                'open1'=>1300,
                'open2'=>1500,
                'mini_value'=>1,
                'max_value'=>2000
            ],
            'custom_channel_title'=>'',
            'channel_reverse'=>0,
            'channel_type'=>0,
            'easy_operation'=>0,
        ];

        if(in_array($data['vehicle_type'],$this->OrdinaryVehicleType)){
            $value['channel_type'] = 4;
        }

        $value2 = $value;
        $value2['channel_type'] = 2;
        $channelConfig = [
            'ch1'=>$value,
            'ch2'=>$value,
            'ch3'=>$value2,
            'ch4'=>$value2,
            'ch5'=>$value2,
            'ch6'=>$value2,
            'ch7'=>$value2,
            'ch8'=>$value2,
            'ch9'=>$value2,
            'ch10'=>$value2
        ];

        if(in_array($data['vehicle_type'],$this->ExcavatorVehicleType)){
            $value2['channel_type'] = 1;
            $value3 = $value;
            $value3['channel_type'] = 2;
            $channelConfig = [
                'ch1'=>$value,
                'ch2'=>$value,
                'ch3'=>$value,
                'ch4'=>$value,
                'ch5'=>$value,
                'ch6'=>$value,
                'ch7'=>$value2,
                'ch8'=>$value3,
                'ch9'=>$value3,
                'ch10'=>$value3,
            ];
        }

        $vehicleConfig['vehicle_config_detail'] = json_encode($channelConfig);

        $data['app_transmitter_id'] = mt_rand(40000000,49999999);
        $data['vehicle_billing_config'] = json_encode($vehicleBillingConfig);
        $vehicle = Vehicle::create($data);
        $vehicleConfig['vehicle_id'] = $vehicle['id'];
        $vehicleConfig['camera_type'] = $data['camera_type'];
        VehicleConfig::create($vehicleConfig);

        return ReponseData::reponseFormat(200,'车辆新增成功');
    }

    public function vehicleDetail($request){
//        $request = $this->setvice->decrypt($request['data']);
        $id = $request['id'] ?? null;

        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $vehicle = Vehicle::select('*')->where('id', $id)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2001,'未找到该车辆!');
        }
        $vehicleConfig = VehicleConfig::where('vehicle_id', $id)->first();
        $vehicleConfig['id'] = $vehicleConfig['vehicle_id'];
        if(!$vehicleConfig){
            return ReponseData::reponseFormat(2001,'未找到该车辆配置!');
        }
        $vehicleConfig['vehicle_name'] = $vehicle['vehicle_name'];
        $vehicleConfig['vehicle_type'] = $vehicle['vehicle_type'];
        $vehicleConfig['vehicle_image'] = $vehicle['vehicle_image'];
        $vehicleConfig['vehicle_battery'] = $vehicle['vehicle_battery'];
        $vehicleConfig['battery'] = intval($vehicle['battery']);
        $vehicleConfig['front_camera'] = $vehicle['front_camera'];
        $vehicleConfig['rear_camera'] = $vehicle['rear_camera'];
        $vehicleConfig['transmitter_id'] = $vehicle['transmitter_id'];
        $vehicleConfig['receiver_id'] = $vehicle['receiver_id'];
        $vehicleConfig['vehicle_sorting'] = $vehicle['vehicle_sorting'];
        $vehicleConfig['top_speed'] = $vehicle['top_speed'];
        $vehicleConfig['vehicle_introduction'] = $vehicle['vehicle_introduction'];
        $vehicleConfig['vehicle_config_detail'] = json_decode($vehicleConfig['vehicle_config_detail']);
        $vehicleConfig['password'] = $vehicle['password'];
        $vehicleConfig['forward_type'] = $vehicle['forward_type'];
        $vehicleConfig['app_transmitter_id'] = $vehicle['app_transmitter_id'];

        $vehicleConfig['battery_type'] = $vehicle['battery_type'];
        $vehicleConfig['right_rear_camera'] = $vehicle['right_rear_camera'];
        $vehicleConfig['vehicle_state'] = $vehicle['vehicle_state'];
        $vehicleConfig['vehicle_voltage'] = $vehicle['vehicle_voltage'];
        $vehicleConfig['ratio_type'] = $vehicle['ratio_type'];
        $vehicleConfig['video_clarity_value'] = $vehicle['video_clarity_value'];
        $vehicleConfig['right_rear_camera_open'] = $vehicle['right_rear_camera_open'];
        $vehicleConfig['content_url'] = env('CONTENT_URL','xhzzf.huazyk.cn') ;
        $vehicleConfig['content_url_port'] = env('CONTENT_URL_PORT','8899') ;
        $vehicleConfig['web_camera_host'] = env('WEB_CAMERA_HOST','') ;
        $vehicleConfig['web_camera_port'] = env('WEB_CAMERA_PORT','') ;
        $vehicleConfig['web_camera_user_name'] = env('WEB_CAMERA_NAME','') ;
        $vehicleConfig['web_camera_user_password'] = env('WEB_CAMERA_PASSWORD','') ;
        return ReponseData::reponseFormatList(200,'成功!',$vehicleConfig);
    }

    public function vehicleDetailSave($request)
    {
//        $request = $this->setvice->decrypt($request['data']);

        $id = $request['id'];
        $vehicleConfigDetail = $request['vehicle_config_detail'];
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }

        $vehicle = Vehicle::where('id', $id)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2000,'车辆未找到');
        }
        $video_clarity_value = $request['video_clarity_value'] ?? $vehicle['video_clarity_value'];

        $change_ui_control = $request['change_ui_control'] ?? $vehicle['change_ui_control'];
        $right_rear_camera_open = $request['right_rear_camera_open'] ?? $vehicle['right_rear_camera_open'];

        $vehicleConfig = VehicleConfig::where('vehicle_id', $id)->first();
        $getVehicleConfigDetail = json_decode($vehicleConfig['vehicle_config_detail'],true);
        if(!$vehicleConfig){
            return ReponseData::reponseFormat(2001,'未找到该车辆配置!');
        }
        if($vehicleConfigDetail) {
            foreach ($vehicleConfigDetail as &$v) {
                //开值
                $v['high_value']['mini_value'] = intval($v['high_value']['mini_value']);
                $v['high_value']['max_value'] = intval($v['high_value']['max_value']);
                $v['high_value']['current_value'] = intval($v['high_value']['current_value']);
                //高微调
                $v['high_trim']['mini_value'] = intval($v['high_trim']['mini_value']);
                $v['high_trim']['max_value'] = intval($v['high_trim']['max_value']);
                $v['high_trim']['current_value'] = intval($v['high_trim']['current_value']);

                //高比例
                $v['high_rate']['mini_value'] = intval($v['high_rate']['mini_value']);
                $v['high_rate']['max_value'] = intval($v['high_rate']['max_value']);
                $v['high_rate']['current_value'] = intval($v['high_rate']['current_value']);

                //关值
                $v['low_value']['mini_value'] = intval($v['low_value']['mini_value']);
                $v['low_value']['max_value'] = intval($v['low_value']['max_value']);
                $v['low_value']['current_value'] = intval($v['low_value']['current_value']);
                //低微调
                $v['low_trim']['mini_value'] = intval($v['low_trim']['mini_value']);
                $v['low_trim']['max_value'] = intval($v['low_trim']['max_value']);
                $v['low_trim']['current_value'] = intval($v['low_trim']['current_value']);
                //低比例
                $v['low_rate']['mini_value'] = intval($v['low_rate']['mini_value']);
                $v['low_rate']['max_value'] = intval($v['low_rate']['max_value']);
                $v['low_rate']['current_value'] = intval($v['low_rate']['current_value']);


                $v['center_value']['mini_value'] = intval($v['center_value']['mini_value']);
                $v['center_value']['max_value'] = intval($v['center_value']['max_value']);
                $v['center_value']['current_value'] = intval($v['center_value']['current_value']);

                //开关
                $v['ch_multiple']['mini_value'] = intval($v['ch_multiple']['mini_value']);
                $v['ch_multiple']['max_value'] = intval($v['ch_multiple']['max_value']);
                $v['ch_multiple']['close'] = intval($v['ch_multiple']['close']);
                $v['ch_multiple']['open1'] = intval($v['ch_multiple']['open1']);
                $v['ch_multiple']['open2'] = intval($v['ch_multiple']['open2']);
            }
        }

        if(isset($vehicleConfigDetail['ch1'])){
            $getVehicleConfigDetail['ch1'] = $vehicleConfigDetail['ch1'];

        }
        if (isset($vehicleConfigDetail['ch2'])){
            $getVehicleConfigDetail['ch2'] = $vehicleConfigDetail['ch2'];
        }
        if(isset($vehicleConfigDetail['ch3'])){
            $getVehicleConfigDetail['ch3'] = $vehicleConfigDetail['ch3'];
        }
        if(isset($vehicleConfigDetail['ch4'])){
            $getVehicleConfigDetail['ch4'] = $vehicleConfigDetail['ch4'];
        }
        if(isset($vehicleConfigDetail['ch5'])){
            $getVehicleConfigDetail['ch5'] = $vehicleConfigDetail['ch5'];
        }
        if(isset($vehicleConfigDetail['ch6'])){
            $getVehicleConfigDetail['ch6'] = $vehicleConfigDetail['ch6'];
        }
        if(isset($vehicleConfigDetail['ch7'])){
            $getVehicleConfigDetail['ch7'] = $vehicleConfigDetail['ch7'];
        }
        if(isset($vehicleConfigDetail['ch8'])){
            $getVehicleConfigDetail['ch8'] = $vehicleConfigDetail['ch8'];
        }
        if(isset($vehicleConfigDetail['ch9'])){
            $getVehicleConfigDetail['ch9'] = $vehicleConfigDetail['ch9'];
        }
        if(isset($vehicleConfigDetail['ch10'])){
            $getVehicleConfigDetail['ch10'] = $vehicleConfigDetail['ch10'];
        }
        $data = [
            'rear_camera_type' => $request['rear_camera_type'] ?? $vehicleConfig['rear_camera_type'],
//            'mixed_control' => $request['mixed_control'] ?? $vehicleConfig['mixed_control'],
            'vehicle_config_detail' => json_encode($getVehicleConfigDetail),
            'auto_easy_operation_value' =>  $request['auto_easy_operation_value'] ?? $vehicleConfig['auto_easy_operation_value'],

        ];
        $vehicleConfig->update($data);
        Vehicle::where('id', $id)->update([
            'change_ui_control'=>$change_ui_control,
            'video_clarity_value' => $video_clarity_value,
            'right_rear_camera_open' => $right_rear_camera_open
            ]);
        return ReponseData::reponseFormat(200,'更新成功');
    }

    public function updateVehicle($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $id = $request['id'];
        $password = $request['password'] ?? null;

        $data = [
            'vehicle_image' => $request['vehicle_image'] ?? null,
            'battery' => $request['battery'] ?? null,
            'vehicle_name' => $request['vehicle_name'] ?? null,
            'vehicle_introduction' => $request['vehicle_introduction'] ?? '',
            'top_speed' => $request['top_speed'] ?? '',
            'front_camera' => $request['front_camera'] ?? null,
            'right_rear_camera' =>  $request['right_rear_camera'] ?? '',
            'transmitter_id' => $request['transmitter_id'] ?? '',
            'receiver_id' => $request['receiver_id'] ?? null,
            'vehicle_type' => $request['vehicle_type'] ?? null,
            'vehicle_sorting' => $request['vehicle_sorting'] ?? '0',
            'forward_type' => $request['type'] ?? 1,
            'camera_type' => $request['camera_type'] ?? 1,

        ];

        $vehicle = Vehicle::where('id', $id)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2007,'未找到该车辆!');

        }
        if(!$data['vehicle_image']){
            return ReponseData::reponseFormat(2000,'车辆图片必填!');
        }
        if(!$data['battery']){
            return ReponseData::reponseFormat(2000,'车辆电池必填!');
        }
        if(!$data['vehicle_name']){
            return ReponseData::reponseFormat(2000,'车辆名称必填!');
        }
        if(!$data['front_camera']){
            return ReponseData::reponseFormat(2000,'前摄像头必填!');
        }
        if(!$data['vehicle_type']){
            return ReponseData::reponseFormat(2000,'车辆类型必填!');
        }

        $getVehicleBillingConfig = json_decode($vehicle['vehicle_billing_config'], true);
        $config = [
            'cumulative_time_card' => $request['cumulative_time_card'] ?? $getVehicleBillingConfig['cumulative_time_card'], //累计驾驶时间卡
            'play_card' => $request['play_card'] ?? $getVehicleBillingConfig['play_card'], //畅玩卡
            'standard_card' => $request['standard_card'] ?? $getVehicleBillingConfig['standard_card'], //标准卡
            'private_billing' => $request['private_billing'] ?? $getVehicleBillingConfig['private_billing'], //私享计费
        ];
        $vehicleBillingConfig  = [
            'private_billing_open'=>$request['private_billing_open'] ?? $getVehicleBillingConfig['private_billing_open'],
            'cumulative_time_card_open'=>$data['cumulative_time_card_open'] ?? $getVehicleBillingConfig['cumulative_time_card_open'],
            'play_card_open'=>$data['play_card_open'] ?? $getVehicleBillingConfig['play_card_open'],
            'standard_card_open'=>$data['standard_card_open'] ?? $getVehicleBillingConfig['standard_card_open'],
        ];
        $data['is_private'] = $request['is_private'] ?? $vehicle['is_private'];
        if($data['is_private'] == 1){
                $vehicleBillingConfig['private_billing'] = $config['private_billing'];
        }else{
            if($config['cumulative_time_card']){
                $vehicleBillingConfig['cumulative_time_card'] = $config['cumulative_time_card'];
            }

            if($config['play_card']){
                $vehicleBillingConfig['play_card'] = $config['play_card'];
            }

            if($config['standard_card']){
                $vehicleBillingConfig['standard_card'] = $config['standard_card'];
            }
            $vehicleBillingConfig['private_billing'] = $config['private_billing'];

        }

        if($vehicle['vehicle_type'] != $data['vehicle_type']){
            $value = [
                'high_value'=>[
                    'mini_value'=>1,
                    'max_value'=>2000,
                    'current_value'=>1500,
                ],
                'high_trim'=>[//微调
                    'mini_value'=>1, //低
                    'max_value'=>1000,//高
                    'current_value'=>800 //  当前位置
                ],
                'high_rate'=>[//值固定死  高比例
                    'mini_value'=>1, //低
                    'max_value'=>100,//高
                    'current_value'=>50 //  当前位置
                ],
                'low_value'=>[
                    'mini_value'=>1,
                    'max_value'=>2000,
                    'current_value'=>500,
                ],
                'low_trim'=>[//微调
                    'mini_value'=>1, //低
                    'max_value'=>1000,//高
                    'current_value'=>800 //  当前位置
                ],
                'low_rate'=>[//值固定死  低微比例
                    'mini_value'=>1, //低
                    'max_value'=>100,//高
                    'current_value'=>50 //  当前位置
                ],
                'center_value'=>[
                    'mini_value'=>1,
                    'max_value'=>2000,
                    'current_value'=>1000,
                ],
                'ch_multiple'=>[
                    'close'=>1000,
                    'open1'=>1300,
                    'open2'=>1500,
                    'mini_value'=>1,
                    'max_value'=>2000
                ],
                'custom_channel_title'=>'',
                'channel_reverse'=>0,
                'channel_type'=>0,
                'easy_operation'=>0,
            ];

            if(in_array($data['vehicle_type'],$this->OrdinaryVehicleType)){
                $value['channel_type'] = 4;
            }

            $value2 = $value;
            $value2['channel_type'] = 2;
            $channelConfig = [
                'ch1'=>$value,
                'ch2'=>$value,
                'ch3'=>$value2,
                'ch4'=>$value2,
                'ch5'=>$value2,
                'ch6'=>$value2,
                'ch7'=>$value2,
                'ch8'=>$value2,
                'ch9'=>$value2,
                'ch10'=>$value2
            ];

            if(in_array($data['vehicle_type'],$this->ExcavatorVehicleType)){
                $value2['channel_type'] = 1;
                $value3 = $value;
                $value3['channel_type'] = 2;
                $channelConfig = [
                    'ch1'=>$value,
                    'ch2'=>$value,
                    'ch3'=>$value,
                    'ch4'=>$value,
                    'ch5'=>$value,
                    'ch6'=>$value,
                    'ch7'=>$value2,
                    'ch8'=>$value3,
                    'ch9'=>$value3,
                    'ch10'=>$value3,
                ];
            }
            $vehicleConfig['vehicle_config_detail'] = json_encode($channelConfig);
            $vehicleConfig['camera_type'] = $data['camera_type'];
            VehicleConfig::where('vehicle_id',$id)->update($vehicleConfig);

        }
        $data['vehicle_billing_config'] = json_encode($vehicleBillingConfig);
        if($password){
            $data['password'] = $password;
        }else{
            $data['password'] = '';
        }
        $vehicle->update($data);
        return ReponseData::reponseFormat(200,'更新成功');
    }

    public function processingAlarm($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $id = $request['id'];
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传');
        }
        $alarmVehicle = AlarmVehcle::where('id', $id)->first();
        if(!$alarmVehicle){
            return ReponseData::reponseFormat(2000,'未找到该报警记录');
        }
        $alarmVehicle->status = 1;
        $alarmVehicle->save();
        $vehicle = Vehicle::where('id', $alarmVehicle['vehicle_id'])->first();
        if($vehicle){
            $vehicle->update(['status'=>1]);
        }
        return ReponseData::reponseFormat(200,'处理成功!');
    }
    public function processingAlarmList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
//        $status = $request['status'] ??  null;
        $agentId = $request['agent_id'] ?? null;
        if(!$agentId){
            return ReponseData::reponseFormat(2000,'代理id必传');
        }
//        if(!$status){
//            return ReponseData::reponseFormat(2000,'status必传');
//        }
        $list = AlarmVehcle::where('agent_id',$agentId)
            ->orderBy('id', 'desc') // id 倒序，最新在前
            ->limit(50)
            ->get();
        $uids = $list->pluck('uid');
        $userUserName = Cuser::query()
            ->whereIn('id', $uids)
            ->pluck('show_id', 'id')
            ->toArray();
        $respList = [
            'on_dispose'=>[],
            'off_dispose'=>[],
        ];
        foreach($list as $value){
            $vehicle = Vehicle::select('vehicle_image','vehicle_name')->where('id', $value->vehicle_id)->first();
            $value['venue_id'] = $value['war_id'];
            $value['venue_name'] = $value['war_zone_name'];
            $value['user_name'] = $userUserName[$value['uid']] ?? '';
            $value['user_name'] = strval($value['user_name']);

            if($value['status'] == 1){
                $respList['on_dispose'][] = $value;
            }else{
                $respList['off_dispose'][] = $value;
            }
            if($vehicle){
                $value['vehicle_image'] = $vehicle['vehicle_image'];
                $value['vehicle_name'] = $vehicle['vehicle_name'];
            }else{
                $value['vehicle_image'] = '';
                $value['vehicle_name'] = '';
            }

            unset($value['war_id']);
            unset($value['war_zone_name']);
        }
        return ReponseData::reponseFormatList(200,'获取成功!',$respList);
    }

    public function processingAlarmDelete($request)
    {
//        $request = $this->setvice->decrypt($request['data']);

        $id = $request['id'];
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传');
        }
        $alarmVehicle = AlarmVehcle::where('id', $id)->first();
        if(!$alarmVehicle){
            return ReponseData::reponseFormat(2000,'未找到该报警记录');
        }
        $alarmVehicle->delete();

        return ReponseData::reponseFormat(200,'删除成功!');
    }

    public function vehicleDetailReset($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $id = $request['id'];
        $type = $request['type'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        if(!$type){
            return ReponseData::reponseFormat(2000,'车辆重置配置必须传');
        }

        $vehicle = Vehicle::where('id', $id)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2007,'未找到该车辆!');

        }
        $vehicleConfig = VehicleConfig::where('vehicle_id',$id)->first();
        if(!$vehicleConfig){
            return ReponseData::reponseFormat(2000,'未找到该配置!');
        }
        $updateVehicleConfig = json_decode($vehicleConfig['vehicle_config_detail'],true);
        $default = [
            'open_value'=>[
                'mini_value'=>1,
                'max_value'=>2000,
                'current_value'=>1500,
            ],
            'close_value'=>[
                'mini_value'=>1,
                'max_value'=>2000,
                'current_value'=>500,
            ],
            'center_value'=>[
                'mini_value'=>1,
                'max_value'=>2000,
                'current_value'=>1000,
            ],
        ];
        if($type >= 3){
            $default['close_value']['current_value'] = 700;
        }

        switch ($type) {
            case 1:
                $updateVehicleConfig['ch1'] = $default;
                $returnData = $updateVehicleConfig['ch1'];
                 break;
            case 2:
                $updateVehicleConfig['ch2'] = $default;
                $returnData = $updateVehicleConfig['ch2'];
                break;

            case 3:
                $updateVehicleConfig['ch3'] = $default;
                $returnData = $updateVehicleConfig['ch3'];
                break;

            case 4:
                $updateVehicleConfig['ch4'] = $default;
                $returnData = $updateVehicleConfig['ch4'];
                break;

            case 5:
                $updateVehicleConfig['ch5'] = $default;
                $returnData = $updateVehicleConfig['ch5'];
                break;
            case 6:
                $updateVehicleConfig['ch6'] = $default;
                $returnData = $updateVehicleConfig['ch6'];
                break;
            case 7:
                $updateVehicleConfig['ch7'] = $default;
                $returnData = $updateVehicleConfig['ch7'];
                break;
            case 8:
                $updateVehicleConfig['ch8'] = $default;
                $returnData = $updateVehicleConfig['ch8'];
                break;

        }
        $vehicleConfig->update(['vehicle_config_detail'=>$updateVehicleConfig]);

        return ReponseData::reponseFormatList(200,'重置成功',$returnData);
    }

    public function processingAlarmCreate($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'id' => $request['id'] ?? null,
            'text' => $request['text'] ?? null,
            'order_no' => $request['order_no'] ?? null,
            'uid' => $request['uid'] ?? null,
        ];

        if(!$data['id']){
            return ReponseData::reponseFormat(2000,'id必传');
        }

        if(!$data['text']){
            return ReponseData::reponseFormat(2000,'内容必传');
        }

        $vehicle = Vehicle::where('id', $data['id'])->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2000,'未找到该车辆!');
        }

        $insertData = [
            'text' => $data['text'],
            'vehicle_id' => $data['id'],
            'vehicle_name' => $vehicle['vehicle_name'],
            'agent_id' => $vehicle['agent_id'],
            'war_id' => $vehicle['venue_id'],
            'war_zone_name' => $vehicle['venue_name'],
            'vehicle_image' => $vehicle['vehicle_image'],
            'order_no' => $data['order_no'],
            'uid' => $data['uid'],
            'status' => 0,
        ];

        $key = 'processing_'.$data['order_no'].'_'.$data['uid'];
        $ret = Redis::set($key, '1','ex','2','nx');
        if(!$ret){
            return ReponseData::reponseFormat(2000,'请勿重复点击哦');
        }
        $drivingRecord = DrivingRecord::where('order_no', $data['order_no'])->first();
        if(!$drivingRecord){
            return ReponseData::reponseFormat(2000,'未找到该订单!');
        }
        $user = Cuser::where('id',$drivingRecord['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2000,'未找到用户!');
        }
        $time = time();
        $startTime = $drivingRecord['start_time'];
        if($drivingRecord['reservation_status'] == 4 || $drivingRecord['reservation_status'] == 5){
            return  ReponseData::reponseFormat(2000,'订单已完成 不允许报修');
        }
        if($startTime > 0) {
            $surplusTime = $time - $startTime;
            if ($surplusTime <= 10) {
                Redis::del($drivingRecord['transmitter_id']); //解绑绑定车辆接收机、发射机id
                $drivingRecord->update([
                    'reservation_status' => 5,
                    'end_time' => $time,
                    'transmitter_id' => '0',//释放发射机id
                ]);
                $receiverJson = json_decode(Redis::get($drivingRecord['receiver_id'] . '_receiver'), true);
                $receiverJson['transmitter_id'] = '0';
                $receiverJson['transmitter_host_port'] = '';
                Redis::set($drivingRecord['receiver_id'] . '_receiver', json_encode($receiverJson));
                try {
                    if ($drivingRecord['payment_type'] == 1) {
                        WalletService::safeAdjust([
                            'uid' => $user->id,
                            'type' => CuserWalletLog::TypeReturn,
                            'type_name' => '报修退还',
                            'make_order_no' => $drivingRecord['order_no'],
                            'amount' => $drivingRecord['payment_amount'],
                            'venue' => $user->special_area_name,
                            'special_area' => $user->special_area,
                        ]);
                    }
                    if ($drivingRecord['payment_type'] == 2) {
                        WalletService::safeAdjustEnergy([
                            'uid' => $user->id,
                            'type' => CuserWalletLog::TypeReturn,
                            'type_name' => '报修退还',
                            'make_order_no' => $drivingRecord['order_no'],
                            'amount' => $drivingRecord['payment_amount'],
                            'venue' => $user->special_area_name,
                            'special_area' => $user->special_area,
                        ]);
                    }

                    $drivingRecord->update([
                        'reservation_status' => 4,
                        'end_time' => $time,
                        'transmitter_id' => '0',//释放发射机id
                        'payment_amount' => 0,
                    ]);

                    Redis::del('vehicle'.$vehicle['id']); //上报警告结束解锁车

                } catch (\Exception $e) {
                    return ReponseData::reponseFormat(2000, $e->getMessage());
                }
            } else {
                $billing_rules = json_decode($drivingRecord['billing_rules'], true);
                if (!$billing_rules) {
                    return ReponseData::reponseFormat(2000, '订单错误');
                }
                $rulesAmount = $billing_rules['battery']; //金额
                $rulesTime = $billing_rules['time'] * 60; //时间
                $startTime = $drivingRecord['start_time'];
                if ($drivingRecord['billing_method'] != 1) {
                    $count = ($time - $startTime) / $rulesTime + 1; //已进行次数
                    $shouldTime = $startTime + ($rulesTime * intval($count)); //当前阶段应该结束时间
                    $shouldTime2 = $shouldTime - $time; //阶段剩余多少时间
                    $shouldTime3 = $rulesTime - $shouldTime2; //阶段时间-剩余时间
                    $num = $shouldTime3 / $rulesTime;
                    $returnAmount = intval($rulesAmount * ($shouldTime2 / $rulesTime)); //返回金额 = 阶段金额*当前剩余时间/阶段时间
//                        }
                    if($num >= 0.3 && $num < 0.5){
                        $returnAmount = intval($rulesAmount * 0.5);
                    }
                    $totalAmount = ($billing_rules['battery'] * $count);
                    if($returnAmount == $drivingRecord['payment_amount'] && $count == 1){
                        $returnAmount = 0;
                    }
                    if($count > 1 &&  $totalAmount > $drivingRecord['payment_amount']){ //继续驾驶没成功
                        $returnAmount = 0;
                    }


                } else {

                    $count =  1; //按次固定一次
                    $shouldTime = $startTime + ($rulesTime * $count); //当前阶段应该结束时间
                    $shouldTime2 = $shouldTime - $time; //阶段剩余多少时间=未使用时间
                    $shouldTime3 = $rulesTime - $shouldTime2; //阶段时间-剩余时间=已使用时间
                    $num = $shouldTime3 / $rulesTime;
                    $p3 = $rulesAmount * 0.3;  // 中间30%
                    $p3_last = $rulesAmount * 0.3; // 最后30%
                    $returnAmount = 0;
                    $p1 = $rulesAmount * 0.2;

                    if($num < 0.9) { //超90直接不退钱

                        $returnAmount = intval($rulesAmount * ($shouldTime2 / $rulesTime)); //返回金额 = 阶段金额*当前剩余时间/阶段时间
                        if(($time - $startTime) <= 15){
                            $returnAmount = intval($rulesAmount) - 2; // 上车就扣2电池
                        }
                        if((intval($rulesAmount) - $returnAmount) <= 2){
                            $returnAmount = intval($rulesAmount) - 2; // 上车就扣2电池
                        }


                        // 只用了前40%区间，扣4成，剩余6成可退
//                        $returnAmount = intval($p3 + $p3_last);
//                        if($num <= 0.2){ // 如果时长不到20%
//                            $returnAmount = intval($p1 + $p3 + $p3_last); //总共扣20%的钱
//                        }
//                        if ($num >= 0.4 && $num < 0.7) {
//                            // 用完前40%+中间30%，共扣7成，最后3成可退
//                            $returnAmount = intval($p3_last);
//                        }
                    }

//                    $shouldTime = $startTime + $rulesTime; //当前阶段应该结束时间
//                    $shouldTime2 = $shouldTime - $time; //阶段剩余多少时间
//                    $shouldTime3 = $rulesTime - $shouldTime2; //阶段时间-剩余时间=已使用时间
//                    $num = $shouldTime3 / $rulesTime;
//                    $p3 = $rulesAmount * 0.3;  // 中间30%
//                    $p3_last = $rulesAmount * 0.3; // 最后30%
//                    $returnAmount = intval($rulesAmount * ($shouldTime2 / $rulesTime)); //返回金额 = 阶段金额*当前剩余时间/阶段时间
                }

                if ($drivingRecord['payment_type'] == 1) {
                    WalletService::safeAdjust([
                        'uid' => $user->id,
                        'type' => CuserWalletLog::TypeReturn,
                        'type_name' => '报修退还',
                        'make_order_no' =>$drivingRecord['order_no'],
                        'amount' => $returnAmount,
                        'venue' => $user->special_area_name,
                        'special_area' =>$user->special_area,
                    ]);
                    //代理商收入
                    $agentWallet = AgentWallet::getBalance($drivingRecord['agent_id']);
                    $balance = $agentWallet['balance'];
                    $updateQuery = AgentWallet::where(['agent_id' => $drivingRecord['agent_id']]);
                    $updateAmount = $drivingRecord['payment_amount'] - $returnAmount;

                    $affected = $updateQuery->update(['balance' => DB::raw("balance+{$updateAmount}")]);
                    if ($affected != 1) {
                        Log::info("结束驾驶收入金额： {$updateAmount}, 增加失败： {$agentWallet['balance']}");
                    }
                    $afterBalance = $balance + $drivingRecord['payment_amount'] - $returnAmount;
                    AgentWalletLog::create([
                        'agent_id' => $drivingRecord['agent_id'],
                        'type' => 1,
                        'type_name' => '收入',
                        'make_order_no'=>$drivingRecord['order_no'],
                        'amount' =>  $drivingRecord['payment_amount'] - $returnAmount,
                        'balance' => $afterBalance,
                        'time' => time(),
                    ]);
                }
                if ($drivingRecord['payment_type'] == 2) {
                    WalletService::safeAdjustEnergy([
                        'uid' => $user->id,
                        'type' => CuserWalletLog::TypeReturn,
                        'type_name' => '报修退还',
                        'make_order_no' => $drivingRecord['order_no'],
                        'amount' => $returnAmount,
                        'venue' => $user->special_area_name,
                        'special_area' => $user->special_area,
                    ]);
                }
                Redis::del($drivingRecord['transmitter_id']); //解绑绑定车辆接收机、发射机id
                $drivingRecord->update([
                    'reservation_status' => 4,
                    'end_time' => $time,
                    'transmitter_id' => '0',//释放发射机id
                    'payment_amount' => $drivingRecord['payment_amount'] - $returnAmount,
                ]);
                $receiverJson = json_decode(Redis::get($drivingRecord['receiver_id'] . '_receiver'), true);
                $receiverJson['transmitter_id'] = '0';
                $receiverJson['transmitter_host_port'] = '';
                Redis::set($drivingRecord['receiver_id'] . '_receiver', json_encode($receiverJson));

            }
        }else{
            $drivingRecord->update([
                'reservation_status' => 5,
                'transmitter_id' => '0',//释放发射机id
            ]);
        }
        $vehicle->update(['status'=>0,'vehicle_state' => 1]);
        Redis::del('vehicle'.$vehicle['id']); //上报警告结束解锁车

        AlarmVehcle::create($insertData);
        return  ReponseData::reponseFormat(200,'车辆报修提交成功!');
    }
    public function updateVehicleBattery($request)
    {
        $vehicle_id =  $request['vehicle_id'] ?? null;

        if(!$vehicle_id){
            return ReponseData::reponseFormat(2000,'车辆id必须传');
        }

        $vehicle = Vehicle::where('id',$vehicle_id)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2000,'未找到该车辆');
        }

        $vehicle_battery= $request['vehicle_battery'] ?? $vehicle['vehicle_battery'];
        $vehicle->update(['vehicle_battery'=>$vehicle_battery]);

        return ReponseData::reponseFormat(200,'更新成功');
    }
}
