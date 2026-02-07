<?php

namespace App\Http\Service;


use App\Models\AgentVenue;
use App\Models\AgentWallet;
use App\Models\AgentWalletLog;
use App\Models\CommonProblem;
use App\Models\ComplainRecord;
use App\Models\Cuser;
use App\Models\CuserAgent;
use App\Models\CuserEnergyLog;
use App\Models\CuserWallet;
use App\Models\CuserWalletLog;
use App\Models\DepositActivity;
use App\Models\DepositLog;
use App\Models\DrivingRecord;
use App\Models\FeedBack;
use App\Models\ProtocolManage;
use App\Models\ReponseData;
use App\Models\Vehicle;
use App\Http\Service\AlipayNativeService;
use Illuminate\Support\Facades\DB;
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
        $url = '';
        $resp = [
            'url' =>$url
        ];
        return ReponseData::reponseFormatList(200,'成功',$resp);
    }

    public function index($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $uid = $request['uid'] ?? null;
        $type = $request['type'] ?? 0;

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
                    $value['labels'] = $this->labels[$value['vehicle_id']] ?? '';
                }
                Redis::setex($redisKey,5,json_encode($venueList));
            }
        }else{
            $venueList = json_decode($redis,true);
        }


        $respData = [
            'banner' => '',
            'venueList' => $venueList,
        ];

        return ReponseData::reponseFormatList(200,'获取成功',$respData);
    }

    public function getTitle()
    {
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
        return  ReponseData::reponseFormatList(200,'成功',$title);
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
        if(!$list){
            return  ReponseData::reponseFormat(2000,'场地不存在');
        }
        $online = Vehicle::where(['agent_id'=>$list['agent_id'],'venue_id'=>$data['venue_id'],'vehicle_state'=>1])->count(); //在线车辆
        $drive = Vehicle::where(['agent_id'=>$list['agent_id'],'venue_id'=>$data['venue_id'],'vehicle_state'=>2])->count(); //驾驶中车辆
        $people_number = DrivingRecord::where('venue_id', $data['venue_id'])->where('reservation_status', 1)->count();//表未建立 暂定
        $list['online'] = $online;
        $list['drive'] = $drive;
        $list['queue'] = $people_number;
        $list['start_time'] = date('H:i',$list['start_time']);
        $list['end_time'] = date('H:i',$list['end_time']);
        $vehicle = Vehicle::select('id','vehicle_name','vehicle_introduction','top_speed','vehicle_image','vehicle_state','is_password','vehicle_battery','password','app_transmitter_id')->where(['agent_id'=>$list['agent_id'],'venue_id'=>$list['id']])->get(); //车辆列表
        foreach($vehicle as $value){
            $vehicle_people_number = DrivingRecord::where('vehicle_id', $value['id'])->where('reservation_status', 1)->count();//表未建立 暂定
            $value['vehicle_queue'] = $vehicle_people_number ?? 0;
        }
        $list['venue_config'] = json_decode($list['venue_config'],true);
        $list['venue_image'] = explode(',',$list['venue_image']);
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

        $user = Cuser::select('id','username','special_area','head_shot')->where('id', $uid)->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $wallet = CuserWallet::getBalance($uid,$user['special_area']);
        $resp = [
            'id'=>$user['id'],
            'head_shot'=>$user['head_shot'],
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

        $specialList = CuserAgent::select('id','agent_name','head_shot')->where('superior_agent_id',0)->get();
        $sid = $specialList->pluck('id');
        $amountArray = CuserWallet::where('uid',$uid)->whereIn('type',$sid)->pluck('balance','type')->toArray();
        foreach ($specialList as $value) {
            $value['partitions_number'] = CuserAgent::where('superior_agent_id', $value['id'])->count();
            $cuserAgentId = CuserAgent::where('superior_agent_id',$value['id'])->pluck('id');
            $value['vehicles_number'] = Vehicle::whereIn('agent_id',$cuserAgentId)->count();
            $value['balance'] = $amountArray[$value['id']] ?? 0;
            $value['image'] = $value['head_shot'] ?? '';
            unset($value['head_shot']);
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
        $user = Cuser::select('id','username','special_area')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $query = DrivingRecord::select('*');
        $query->where('uid', $data['uid'])->where('special_area',$user['special_area']);

        $rows = $query->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);
        foreach ($rows as $row) {
            if($row['reservation_status'] != 0){
                $row['is_reservation'] = 0;
            }else{
                $row['is_reservation'] = 1;
            }
            $row['billing_rules'] = json_decode($row['billing_rules'],true);
            $row['app_transmitter_id'] = $row['transmitter_id'];
            unset($row['transmitter_id']);
        }
        return ReponseData::reponsePaginationFormat($rows);

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
        $user = Cuser::select('id','username','special_area')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }

        $query = DrivingRecord::select('id','user_name','vehicle_name','vehicle_id','order_no','billing_method','venue_id','venue_name','payment_amount','appeal_status','reservation_status','order_time','start_time','end_time','payment_type','head_shot');
        $query->where('uid', $data['uid'])->where('special_area',$user['special_area']);
        $rows = $query->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);
        return ReponseData::reponsePaginationFormat($rows);
    }

    public function walletList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
            'type' => $request['type'] ?? null,
        ];
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::select('id','username','special_area')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $query = CuserWalletLog::select('id','type','time','amount','type_name');
        $query->where('uid', $data['uid'])->where('special_area',$user['special_area']);
        if($data['type']){
            $query->where('type',$data['type']);
        }
        $rows = $query->orderBy("time", 'desc')->paginate($data['size'], ['*'], 'page', $data['page']);
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
            'amount' => $request['amount'] ?? null,
        ];

        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户哦!');
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

    public function alipayDeposit($request)
    {
        $data = [
            'uid' => $request['uid'] ?? null,
            'amount' => $request['amount'] ?? null,
            'activity_id' => $request['activity_id'] ?? null,
        ];
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传');
        }
        $user = Cuser::where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2000,'未找到该用户哦!');
        }
        $depositOrder = [
            'uid' => $request['uid'],
            'amount' => $request['amount'],
            'user_name' => $user['username'],
            'special_area'=> $user['special_area'],
            'special_area_name'=> $user['special_area_name'],
            'phone_number' => $user['phone_number'],
            'time' => time(),
            'type' => 0,
            'pay_type' => 2,//1微信，支付宝，3银行卡，4momo
        ];

        DepositLog::create($depositOrder);

        try {
            // 2. 初始化原生支付宝工具类
            $alipay = new AlipayNativeService();

            // 3. 生成APP支付的orderStr
            $orderStr = $alipay->createAppOrder($data);

            // 4. 可选：记录订单到数据库（示例）
            // \App\Models\Order::updateOrCreate(
            //     ['out_trade_no' => $validated['out_trade_no']],
            //     [
            //         'total_amount' => $validated['total_amount'],
            //         'subject' => $validated['subject'],
            //         'status' => 'unpaid',
            //         'created_at' => now(),
            //     ]
            // );

            // 5. 返回给APP端
            return response()->json([
                'code' => 200,
                'msg' => '生成订单成功',
                'data' => ['order_str' => $orderStr]
            ]);
        } catch (\Exception $e) {
            Log::error('原生支付宝生成支付参数失败：'.$e->getMessage());
            return response()->json([
                'code' => 500,
                'msg' => '生成支付参数失败：'.$e->getMessage(),
                'data' => null
            ]);
        }
    }

    public function alipayNotify($request)
    {
        $params = $request->all();
        try {
            // 2. 初始化工具类并验签（关键：防止伪造通知）
            $alipay = new AlipayNative();
            if (!$alipay->verifySign($params)) {
                Log::error('支付宝异步通知验签失败');
                return 'fail'; // 验签失败，返回fail
            }

            // 3. 验证交易状态（TRADE_SUCCESS=支付成功）
            if ($params['trade_status'] != 'TRADE_SUCCESS') {
                Log::warning('支付宝交易状态异常：'.$params['trade_status']);
                return 'success'; // 状态异常但验签成功，仍返回success避免重复通知
            }

            // 4. 处理核心业务逻辑（更新订单状态）
            $outTradeNo = $params['out_trade_no']; // 商户订单号
            $tradeNo = $params['trade_no']; // 支付宝交易号
            $payAmount = $params['total_amount']; // 实际支付金额

            // 示例：更新订单状态
            // $order = \App\Models\Order::where('out_trade_no', $outTradeNo)->first();
            // if ($order && $order->status == 'unpaid') {
            //     $order->update([
            //         'status' => 'paid',
            //         'alipay_trade_no' => $tradeNo,
            //         'paid_at' => date('Y-m-d H:i:s', strtotime($params['gmt_payment'])),
            //         'updated_at' => now(),
            //     ]);
            // }

            // 5. 必须返回"success"，否则支付宝会重复通知（最多8次）
            return 'success';
        } catch (\Exception $e) {
            Log::error('支付宝异步通知处理失败：'.$e->getMessage());
            return 'fail';
        }
    }

    public function feedBack($request)
    {
//        $request = $this->decrypt($request['data']);
        $image = $request['image'] ?? null;
        $content = $request['content'] ?? null;
        $uid = $request['uid'] ?? null;
        $agent_id = $request['agent_id'] ?? null;

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
//        $data = $this->decrypt($request['data']);

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

    public function drivingProtocol($request)
    {
//        $data = $this->decrypt($request['data']);
         $uid = $request['uid'] ?? null;

         if(!$uid){
             return ReponseData::reponseFormat(2000,'用户id必传');
         }

         $list = ProtocolManage::where('type',1)->first();

         return ReponseData::reponseFormatList(200,'成功',$list);
    }

    public function complainList($request)
    {
//        $request = $this->decrypt($request['data']);

        $data = [
            'uid' => $request['uid'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        $user = Cuser::select('id','username','special_area')->where('id', $data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $query = ComplainRecord::select('id','uid', 'order_no','venue_id', 'venue_name', 'billing_method','appeal_status','time');
        $query->where('uid', $data['uid']);

        $rows = $query->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);

        return  ReponseData::reponsePaginationFormat($rows);
    }


    public function changeName($request)
    {
//        $request = $this->decrypt($request['data']);

        $uid = $request['uid'] ?? null;
        $name = $request['name'] ?? null;

        if(!$uid){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }
        if(!$name){
            return ReponseData::reponseFormat(2000,'昵称必传!');
        }
        $user = Cuser::select('id','username','special_area')->where('id', $uid)->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $user->username = $name;
        $user->save();

        return ReponseData::reponseFormat(200,'成功');
    }

    public function accountCancel($request)
    {
//        $request = $this->decrypt($request['data']);

        $uid = $request['uid'] ?? null;

        if(!$uid){
            return ReponseData::reponseFormat(2000,'用户id必传!');
        }

        $user = Cuser::select('*')->where('id', $uid)->first();
        if(!$user){
            return ReponseData::reponseFormat(2004,'未查询到该用户!');
        }
        $user->is_cancel = 1;
        $user->save();

        return ReponseData::reponseFormat(200,'注销成功');
    }


    public function complain($request)
    {
//        $request = $this->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
            'content' => $request['content'] ?? null,
            'image' => $request['image'] ?? null,
            'order_no' =>  $request['order_no'] ?? null,
        ];
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传');
        }
        if(!$data['content']){
            return ReponseData::reponseFormat(2000,'内容必传');
        }
        if(!$data['image']){
            return ReponseData::reponseFormat(2000,'图片必传');
        }
        $order = DrivingRecord::where(['order_no'=>$data['order_no'],'reservation_status'=>4])->first();
        if(!$order){
            return ReponseData::reponseFormat(2000,'未找到该订单，请确认是否已完成');
        }
        $data['user_name'] = $order['user_name'];
        $data['phone'] = $order['phone'];
        $data['venue_id'] = $order['venue_id'];
        $data['venue_name'] = $order['venue_name'];
        $data['vehicle_id'] = $order['vehicle_id'];
        $data['vehicle_name'] = $order['vehicle_name'];
        $data['reservation_status'] = $order['reservation_status'];
        $data['billing_method'] = $order['billing_method'];
        $data['appeal_status'] = 1;
        $data['time'] = time();
        ComplainRecord::create($data);
        $order->appeal_status = 1;
        $order->save();

        return ReponseData::reponseFormat(200,'成功');
    }


    public function startDriving($request)
    {
        $data = [
            'uid' => $request['uid'] ?? null,
            'agent_id' => $request['agent_id'] ?? null,
//            'transmitter_id' => $request['transmitter_id'] ?? null,
//            'receiver_id' => $request['receiver_id'] ?? null,
            'type' => $request['type'] ?? null,
//            'amount' => $request['amount'] ?? null,
            'order_no' => $request['order_no'] ?? null,
//            'payment_type' => $request['payment_type'] ?? null,
//            'billing_method' => $request['billing_method'] ?? null,
        ];
//        if(!$data['transmitter_id']){
//            return ReponseData::reponseFormat(2000,'发射机id必传');
//        }
//        if(!$data['receiver_id']){
//            return ReponseData::reponseFormat(2000,'接收机id必传');
//        }

        //用户端处理逻辑
//        Redis::set($data['transmitter_id'],$data['receiver_id']); //绑定车辆接收机、发射机id

        if($data['uid']){
            if(!$data['order_no']){
                return ReponseData::reponseFormat(2000,'订单号必传');
            }

            if(!$data['type']){
                return ReponseData::reponseFormat(2000,'驾驶状态必传');
            }
//            if(!$data['amount']){
//                return ReponseData::reponseFormat(2000,'金额必传');
//            }

//            if($data['payment_type'] === null){
//                return ReponseData::reponseFormat(2000,'支付类型必传');
//            }
//            if($data['billing_method'] === null){
//                return ReponseData::reponseFormat(2000,'计费方式必传');
//            }
            $user = Cuser::where('id',$data['uid'])->first();
            if(!$user){
                return ReponseData::reponseFormat(2000,'未找到该用户');
            }
            $order = DrivingRecord::where('order_no',$data['order_no'])->first();
            if(!$order){
                return ReponseData::reponseFormat(2000,'未找到该预约单号');
            }
            $receiverId = Vehicle::where('id',$order['vehicle_id'])->value('receiver_id');
            Redis::set($order['transmitter_id'],$receiverId); //绑定车辆接收机、发射机id

            $data['receiver_id'] = $receiverId;
            $billingRules = json_decode($order['billing_rules'],true);
            $data['amount'] = $billingRules['battery'] ?? 0;
            $data['payment_type'] = $order['payment_type'];
//            $data['billing_method'] = $order['billing_method'];
            if($order['reservation_status'] == 4 || $order['reservation_status'] == 5){
                return ReponseData::reponseFormat(2000,'订单已完成或已取消预约');
            }
            $cuserWallet = CuserWallet::getBalance($data['uid'],$user['special_area']);

            if($data['type'] == 1){  //开始驾驶
                if($data['payment_type'] == 1){
                    if($cuserWallet['balance'] < $data['amount']){
                        return ReponseData::reponseFormat(2000,'电池余额不足！请先充值哦');
                    }
                    WalletService::safeAdjust(
                        [
                            'uid' => $user['id'],
                            'type' => CuserWalletLog::TypeConsumption,
                            'type_name'=>'驾驶扣款',
                            'make_order_no' => $order['order_no'],
                            'amount' => $data['amount'] * -1,
                            'venue'  => $user->special_area_name,
                            'special_area' => $user->special_area,
                        ]
                    );
                    //代理商余额增加 待定
                    $order->update([
                            'reservation_status' => 3,
                            'payment_amount'=> $data['amount'],
                            'start_time'=>time(),
                      ]
                    );
                    return  ReponseData::reponseFormat(200,'开始驾驶成功');
                }

                if($data['payment_type'] == 2 && $cuserWallet['energy'] < $data['amount']){
                    if($cuserWallet['energy'] < $data['amount']){
                        return ReponseData::reponseFormat(2000,'能量余额不足！请先充值哦');
                    }
                    WalletService::safeAdjustEnergy(
                        [
                            'uid' => $user['id'],
                            'type' => CuserEnergyLog::TypeConsumption,
                            'type_name'=>'驾驶扣款',
                            'make_order_no' => $order['order_no'],
                            'amount' => $data['amount'] * -1,
                            'venue'  => $user->special_area_name,
                            'special_area' => $user->special_area,
                        ]
                    );
                    $order->update([
                            'reservation_status' => 3,
                            'payment_amount'=> $data['amount'],
                            'start_time'=>time(),
                        ]
                    );
                    return  ReponseData::reponseFormat(200,'开始驾驶成功');
                }

            }

            if($data['type'] == 2) { //继续驾驶
                if($order['billing_method'] == 1){
                    return ReponseData::reponseFormat(2000,'按次计费请重新开始驾驶哦！');
                }
                if ($data['payment_type'] == 1) {
                    if ($cuserWallet['balance'] < $data['amount']) {
                        return ReponseData::reponseFormat(2000, '电池余额不足！请先充值哦');
                    }
                    $updateQuery = CuserWallet::where(['uid' => $data['uid']])->where('type',$data['special_area']);
                    $affected = $updateQuery->update(['balance' => DB::raw("balance+{$data['amount']}")]);
                    if($affected != 1){
                        Log::info("继续驾驶金额： {$data['amount']}, 余额不足或扣款失败： {$cuserWallet['balance']}");
                        return ReponseData::reponseFormat(2000,'余额不足');
                    }
                    $walletLog =  CuserWalletLog::where('make_order_no',$data['order_no'])->first();
                    if(!$walletLog){
                        return ReponseData::reponseFormat(2000,'未找到该条记录');
                    }
                    $walletLog->update([
                        'amount'=> $walletLog['amount'] + $data['amount'],
                        'balance'=> $walletLog['balance'] - $data['amount'],
                    ]);
                    //代理商余额增加 待定
                    $order->update([
                            'payment_amount' =>$order['payment_amount'] + $data['amount'],
                        ]
                    );
                    return ReponseData::reponseFormat(200, '继续驾驶成功');
                }

                if ($data['payment_type'] == 2) {
                    if ($cuserWallet['energy'] < $data['amount']) {
                        return ReponseData::reponseFormat(2000, '电池余额不足！请先充值哦');
                    }
                    $updateQuery = CuserWallet::where(['uid' => $data['uid']])->where('type',$data['special_area']);
                    $affected = $updateQuery->update(['energy' => DB::raw("energy+{$data['amount']}")]);
                    if($affected != 1){
                        Log::info("继续驾驶金额： {$data['amount']}, 能量余额不足或扣款失败： {$cuserWallet['energy']}");
                        return ReponseData::reponseFormat(2000,'余额不足');
                    }
                    $walletLog =  CuserWalletLog::where('make_order_no',$data['order_no'])->first();
                    if(!$walletLog){
                        return ReponseData::reponseFormat(2000,'未找到该条记录');
                    }
                    $walletLog->update([
                        'amount'=> $walletLog['amount'] + $data['amount'],
                        'balance'=> $walletLog['energy'] - $data['amount'],
                    ]);
                    //代理商余额增加 待定
                    $order->update([
                            'payment_amount' =>$order['payment_amount'] + $data['amount'],
                        ]
                    );
                    return ReponseData::reponseFormat(200, '继续驾驶成功');
                }
            }

            if($data['type'] == 3){ //结束驾驶
                Redis::del($order['transmitter_id']); //解绑绑定车辆接收机、发射机id
                $order->update([
                    'reservation_status' => 4,
                    'end_time'=>time(),
                    'transmitter_id' => '0',//释放发射机id
                ]);
                $receiverJson = json_decode(Redis::get($data['receiver_id'].'_receiver'),true);
                $receiverJson['transmitter_id'] = '0';
                $receiverJson['transmitter_host_port'] = '';
                Redis::set($data['receiver_id'].'_receiver',json_encode($receiverJson));
                $agentWallet = AgentWallet::getBalance($user['special_area']);
                AgentWalletLog::create([
                    'agent_id' => $data['agent_id'],
                    'type'=>1,
                    'type_name'=>'收入',
                    'amount'=>$order['payment_amount'],
                    'balance'=>$agentWallet['balance'] + $order['payment_amount'],
                    'time'=>time(),
                ]);

                return  ReponseData::reponseFormat(200,'结束驾驶成功');
            }
        }
        //代理商端处理逻辑
        if($data['agent_id']){
            return ReponseData::reponseFormat(200,'开始驾驶成功');
        }

    }

    public function reservation($request)
    {
        $data = [
            'uid' => $request['uid'] ?? null,
            'vehicle_id' => $request['vehicle_id'] ?? null,
            'vehicle_name' => $request['vehicle_name'] ?? null,
            'venue_id' => $request['venue_id'] ?? null,
            'venue_name' => $request['venue_name'] ?? null,
            'payment_type' => $request['payment_type'] ?? null,
            'billing_method' => $request['billing_method'] ?? null,
            'billing_rules' => $request['billing_rules'] ?? null,
            'app_transmitter_id' => $request['app_transmitter_id'] ?? null,
        ];

        if($data['app_transmitter_id'] === null){
            return ReponseData::reponseFormat(2000,'app发射机id必传');
        }
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传');
        }
        $user = Cuser::where('id',$data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2000,'未找到该用户');
        }

        if($data['payment_type'] === null){
            return ReponseData::reponseFormat(2000,'支付类型必传');
        }
        if($data['billing_method'] === null){
            return ReponseData::reponseFormat(2000,'计费方式必传');
        }
        $orderNo = OrderNo('ZKSJ');
        DrivingRecord::create([
            'uid' => $data['uid'],
            'user_name' => $user['username'],
            'order_no' => $orderNo,
            'vehicle_id' => $data['vehicle_id'],
            'vehicle_name' => $data['vehicle_name'],
            'venue_id' => $data['venue_id'],
            'venue_name' => $data['venue_name'],
            'billing_rules' => json_encode($data['billing_rules']),
            'special_area' => $user['special_area'],
            'special_area_name' => $user['special_area_name'],
            'phone' => $user['phone_number'],
            'reservation_status' => 1,
            'payment_type' => $data['payment_type'],
            'billing_method' => $data['billing_method'],
            'order_time' => time(),
            'agent_id' => $user['special_area'],
            'transmitter_id' => $data['app_transmitter_id'],
        ]);
        $list = [
            'vehicle_name'=>$data['vehicle_name'],
            'time' => Date('Y-m-d H:i:s'),
            'payment_type' => $data['payment_type'],
            'billing_method' => $data['billing_method'],
            'order_no' => $orderNo,
            'transmitter_id' => $data['app_transmitter_id'],
            'people_number' => DrivingRecord::where('vehicle_id', $data['vehicle_id'])->where('reservation_status', 1)->count(),//排队人数

        ];
        return ReponseData::reponseFormatList(200,'预约成功',$list);
    }

    public function cancelReservation($request)
    {
        $data = [
            'uid' => $request['uid'] ?? null,
            'order_no' => $request['order_no'] ?? null,
        ];
        if(!$data['uid']){
            return ReponseData::reponseFormat(2000,'用户id必传');
        }
        $user = Cuser::where('id',$data['uid'])->first();
        if(!$user){
            return ReponseData::reponseFormat(2000,'未找到该用户');
        }
        $order = DrivingRecord::where('order_no',$data['order_no'])->first();
        if(!$order){
            return ReponseData::reponseFormat(2000,'未找到该订单');
        }
        $order->reservation_status = 5;
        $order->save();
        return ReponseData::reponseFormat(200,'取消预约成功');
    }

    public function depositList($request)
    {
        $uid = $request['uid'] ?? null;

        if($uid){
            return ReponseData::reponseFormat(2000,'用户id必须传');
        }
        $data = [
            [
                'amount' => 10,
            ],
            [
                'amount' => 20,
            ],
            [
                'amount' => 50,
            ],
            [
                'amount' => 100,
            ],
            [
                'amount' => 200,
            ],
            [
                'amount' => 500,
            ],
        ];

        return  ReponseData::reponseFormatList(200,'成功',$data);
    }

    public function depositActivityList($request)
    {
        $uid = $request['uid'] ?? null;

        if($uid){
            return ReponseData::reponseFormat(2000,'用户id必须传');
        }
        $list = DepositActivity::select('activity_id','payment_amount','send_energy')->get();

        return ReponseData::reponseFormatList(200,'成功',$list);
    }
}
