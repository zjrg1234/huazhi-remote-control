<?php

namespace App\Http\Service;


use App\Models\AgentVenue;
use App\Models\Cuser;
use App\Models\CuserAgent;
use App\Models\CuserWallet;
use App\Models\CuserWalletLog;
use App\Models\DrivingRecord;
use App\Models\FeedBack;
use App\Models\ReponseData;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


class IndexService{
    protected $setvice;
    protected $labels = [
        1 => '遥控车',
        2 => '遥控船',
        3 => '工程车'
    ];
    public function __construct()
    {
        $this->setvice = new LoginService();
    }
    public function startupPage($request)
    {

    }

    public function index($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $uid = $request['uid'] ?? null;
        $type = $request['type'] ?? 0;
        $title =[ //暂时写死
            [
                'id'=>1,
                'name'=>'遥控车',
            ],
            [
                'id'=>2,
                'name'=>'遥控船',
            ],
            [
                'id'=>3,
                'name'=>'工程车',
            ],

        ];
        if(!$uid){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::where('id',$uid)->first();
        if(!$user){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $cuserAgentId = CuserAgent::where('superior_agent_id',$user['special_area'])->pluck('id');
        if($type != 0){
            $venueList = AgentVenue::select('id','venue_name','venue_image','vehicle_id')->whereIn('agent_id',$cuserAgentId)->where('vehicle_id',$type)->get();
        }else{
            $venueList = AgentVenue::select('id','venue_name','venue_image','vehicle_id')->whereIn('agent_id',$cuserAgentId)->get();

        }

        $redisKey = $user['special_area'].'_type_'.$type;
        $redis = Redis::get($redisKey);
        if(!$redis) {
            if($venueList->isEmpty()){
                $venueList = [];
            }else{
                foreach ($venueList as $value) {
                    $online = Vehicle::where('venue_id', $value['id'])->where('vehicle_state', 1)->count();
                    $driving = Vehicle::where('venue_id', $value['id'])->where('vehicle_state', 2)->count();
                    $queue = DrivingRecord::where('venue_id', $value['id'])->where('reservation_status', 1)->count();
                    $value['online'] = $online;
                    $value['driving'] = $driving;
                    $value['queue'] = $queue;
                    $value['venue_image'] = explode(',', $value['venue_image']);
                    $value['labels'] = $this->labels[$value['vehicle_id']];
                }
                Redis::setex($redisKey,5,json_encode($venueList));
            }
        }else{
            $venueList = json_decode($redis,true);
        }


        $respData = [
            'title'=>$title,
            'venueList' => $venueList,
        ];

        return ReponseData::reponseFormatList(200,'获取成功',$respData);
    }

    public function venueDetail($request){
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
            'venue_id' => $request['venue_id'] ?? null,
        ];

        if(!$data['uid']){
            return ReponseData::reponseFormat(2001,'用户id必传!');
        }

        if(!$data['venue_id']){
            return ReponseData::reponseFormat(2001,'场地id必传!');
        }
        $user = Cuser::where('id', $data['uid'])->exists();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }

        $list = AgentVenue::select('id','agent_id','venue_name','venue_image','venue_introduction','labels','start_time','end_time','venue_config')->where(['id'=>$data['venue_id']])->first();
        $online = Vehicle::where(['agent_id'=>$list['agent_id'],'venue_id'=>$data['venue_id'],'vehicle_state'=>1])->count(); //在线车辆
        $drive = Vehicle::where(['agent_id'=>$list['agent_id'],'venue_id'=>$data['venue_id'],'vehicle_state'=>2])->count(); //驾驶中车辆
        $people_number = DrivingRecord::where('venue_id', $data['venue_id'])->where('reservation_status', 1)->count();//表未建立 暂定
        $list['online'] = $online;
        $list['drive'] = $drive;
        $list['people_number'] = $people_number;
        $list['start_time'] = date('Y-m-d H:i:s',$list['start_time']);
        $list['end_time'] = date('Y-m-d H:i:s',$list['end_time']);
        $vehicle = Vehicle::select('id','vehicle_name','vehicle_introduction','top_speed','vehicle_image','vehicle_state','is_password','vehicle_battery','password')->where(['agent_id'=>$list['agent_id'],'venue_id'=>$list['id']])->get(); //车辆列表
        $list['venue_config'] = json_decode($list['venue_config'],true);
        $list['vehicle'] = $vehicle;

        return ReponseData::reponseFormatList(200,'成功',$list);
    }

    public function mine($request)
    {
//        $request = $this->setvice->decrypt($request['data']);

        $uid = $request['uid'] ?? null;
        if(!$uid){
            return ReponseData::reponseFormat(2001,'用户id必传!');
        }

        $user = Cuser::select('id','username')->where('id', $uid)->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $wallet = CuserWallet::select('balance','energy')->where('uid', $uid)->where('type',$user['special_area'])->first();
        $resp = [
            'username' => $user['username'],
            'wallet' => $wallet,
        ];

        return  ReponseData::reponseFormatList(200,'成功',$resp);
    }

