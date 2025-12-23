<?php

namespace App\Http\Service;


use App\Models\AgentVenue;
use App\Models\AgentWallet;
use App\Models\AgentWalletLog;
use App\Models\CuserAgent;
use App\Models\CuserWallet;
use App\Models\DrivingRecord;
use App\Models\ReponseData;
use App\Models\Vehicle;
use App\Models\VehicleConfig;
use Illuminate\Support\Facades\Hash;
use MongoDB\Driver\ReadPreference;

class AgentService
{

    //代理前台用户余额
    public function agentMine($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $agent_id = $request['agent_id'] ?? null;
        if(!$agent_id){
            return ReponseData::reponseFormat(2000,'agent_id必传!');
        }
        $user = CuserAgent::where('id', $agent_id)->first();
        if(!$user){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $balance  = CUserWallet::getBalance($user['id']);

    }

    public function agentDrivingRecord($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $agent_id = $request['agent_id'] ?? null;
        $size = $request['size'] ?? 10;
        $page = $request['page'] ?? 1;

        if(!$agent_id){
            return ReponseData::reponseFormat(2000,'agent_id必传!');
        }
        $user = CuserAgent::where('id', $agent_id)->first();
        if(!$user){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $query = DrivingRecord::select('agent_id','user_name','order_no','venue_name','vehicle_name','billing_method','order_time','start_time','end_time','payment_amount');


        $query = $query->where('agent_id', $agent_id);
        $rows = $query->orderBy("id", 'asc')->paginate($size, ['*'], 'page', $page);
        return ReponseData::reponsePaginationFormat($rows);
    }

    public function agentDriving($request)
    {
        $request = $this->setvice->decrypt($request['data']);
        $agent_id = $request['agent_id'] ?? null;
        $agentId = $request['agent_id'] ?? null;


        if(!$agent_id){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        if(!$agentId){
            return ReponseData::reponseFormat(2000,'代理Id必传!');
        }
        $user = CuserAgent::where('id', $agent_id)->first();
        if(!$user){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $list = DrivingRecord::select('agent_id','user_name','order_no','venue_name','vehicle_name','billing_method','order_time','start_time','end_time','payment_amount')
            ->where('agent_id', $agentId)
            ->where('reservation_status',3)
            ->get();



        return ReponseData::reponseFormatList(200,'获取成功',$list);
    }

    //后台管理
    public function list($request)
    {
        $query_params = [
            'page'                 => $request['page'] ?? 1,
            'size'                 => $request['size'] ?? 10,
            'phone_number'         => $request['phone_number'] ?? null,
            'agent_name'            => $request['agent_name'] ?? null,
            'is_cancel'            => $request['is_cancel'] ?? null,
        ];
        $query = CuserAgent::select('id',
            'agent_name',
            'level',
            'phone_number',
            'venue_quantity',
            'create_site_quantity',
            'is_support',
            'head_shot',
            'provinces',
            'city',
            'register_time',
            'review_status',
            'support_status',
            'is_cancel',
            'sorting',
            'yesterday_turnover',
            'superior_agent_id',
            'withdrawal_amount',
            'first_handling_fee',
            'company_handling_fee');
        if(isset($query_params['phone_number'])){
            $query->where('phone_number',$query_params['phone_number']);
        }

        if(isset($query_params['agent_name'])){
            $query->where('agent_name',$query_params['agent_name']);
        }

        if(isset($query_params['is_cancel'])){
            $query->where('is_cancel',$query_params['is_cancel']);
        }

        $rows = $query->orderBy("id", 'asc')->paginate($query_params['size'], ['*'], 'page', $query_params['page']);
        $agent_ids = array_column($rows->items(), 'id');
        $userBalanceWallet = AgentWallet::query()
            ->whereIn('agent_id', $agent_ids)
            ->pluck('balance', 'agent_id')
            ->toArray();


        foreach ($rows as $value){
            $value['balance'] = $userBalanceWallet[$value['id'] ?? '0'];//余额
            $value['first_handling_fee'] = $value['first_handling_fee'] . '%';//一级代理商抽成
            $value['company_handling_fee'] = $value['company_handling_fee'] . '%';//公司抽成

        }
        return ReponseData::reponsePaginationFormat($rows);
    }

    public function create($request)
    {
        $data = [
            'level' => $request['level'] ?? null, //代理商等级
            'superior_agent_id' => $request['superior_agent_id'] ?? null, //上级代理商id
            'phone_number'=>$request['phone_number'] ?? null, //手机号
            'head_shot' => $request['head_shot'], //头像
            'create_site_quantity' => $request['create_site_quantity'] ?? null, //可创建场地数量
            'sorting'=>$request['sorting'],//排序号
            'is_support' => $request['is_support'] ?? null, //是否自营
            'agent_name' => $request['agent_name'],
            'first_handling_fee'=>$request['first_handling_fee'] ?? 0,
            'company_handling_fee'=>$request['company_handling_fee'] ?? 0,
        ];
        if(!$data['level']){
            return ReponseData::reponseFormat(2000,'代理商等级必填');
        }

        if(!isset($data['superior_agent_id'])){
            return ReponseData::reponseFormat(2000,'上级代理商必填');
        }

        if(!$data['phone_number']){
            return ReponseData::reponseFormat(2000,'手机号必填');
        }

        if(!$data['create_site_quantity']){
            return ReponseData::reponseFormat(2000,'可创建场地总数必填');
        }

        if(!$data['is_support']){
            return ReponseData::reponseFormat(2000,'是否自营必填');
        }
        $data['password'] = Hash::make('123456');
        $cuserAgent = CuserAgent::create($data);
        AgentWallet::getBalance($cuserAgent['id']);

        return ReponseData::reponseFormat(200,'新增成功');

    }

    public function update($request)
    {
        $id = $request['id'];
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必填');
        }
        $list = CuserAgent::where('id', $id)->first();
        if(!$list){
            return ReponseData::reponseFormat(2000,'未找到该数据');
        }
        $data = [
            'head_shot' => $request['head_shot'] ?? $list['head_shot'], //头像
            'create_site_quantity' => $request['create_site_quantity'] ?? null, //可创建场地数量
            'sorting'=>$request['sorting'] ?? $list['sorting'], ///排序号
            'is_support' => $request['is_support'] ?? null, //是否自营
            'agent_name' => $request['agent_name'] ?? $list['agent_name'],
            'first_handling_fee'=>$request['first_handling_fee'] ?? $list['first_handling_fee'],
            'company_handling_fee'=>$request['company_handling_fee'] ?? $list['company_handling_fee'],
        ];


        if(!$data['create_site_quantity']){
            return ReponseData::reponseFormat(2000,'可创建场地总数必填');
        }

        if(!$data['is_support']){
            return ReponseData::reponseFormat(2000,'是否自营必填');
        }
        $data['password'] = Hash::make('123456');
        $list->update($data);

        return ReponseData::reponseFormat(200,'更新成功');

    }

    public function detail($request)
    {
        $id = $request['id'] ?? null;

        if(!$id){
            return ReponseData::reponseFormat(2001,'id必传!');
        }
        $cuserAgent = CuserAgent::select('*')->where('id', $id)->first();
        if(!$cuserAgent){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }

        return ReponseData::reponseFormatList(200,'成功',$cuserAgent);
    }

    public function vehicleList($request)
    {
        $data = [
            'agent_id' => $request['id'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2001,'id必传!');
        }
        $exists = CuserAgent::where('id', $data['agent_id'])->exists();
        if(!$exists){
            return ReponseData::reponseFormat(2004,'未查询到该代理!');
        }

        $list = Vehicle::select('id','vehicle_name','vehicle_image','vehicle_state','vehicle_battery','top_speed','status')->where(['agent_id'=>$data['agent_id']])->get();

        return ReponseData::reponseFormatList(200,'成功',$list);
    }

    public function vehicleDetail($request){
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
        $vehicleConfig['transmitter_id'] = $vehicle['transmitter_id'];
        $vehicleConfig['receiver_id'] = $vehicle['receiver_id'];
        if($vehicle['venue_id'] != 0){
            $vehicleConfig['binding_status'] = '已绑定';
        }else{
            $vehicleConfig['binding_status'] = '未绑定';
        }
        $vehicleConfig['vehicle_state'] = $vehicle['vehicle_state'];
        $vehicleConfig['front_camera'] = $vehicle['front_camera'];
        $vehicleConfig['rear_camera'] = $vehicle['rear_camera'];
        $vehicleConfig['vehicle_config_detail'] = json_decode($vehicleConfig['vehicle_config_detail']);


        return ReponseData::reponseFormatList(200,'成功!',$vehicleConfig);
    }

    public function walletLog($request)
    {
        $id = $request['id'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $cuserAgent = CuserAgent::select('*')->where('id', $id)->first();
        if(!$cuserAgent){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }

        $query_params = [
            'page'                 => $request['page'] ?? 1,
            'size'                 => $request['size'] ?? 10,
            'type'         => $request['type'] ?? null,
            'start_time'            => $request['start_time'] ?? null,
            'end_time'            => $request['end_time'] ?? null,
        ];
        $query = AgentWalletLog::select('*');
        if($query_params['type']){
            $query->where('type', $query_params['type']);
        }
        if($query_params['start_time'] && $query_params['end_time']){
            $query->whereBetween('time', [$query_params['start_time'], $query_params['end_time']]);
        }
        $rows = $query->orderBy("id", 'asc')->paginate($query_params['size'], ['*'], 'page', $query_params['page']);

        return  ReponseData::reponsePaginationFormat($rows);

    }

    public function changePassword($request)
    {
        $id = $request['id'] ?? null;
        $password = $request['password'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $cuserAgent = CuserAgent::select('*')->where('id', $id)->first();
        if(!$cuserAgent){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $phone = $request['phone_number'] ?? $cuserAgent['phone_number'];
        $cuserAgent['password'] = Hash::make($password);
        $cuserAgent['phone_number'] = $phone;
        $cuserAgent->save();

        return ReponseData::reponseFormat(200,'修改成功');
    }

    public function Frozen($request)
    {
        $id = $request['id'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $cuserAgent = CuserAgent::select('*')->where('id', $id)->first();
        if(!$cuserAgent){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $cuserAgent['is_frozen'] = 1;
        return ReponseData::reponseFormat(200,'冻结成功');
    }

    public function takeDown($request)
    {
        $id = $request['id'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $cuserAgent = CuserAgent::select('*')->where('id', $id)->first();
        if(!$cuserAgent){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $cuserAgent['support_status'] = 0;
        return ReponseData::reponseFormat(200,'下架成功');
    }

    public function updateYesterdayTurnover($request)
    {
        $id = $request['id'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $cuserAgent = CuserAgent::select('*')->where('id', $id)->first();
        if(!$cuserAgent){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $start_time = strtotime(date('Y-m-d', strtotime('-1 day')).' 00:00:00');
        $end_time = strtotime(date('Y-m-d', strtotime('-1 day')).' 23:59:59');

        $amount = AgentWalletLog::whereBetween('time', [$start_time, $end_time])->sum('amount');
        $cuserAgent['yesterday_turnover'] = $amount;
        $cuserAgent->save();

        return ReponseData::reponseFormat(200,'更新成功');
    }
}
