<?php

namespace App\Http\Service;


use App\Models\AgentVenue;
use App\Models\CuserAgent;
use App\Models\ReponseData;
use App\Models\Vehicle;

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
            'agent_id' => $request['agent_id'],
            'support_status' => $request['support_status'],
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2001,'代理id必传!');
        }
        $exists = CuserAgent::where('agent_id', $data['agent_id'])->exists();
        if(!$exists){
            return ReponseData::reponseFormat(2004,'未查询到该代理!');
        }
        if(!$data['support_status']){
            return ReponseData::reponseFormat(2003,'营业状态必传!');
        }
        if($data['support_status'] != 1){
            $list = AgentVenue::select('venue_name','')->where(['agent_id'=>$data['agent_id'],'support_status'=> 0])->get();
        }else{
            $list = AgentVenue::query()->where(['agent_id'=>$data['agent_id'],'support_status' != 0])->get();
        }

        return ReponseData::reponseFormatList(200,'获取成功',$list);

    }

    public function bindingVenue($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'vehicle_id' => $request['vehicle_id'],
            'venue_id' => $request['venue_id'],
        ];
        $venue = AgentVenue::where('id', $data['venue_id'])->first();
        if(!$venue){
            return ReponseData::reponseFormat(2004,'未查询到该场地!');
        }
        $vehicle = Vehicle::where('id', $data['vehicle_id'])->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆!');
        }
        $vehicle['venue_id'] = $data['venue_id'];
        $vehicle->save();
        return ReponseData::reponseFormat(200,'车辆绑定场地成功!');
    }

    public function deleteVehicle($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $vehicleId = $request['vehicle_id'] ?? null;

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
        $request = $this->setvice->decrypt($request['data']);
        $vehicleId = $request['vehicle_id'] ?? null;

        if(!$vehicleId){
            return ReponseData::reponseFormat(2004,'车辆id必传!');
        }
        $vehicle = Vehicle::where('id', $vehicleId)->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆或已被删除!');
        }
        $vehicle->update(['status'=>0]);

        return ReponseData::reponseFormat(200,'车辆下架成功!');

    }

    public function unbindVehicle($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'vehicle_id' => $request['vehicle_id'],
            'venue_id' => $request['venue_id'],
        ];
        $venue = AgentVenue::where('id', $data['venue_id'])->first();
        if(!$venue){
            return ReponseData::reponseFormat(2004,'未查询到该场地!');
        }
        $vehicle = Vehicle::where('id', $data['vehicle_id'])->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2002,'未查询到该车辆!');
        }
        $vehicle['venue_id'] = 0;
        $vehicle->save();

        return ReponseData::reponseFormat(200,'车辆解绑成功!');

    }
}