    public function specialList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);

        $uid = $request['uid'] ?? null;
        Log::info('request_'.$uid);

        if(!$uid){
            return ReponseData::reponseFormat(2001,'用户id必传!');
        }

        $user = Cuser::select('id','username','head_shot')->where('id', $uid)->exists();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }

        $specialList = CuserAgent::select('id','agent_name')->where('superior_agent_id',0)->get();
        $sid = $specialList->pluck('id');
        $amountArray = CuserWallet::where('uid',$uid)->whereIn('type',$sid)->pluck('balance','type')->toArray();
        foreach ($specialList as $value) {
            $value['partitions_number'] = CuserAgent::where('superior_agent_id', $value['id'])->count();
            $cuserAgentId = CuserAgent::where('superior_agent_id',$value['id'])->pluck('id');
            $value['vehicles_number'] = Vehicle::whereIn('agent_id',$cuserAgentId)->count();
            $value['balance'] = $amountArray[$value['id']] ?? 0;
        }

        return  ReponseData::reponseFormatList(200,'成功',$specialList);
    }

    public function changeSpecial($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $uid = $request['uid'] ?? null;
        $specialId = $request['special_id'] ?? null;
        if(!$uid){
            return ReponseData::reponseFormat(2001,'用户id必传!');
        }

        $user = Cuser::select('id','username')->where('id', $uid)->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        if(!$specialId){
            return ReponseData::reponseFormat(2000,'专区id必传');
        }
        $specialList = CuserAgent::where('id', $specialId)->exists();
        if(!$specialList){
            return ReponseData::reponseFormat(2001,'未找到该专区');
        }

        $user->special_area = $specialId;
        $user->save();

        return ReponseData::reponseFormat(200,'变更成功');

    }

    public function reservationList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::select('id','username')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }

        $query = DrivingRecord::select('id','vehicle_name','vehicle_id','order_no','billing_method','venue_id','venue_name','appeal_status','reservation_status','order_time');
        $query->where('uid', $data['uid'])->where('special_area',$user['special_area']);

        $rows = $query->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);
        return ReponseData::reponseFormatList(200,'获取成功',$rows);

    }

    public function drivingRecord($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];

        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::select('id','username')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }

        $query = DrivingRecord::select('id','user_name','vehicle_name','vehicle_id','order_no','billing_method','venue_id','venue_name','payment_amount ','appeal_status','reservation_status','order_time','start_time','end_time');
        $query->where('uid', $data['uid'])->where('special_area',$user['special_area']);
        $rows = $query->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);
        return ReponseData::reponseFormatList(200,'获取成功',$rows);
    }

    public function walletList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::select('id','username')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $query = CuserWalletLog::select('id','type','time','amount','type_name');
        $query->where('uid', $data['uid'])->where('special_area',$user['special_area']);
        $rows = $query->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);
        foreach ($rows as $value) {
            $value['time'] = date('Y-m-d H:i:s',$value['time']);
        }

        return ReponseData::reponsePaginationFormat($rows);
    }

    public function wechatDeposit($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
        ];

        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::select('id','username')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $body = [
            'appid' => config('payment.wechat.app_id'),
            'mahid' => config('payment.wechat.mch_id'),
            'description' =>  '',//商品描述 预留
            'out_trade_no' => orderNo('WECHAT'),
            'time_expire' => date('Y-m-dT H:i:s',time()+config('payment.wechat.time_expire')),
            'amount' => $request['amount'],
            'notify_url'=>env('APP_URL').config('payment.wechat.notify_url'),
        ];
        $resp = $this->wechatGetPrepayId($body);
        if($resp['prepay_id']){
            $respBody = [
                'prepay_id' => $resp['prepay_id'],
                'mchid' => $body['mahid'],
                'appid' => $body['appid'],
            ];

            return ReponseData::reponseFormatList(200,'下单成功,请尽快支付!',$respBody);
        }else{
            return ReponseData::reponseFormatList(2000,'发起微信请求失败',null);

        }

    }

    public function wechatNotify($request)
    {

    }

    public function wechatGetPrepayId($body)
    {
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => '',
            'Accept' => 'application/json',
        ];
        $url = Config('payment.wechat.url'); //支付链接
        $resp = Http::withHeaders($header)->post($url, $body);
        $respData = json_decode($resp->body(),true);
        Log::info('wechatPayResp: '.json_encode($respData) . ' url：'.$url .'body：'.json_encode($body));
        if(empty($respData['prepay_id'])){
            return null;
        }
        return $respData['prepay_id'];
    }

    public function feedBack($request)
    {
//        $data = $this->decrypt($request['data']);
        $image = $data['image'] ?? null;
        $content = $data['content'] ?? null;
        $uid = $data['uid'] ?? null;
        $agent_id = $data['agent_id'] ?? null;

        if(!$content){
            return ReponseData::reponseFormat(2002,'意见必填');
        }
        $data = [];

        if($uid){
            $user = Cuser::where('id', $uid)->first();
            if(!$user){
                return ReponseData::reponseFormat(2000,'未找到该账号!');
            }
            $data['uid'] = $uid;
            $data['phone'] = $user['phone_number'];
            $data['user_name'] = $user['username'];

        }

        if($agent_id){
            $agent = CuserAgent::where('id',$agent_id)->first();
            if(!$agent){
                return ReponseData::reponseFormat(2000,'未找到该代理商账号!');
            }
            $data['agent_id'] = $agent_id;
            $data['phone'] = $agent['phone_number'];
            $data['user_name'] = $user['agent_name'];
        }
        $data['image'] = $image;
        $data['content'] = $content;
        $data['time'] = time();
        FeedBack::create($data);

        return ReponseData::reponseFormat(200,'提交成功');
    }

    public function deactivate($request)
    {
        $data = [
            'uid' => $request['uid'] ?? null,
        ];

        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::select('id','username')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $user->is_cancel = 1;

        return ReponseData::reponseFormat(200,'注销成功!');
    }


}
