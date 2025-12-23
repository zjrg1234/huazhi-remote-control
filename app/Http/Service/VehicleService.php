<?php

namespace App\Http\Service;


use App\Models\AgentVenue;
use App\Models\AlarmVehcle;
use App\Models\CuserAgent;
use App\Models\ReponseData;
use App\Models\Vehicle;
use App\Models\VehicleConfig;

class VehicleService
{
    protected $setvice;
    public function __construct()
    {
        $this->setvice = new LoginService();
    }
    public function vehicleList($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'agent_id' => $request['agent_id'] ?? null,
            'type' => $request['type'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2001,'代理id必传!');
        }
        $exists = CuserAgent::where('id', $data['agent_id'])->exists();
        if(!$exists){
            return ReponseData::reponseFormat(2004,'未查询到该代理!');
        }
        if(!$data['type']){
            return ReponseData::reponseFormat(2003,'状态必传!');
        }
        if($data['type'] != 1){
            $list = Vehicle::select('id','vehicle_name','vehicle_image','vehicle_introduction','vehicle_battery','top_speed','status')->where(['agent_id'=>$data['agent_id'],'venue_id'=>0])->get();
        }else{
            $list = Vehicle::select('id','vehicle_name','vehicle_image','vehicle_introduction','vehicle_battery','top_speed','status')->where('agent_id',$data['agent_id'])->where('venue_id','!=' ,0)->get();
        }

        return ReponseData::reponseFormatList(200,'获取成功',$list);

    }

    public function bindingVenue($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'vehicle_id' => $request['id'],
            'venue_id' => $request['venue_id'],
            'type' => $request['type'] ?? null,
        ];
        if(!$data['type']){
            return ReponseData::reponseFormat(2004,'type必传!');

        }
        $venue = AgentVenue::where('id', $data['venue_id'])->first();
        if(!$venue){
            return ReponseData::reponseFormat(2004,'未查询到该场地!');
        }
        $vehicle = Vehicle::where('id', $data['vehicle_id'])->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆!');
        }
        if($data['type'] == 1){
            $vehicle['venue_id'] = $data['venue_id'];
            $vehicle->save();
            $message = '车辆绑定场地成功!';
        }else{
            $vehicle['vehicle_id'] = 0;
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
        $request = $this->setvice->decrypt($request['data']);
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
        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'vehicle_image' => $request['vehicle_image'] ?? null,
            'battery' => $request['battery'] ?? null,
            'vehicle_name' => $request['vehicle_name'] ?? null,
            'vehicle_introduction' => $request['vehicle_introduction'] ?? null,
            'top_speed' => $request['top_speed'] ?? null,
            'front_camera' => $request['front_camera'] ?? null,
            'rear_camera' =>  $request['rear_camera'] ?? null,
            'transmitter_id' => $request['transmitter_id'] ?? null,
            'receiver_id' => $request['receiver_id'] ?? null,
            'vehicle_type' => $request['vehicle_type'] ?? null,
            'vehicle_sorting' => $request['vehicle_sorting'] ?? null,
            'agent_id' => $request['agent_id'] ?? null,
            'forward_type' => $request['type'] ?? null,
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

        $vehicleConfig = [
            'turn_direction' => 1000,
//            'turn_left' => 1000,
//            'turn_right' => 1000,
            'oil_strength' => 1000,
            'turn_strength' => 1000,
            'oil_direction' => 1000,
            'video_definition' => 2,
            'rear_camera_type' => 0,
            'operation_mode' => 0,
        ];
        $channelConfig = [
            'ch1'=>[
                'open_value'=>500,
                'close_value'=>500,
                'median_value'=>500,
            ],
            'ch2'=>[
                'open_value'=>500,
                'close_value'=>500,
                'median_value'=>500,
            ],
            'ch3'=>[
                'open_value'=>500,
                'close_value'=>500,
                'median_value'=>500,
            ],
            'ch4'=>[
                'open_value'=>500,
                'close_value'=>500,
                'median_value'=>500,
            ],
            'ch5'=>[
                'open_value'=>500,
                'close_value'=>500,
                'median_value'=>500,
            ],
            'ch6'=>[
                'open_value'=>500,
                'close_value'=>500,
                'median_value'=>500,
            ],
            'ch7'=>[
                'open_value'=>500,
                'close_value'=>500,
                'median_value'=>500,
            ],
        ];
        $vehicleConfig['vehicle_config_detail'] = json_encode($channelConfig);
        $exists = Vehicle::where('receiver_id', $data['receiver_id'])->first();
        if($exists){
            return ReponseData::reponseFormat(2000,'车辆重复!');
        }
        $vehicle = Vehicle::create($data);
        $vehicleConfig['vehicle_id'] = $vehicle['id'];
        VehicleConfig::create($vehicleConfig);

        return ReponseData::reponseFormat(200,'车辆新增成功');
    }

    public function vehicleDetail($request){
        $request = $this->setvice->decrypt($request['data']);
        $id = $request['id'] ?? null;

        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $vehicle = Vehicle::where('id', $id)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2001,'未找到该车辆!');
        }
        $vehicleConfig = VehicleConfig::where('vehicle_id', $id)->first();
        if(!$vehicleConfig){
            return ReponseData::reponseFormat(2001,'未找到该车辆配置!');
        }
        $vehicleConfig['vehicle_name'] = $vehicle['vehicle_name'];
        $vehicleConfig['vehicle_battery'] = $vehicle['vehicle_battery'];
        $vehicleConfig['top_speed'] = $vehicle['top_speed'];
        $vehicleConfig['vehicle_introduction'] = $vehicle['vehicle_introduction'];
        $vehicleConfig['vehicle_config_detail'] = json_decode($vehicleConfig['vehicle_config_detail']);


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

        $vehicleConfig = VehicleConfig::where('vehicle_id', $id)->first();
        if(!$vehicleConfig){
            return ReponseData::reponseFormat(2001,'未找到该车辆配置!');
        }
        $data = [
            'turn_direction' => $request['turn_direction'] ?? $vehicleConfig['turn_direction'],
            'turn_left' => $request['turn_left'] ?? $vehicleConfig['turn_left'],
            'turn_right' => $request['turn_right'] ?? $vehicleConfig['turn_right'],
            'oil_strength' => $request['oil_strength'] ?? $vehicleConfig['oil_strength'],
            'turn_strength' => $request['turn_strength'] ?? $vehicleConfig['turn_strength'],
            'oil_direction' => $request['oil_direction'] ?? $vehicleConfig['oil_direction'],
            'video_definition' => $request['video_definition'] ?? $vehicleConfig['video_definition'],
            'rear_camera_type' => $request['rear_camera_type'] ?? $vehicleConfig['rear_camera_type'],
            'operation_mode' => $request['operation_mode'] ?? $vehicleConfig['operation_mode'],
            'vehicle_config_detail' => json_encode($vehicleConfigDetail),
        ];
        $vehicleConfig->update($data);

        return ReponseData::reponseFormat(200,'更新成功');
    }

    public function updateVehicle($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $id = $request['id'];

        $data = [
            'vehicle_image' => $request['vehicle_image'] ?? null,
            'battery' => $request['battery'] ?? null,
            'vehicle_name' => $request['vehicle_name'] ?? null,
            'vehicle_introduction' => $request['vehicle_introduction'] ?? null,
            'top_speed' => $request['top_speed'] ?? null,
            'front_camera' => $request['front_camera'] ?? null,
            'rear_camera' =>  $request['rear_camera'] ?? null,
            'transmitter_id' => $request['transmitter_id'] ?? null,
            'receiver_id' => $request['receiver_id'] ?? null,
            'vehicle_type' => $request['vehicle_type'] ?? null,
            'vehicle_sorting' => $request['vehicle_sorting'] ?? null,
            'forward_type' => $request['type'] ?? null,
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
        $vehicle->update($data);
        return ReponseData::reponseFormat(200,'更新成功');
    }

    public function processingAlarm($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $id = $request['id'];
        $vehicle = AlarmVehcle::where('id', $id)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2000,'未找到该报警记录');
        }
        $vehicle->status = 1;
        $vehicle->save();

        return ReponseData::reponseFormat(200,'处理成功!');
    }
    public function processingAlarmList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $status = $request['status'] ??  null;
        $agentId = $request['agent_id'] ?? null;
        if(!$agentId){
            return ReponseData::reponseFormat(2000,'代理id必传');
        }
        if(!$status){
            return ReponseData::reponseFormat(2000,'status必传');
        }
        $list = AlarmVehcle::where('status',$status)->where('agent_id',$agentId)->get();

        return ReponseData::reponseFormatList(200,'获取成功!',$list);
    }
}
