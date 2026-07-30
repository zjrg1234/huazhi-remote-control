<?php

namespace App\Http\Service;

use App\Models\AgentPurchaseRecord;
use App\Models\AgentWallet;
use App\Models\AgentWalletLog;
use App\Models\Cuser;
use App\Models\CuserAgent;
use App\Models\DepositLog;
use App\Models\ScretRecord;
use App\Models\SecretPriceList;
use App\Models\ReponseData;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Vinkla\Hashids\Facades\Hashids;

class SecretPriceListService
{
    protected $setvice;

    public function __construct()
    {
        $this->setvice = new LoginService();
    }
    public function adminList($request)
    {

        $data = [

            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 20,
        ];

        $query = SecretPriceList::select('*');


        // if (isset($params['status'])) {
        //     $query->where('status', $params['status']);
        // }
        $rows = $query->orderBy("id", 'desc')->paginate($data['size'], ['*'], 'page', $data['page']);
        return ReponseData::reponsePaginationFormat($rows);
    }

        //前台接口
        public function list($request)
        {

            $agent_id = $request['agent_id'] ??  null;
            if(!$agent_id){
                return ReponseData::reponseFormat(2000,'代理商id必传');
            }
            $agent = CuserAgent::where('id',$agent_id)->first();
            if(!$agent){
                return ReponseData::reponseFormat(2000,'代理商不存在');
            }
            $list = SecretPriceList::select('*')->get();

            if($agent['level'] == 2) {
                $agent_purchase_record = AgentPurchaseRecord::where(['agent_id' => $agent_id])->where('num', '!=', 0)->where('status', '!=', 2)->pluck('status', 'secret_price_id'); //判断是否有
                $payment_agent_purchase_record = AgentPurchaseRecord::where(['agent_id' => $agent_id])->where('num', '!=', 0)->where('status', 1)->pluck('is_payment', 'secret_price_id');
                foreach ($list as $value) {
                    if (!$agent_purchase_record->isEmpty()) {
                        $exists = $agent_purchase_record[$value->id];
                        if ($exists) {
                            if ($exists == 1 && !$payment_agent_purchase_record->isEmpty() && $payment_agent_purchase_record[$value->id] === 0) { //
                                $value['is_payment'] = 1;
                                $value['is_set_ash'] = 0;
                            } else {
                                $value['is_payment'] = 0;
                                $value['is_set_ash'] = 1;
                            }

                        } else {
                            $value['is_payment'] = 0;
                            $value['is_set_ash'] = 0;
                        }
                    } else {
                        $value['is_payment'] = 0;
                        $value['is_set_ash'] = 0;
                    }

                }
            }else{
                foreach ($list as $value) {
                    $value['is_payment'] = 1;
                    $value['is_set_ash'] = 0;
                }
            }

            return ReponseData::reponseFormatList(200,'成功',$list);
        }

    /**
     * 创建数据
     */
    public function create($request)
    {
        // 注意：这要求你的 Model 里配置好 $fillable 或 $guarded
        $data = [
            'time' => $request['time'] ?? '',
            'name' => $request['name'] ?? '',
            'price' => $request['price'] ?? '',
                ];
        if(!$data['name']){
            return ReponseData::reponseFormat(2000,'名称必填');
        }
        if(!$data['price']){
            return ReponseData::reponseFormat(2000,'金额必填');
        }

        if(!$data['time']){
            return ReponseData::reponseFormat(2000,'时长必填');
        }

        SecretPriceList::create($data);
        return ReponseData::reponseFormat(200,'新建成功!');
    }

    /**
     * 更新数据
     */
    public function update($request)
    {
        $data = [
            'time' => $request['time'] ?? '',
            'name' => $request['name'] ?? '',
            'price' => $request['price'] ?? '',
            'id' =>  $request['id'] ?? null,
        ];
        if(!$data['id']){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $model = SecretPriceList::where('id',$data['id'])->first();
        if(!$model){
            return ReponseData::reponseFormat(2000,'未找到该数据!');
        }

        if(!$data['name']){
            return ReponseData::reponseFormat(2000,'名称必填');
        }
        if(!$data['price']){
            return ReponseData::reponseFormat(2000,'金额必填');
        }

        if(!$data['time']){
            return ReponseData::reponseFormat(2000,'时长必填');
        }

        $model->update($data);

         return ReponseData::reponseFormat(200,'更新成功!');
    }

    /**
     * 删除数据
     */
    public function delete($request)
    {
        $id = $request['id'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $model = SecretPriceList::where('id',$id)->first();
        if(!$model){
            return ReponseData::reponseFormat(2000,'未找到该数据!');
        }
        $model->delete();
        // 直接根据主键删除，如果是软删除，确保 Model 引入了 SoftDeletes
        return ReponseData::reponseFormat(200,'删除成功!');

    }

    public function secretApply($request)
    {
        $data = [
            'num'=>$request['num'] ?? 1,
            'agent_id'=>$request['agent_id'] ?? null,
            'amount'=>$request['amount'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'代理商id必传');
        }
        if(!$data['amount']){
            return ReponseData::reponseFormat(2000,'金额必传');
        }
        $agent = CuserAgent::where('id',$data['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }

        $secretPrice = SecretPriceList::where('price',$data['amount'])->first();
        if(!$secretPrice){
            return ReponseData::reponseFormat(2000,'未找到该密令价格');
        }
        $exists = AgentPurchaseRecord::where('secret_price_id',$secretPrice->id)->where('status',0)->where('agent_id',$data['agent_id'])->first();
        if($exists){
            return  ReponseData::reponseFormat(2000,'已有一个申请单在审核中哦');
        }
        $createPurchaseRecord = [
            'agent_id' => $agent['id'],
            'agent_name' => $agent['agent_name'],
            'secret_price_id' => $secretPrice['id'],
            'driving_time' => $secretPrice['time'],
            'amount' => $secretPrice['price'] * $data['num'],
            'superior_agent_id' => $agent['superior_agent_id'],
            'num' => $data['num'],
        ];

        AgentPurchaseRecord::create($createPurchaseRecord);

        return ReponseData::reponseFormat(200,'申请成功!');

    }

    public function wechatSecretPurchase($request)
    {

        $data = [
            'num'=>$request['num'] ?? 1,
            'agent_id'=>$request['agent_id'] ?? null,
            'amount' => $request['amount'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'代理商id必传');
        }
        if(!$data['amount']){
            return ReponseData::reponseFormat(2000,'价格必传');

        }
        $agent = CuserAgent::where('id',$data['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }
        $secretPrice = SecretPriceList::where('price',$data['amount'])->first();
        if(!$secretPrice){
            return ReponseData::reponseFormat(2000,'该价格口令数据不存在');
        }
        $wechatPayAmount = $data['amount'] * $data['num'];
        $purchaseOrder = [
            'agent_id' => $agent['id'],
            'order_no' => orderNo('CLGX-'),
            'agent_name' => $agent['agent_name'],
            'secret_price_id' => $secretPrice['id'],
            'driving_time' => $secretPrice['time'],
            'amount'=> $wechatPayAmount,
            'superior_agent_id' => 0,
            'num' => $data['num'],
            'status'=> 1,
            'is_payment' => 0,
        ];
        AgentPurchaseRecord::create($purchaseOrder);

        try{
            $wechatPay = new WechatPayV3Service();
            $purchaseOrder['subject'] = '车辆共享次数购买';
            $purchaseOrder['amount'] = $wechatPayAmount * 100; //微信需支付的金额
            $purchaseOrder['notify_url'] = env('WECHAT_PAY_PURCHASE_NOTIFY_URL'); //共享次数购买回调链接
            $resp = $wechatPay->createAppOrder($purchaseOrder);
            return ReponseData::reponseFormatList(200,'下单成功',$resp);
        }catch (\Exception $e){
            Log::error($e->getMessage());
            return $e->getMessage();
        }
    }

    public function alipaySecretPurchase($request)
    {
        $data = [
            'num'=>$request['num'] ?? 1,
            'agent_id'=>$request['agent_id'] ?? null,
            'amount' => $request['amount'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'代理商id必传');
        }
        if(!$data['amount']){
            return ReponseData::reponseFormat(2000,'价格必传');

        }
        $agent = CuserAgent::where('id',$data['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }
        $secretPrice = SecretPriceList::where('price',$data['amount'])->first();
        if(!$secretPrice){
            return ReponseData::reponseFormat(2000,'该价格口令数据不存在');
        }
        $aliPayAmount = $data['amount'] * $data['num'];
        $purchaseOrder = [
            'agent_id' => $agent['id'],
            'order_no' => orderNo('CLGX-'),
            'agent_name' => $agent['agent_name'],
            'secret_price_id' => $secretPrice['id'],
            'driving_time' => $secretPrice['time'],
            'amount'=> $aliPayAmount,
            'superior_agent_id' => 0,
            'num' => $data['num'],
            'status'=> 1,
            'is_payment' => 0,
        ];
        AgentPurchaseRecord::create($purchaseOrder);

        try {
            // 2. 初始化原生支付宝工具类
            $alipay = new AlipayNativeService();
            $purchaseOrder['subject'] = '车辆共享次数购买';
            $purchaseOrder['notify_url'] = env('ALIPAY_PURCHASE_NOTIFY_URL'); //共享次数购买回调链接
            // 3. 生成APP支付的orderStr
            $orderStr = $alipay->createAppOrder($purchaseOrder);

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

    //
    public function deductionSecretPurchase($request)
    {
        $data = [
            'num'=>$request['num'] ?? 1,
            'agent_id'=>$request['agent_id'] ?? null,
            'amount' => $request['amount'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'代理商id必传');
        }
        if(!$data['amount']){
            return ReponseData::reponseFormat(2000,'价格必传');

        }
        $agent = CuserAgent::where('id',$data['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }

        $balance = AgentWallet::getBalance($agent['id']);
        $commission = $agent['company_handling_fee'] * 0.01; //公司抽成
        $firstFee =  $agent['first_handling_fee'] * 0.01;
        $secretPrice = SecretPriceList::where('price',$data['amount'])->first();
        if(!$secretPrice){
            return ReponseData::reponseFormat(2000,'该价格口令数据不存在');
        }
        if($agent['level'] == 1){ //一级代理商
            $practicalBalance = $balance['balance'] - $balance['balance'] * $commission; //实际可抵扣金额如果产生小数点 取整 一级代理商只到这过
            $insertAmount = $secretPrice['price'] * $data['num'];
            if($practicalBalance >= $insertAmount){
                $companyAmount = $insertAmount * $commission ; //每次购买密令公司抽走的金额是被抵扣金额 * 公司抽成比
            }else{
                return ReponseData::reponseFormat(2000,'抵扣金额不足');
            }
            $purchaseOrder = [
                'agent_id' => $agent['id'],
                'order_no' => orderNo('CLGX-'),
                'agent_name' => $agent['agent_name'],
                'secret_price_id' => $secretPrice['id'],
                'driving_time' => $secretPrice['time'],
                'amount'=> $insertAmount,
                'superior_agent_id' => 0,
                'num' => $data['num'],
                'status'=> 1,
                'is_payment' => 1,
                'deduction_amount' => $insertAmount,
                'payment_time' => time(),
            ];

            WalletService::agentSafeAdjust(
                [
                    'agent_id' => $agent['id'],
                    'type' => 3,
                    'type_name' => '抵扣消费',
                    'amount' => $insertAmount * -1,
                    'balance' => $balance['balance'],
                    'make_order_no' => $purchaseOrder['order_no'],
                    'phone' => $agent['phone_number'] ?? '',
                    'time' => time(),
                    'first_handling_fee' => $agent['first_handling_fee'],
                    'company_handling_fee' => $agent['company_handling_fee'],
                    'company_amount' => $companyAmount,
                ]
            );
            AgentPurchaseRecord::create($purchaseOrder);


        }else{ //二级或其他
            $firstAmount = $balance['balance'] * $firstFee; //一级代理商划走金额
            $companyAmount = $balance['balance'] * $commission; //公司划走金额
            $insertAmount = $secretPrice['price'] * $data['num']; //该扣除的钱
            $practicalBalance = $balance['balance'] - ($companyAmount + $firstAmount); //实际可抵扣金额如果产生小数点 二级代理商
            $purchaseOrder = AgentPurchaseRecord::where(['agent_id'=>$agent['id'],'secret_price_id'=>$secretPrice['id'],'status'=>1])
                ->where('is_payment','!=',1)->first();
            if(!$purchaseOrder){
                return ReponseData::reponseFormat(2000,'未找到该申请单');
            }

            $id = WalletService::agentSafeAdjust(
                [
                    'agent_id' => $agent['id'],
                    'type' => 3,
                    'type_name' => '抵扣消费',
                    'amount' => $insertAmount * -1,
                    'balance' => $balance['balance'],
                    'make_order_no' => $purchaseOrder['order_no'],
                    'phone' => $agent['phone_number'] ?? '',
                    'time' => time(),
                    'first_handling_fee' => $agent['first_handling_fee'],
                    'company_handling_fee' => $agent['company_handling_fee'],
                    'company_amount' => $companyAmount,
                    'first_amount' => $firstAmount,
                ]
            );

            $purchaseOrder->update([
                'order_no'=>orderNo('CLGX-'),
                'deduction_amount' => $insertAmount,
                'payment_time' => time(),
                'is_payment' => 1,
            ]);

            if($id){
                $superiorAgent = CuserAgent::where('id',$data['agent_id'])->first();
                if($superiorAgent){
                    $superiorAgentBalance = AgentWallet::getBalance($superiorAgent['id']);
                    $updateQuery = AgentWallet::where(['agent_id' => $superiorAgent['id']]);
                    $affected = $updateQuery->update(['balance' => DB::raw("balance+{$firstAmount}")]);
                    if($affected != 1){
                        Log::info("结束驾驶收入金额： {$data['amount']}, 增加失败： {$superiorAgentBalance['balance']}");
                    }
                    AgentWalletLog::create([
                        'agent_id' => $superiorAgent['id'],
                        'type'=>6,
                        'type_name'=>'一级代理商抽成收入',
                        'amount'=>$firstAmount,
                        'balance'=>$superiorAgentBalance['balance'] + $firstAmount,
                        'time'=>time(),
                    ]);
                }
            }
        }

        return ReponseData::reponseFormat(200,'驾驶共享次数购买成功');

    }

    /**
     * Hashids解密方法：
     *密令按数组排列暂定前几个字段固定为：
     * 0为uid 1为车辆id 2为state（1为临时共享车辆,2为绑定共享 3为口令码）3为随机生成的1-5位数,4为代理商id,5价格id
     **/
    public function secretCreate($request)
    {
//        $request = $this->setvice->decrypt($request['data']);
        $data = [
          'agent_id' => $request['agent_id'] ?? null,
          'show_id'  => $request['show_id'] ?? null,
          'vehicle_id' => $request['vehicle_id'] ?? null,
          'secret_id' => $request['secret_id'] ?? null,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'代理商id必传');
        }
        if(!$data['secret_id']){
            return ReponseData::reponseFormat(2000,'密令id必传');
        }
        if(!$data['vehicle_id']){
            return ReponseData::reponseFormat(2000,'车辆id必传');
        }
        if(!$data['show_id']){
            return ReponseData::reponseFormat(2000,'展示id必传');
        }

        $agent = CuserAgent::where('id',$data['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }

        $agentPurchaseRecord = AgentPurchaseRecord::where('id',$data['secret_id'])->first();
        if(!$agentPurchaseRecord){
            return ReponseData::reponseFormat(2000,'未找到该密令购买记录');
        }

        $vehicle = Vehicle::where('id',$data['vehicle_id'])->first();
        if(!$vehicle){
            return ReponseData::reponseFormat(2000,'未找到该车辆');
        }

        if($agentPurchaseRecord['num'] < 1){
            return ReponseData::reponseFormat(2000,'密令次数已用完');
        }

        $url = config('fingertip.url').'/api/get/show/uid';
        $body = [
            'show_id' => $data['show_id'],
        ];
        $encryptData = $this->setvice->encrypt($body);
        $resp = Http::post($url,[
            'data'=>$encryptData
        ])->json();
        if($resp['code'] != 200)
        {
            return ReponseData::reponseFormat(2000,'未找到该用户哦');
        }
        $respData = $this->setvice->decrypt($resp['data']);
        $secretRecord = ScretRecord::where(['uid'=>$respData['uid'],'agent_id'=>$data['agent_id'],'secret_id'=>$data['secret_id'],'vehicle_id'=>$data['vehicle_id']])->first();
        if(isset($secretRecord) && $secretRecord['is_valid'] == 1){
            return ReponseData::reponseFormat(2000,'用户已有该时长口令没使用，请先使用哦');
        }


        $insertData  = [
            'uid' => $respData['uid'],
            'agent_id' => $data['agent_id'],
//            'second_agent_id' => $data['second_agent_id'],
            'vehicle_id' => $vehicle['id'],
            'vehicle_name'=>$vehicle['vehicle_name'],
            'secret_id' => $data['secret_id'],
            'is_first' => 1,
            'is_valid'=>1,
            'show_id'=> $data['show_id'],
        ];
        $secretRecordUpdate = ScretRecord::create($insertData);
        $hashArray = [
            $respData['uid'],
            $vehicle['id'],
            3,
            mt_rand(1, 99999),
            intval($data['agent_id']),
            intval($secretRecordUpdate['id']),
            intval($agentPurchaseRecord['driving_time'])
        ];

        $hash = Hashids::connection('main')->encode($hashArray);
        Redis::set($hash,1);
        $secret_name = $hash.'-分享码已生成，复制打开【指尖无界】享受速度与激情【个人端】';
        $secretRecordUpdate->update(['secret_name' => $secret_name]);
        $agentPurchaseRecord['num'] = $agentPurchaseRecord['num'] - 1;
        $agentPurchaseRecord->save();
        return ReponseData::reponseFormat(200,'密令生成成功');
//        $user =  ;
//        $data = $this->setvice->decrypt($request);

    }

    public function secretRecord($request)
    {
        $data = [
            'secret_name' => $request['secret_name'] ?? null,
            'vehicle_id' => $request['vehicle_id'] ?? null,
            'agent_id'=>$request['agent_id'] ?? null,
            'second_agent_id' => $request['second_agent_id'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 20,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'账号不存在');
        }

        $query = ScretRecord::select('*')->where('agent_id',$data['agent_id']);



        if(isset($data['secret_name'])){
            $query = $query->where("secret_name",$data['secret_name']);
        }

        if(isset($data['vehicle_id'])){
            $query = $query->where("vehicle_id",$data['vehicle_id']);
        }

        if(isset($data['second_agent_id'])){
            $query = $query->where("second_agent_id",$data['second_agent_id']);
        }

        $rows = $query->orderBy("id", 'desc')->paginate($data['size'], ['*'], 'page', $data['page']);
        return ReponseData::reponsePaginationFormat($rows);

    }

    public function getSecretStatus($request)
    {

        $request = $this->setvice->decrypt($request['data']);
        $data = [
            'uid' => $request['uid'] ?? null,
            'vehicle_id' => $request['vehicle_id'] ?? null,
            'agent_id' => $request['agent_id'] ?? null,
            'secret_record_id' => $request['secret_record_id'] ?? null,
        ];

        if(!$data['agent_id'] || !$data['vehicle_id'] || !$data['uid'] || !$data['secret_record_id']){
            return ReponseData::reponseFormat(2000,'参数错误,请确认');
        }

        $agent = CuserAgent::where('id',$data['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'分享人不存在');
        }

        $vehicle = Vehicle::where('id',$data['vehicle_id'])->first();
        if(!$vehicle){
            return  ReponseData::reponseFormat(2000,'车辆不存在');
        }

        $secretRecord = ScretRecord::where('id',$data['secret_record_id'])->first();
        if(!$secretRecord){
            return  ReponseData::reponseFormat(2000,'分享码无效');
        }

        if($secretRecord['is_valid'] != 1){
            return ReponseData::reponseFormat(2000,'分享码已被使用');
        }

        if($secretRecord['agent_id'] != $agent['id'] || $secretRecord['uid'] != $data['uid'] || $secretRecord['vehicle_id'] != $data['vehicle_id']){
            return  ReponseData::reponseFormat(2000,'请确认分享码使用人');
        }
//        $secretRecord->update(['is_valid'=>2]);

        return ReponseData::reponseFormat(200,'获取成功');

    }

    public function secretApplyList($request)
    {
//        $request = $this->setvice->decrypt($request['data']);

        $data = [
            'agent_id'=>$request['agent_id'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 20,
        ];

        if(!$data['agent_id']){
            return ReponseData::reponseFormat(2000,'id必填');
        }

        $agent = CuserAgent::where('id',$data['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }
        if($agent['level'] == 1){
            $query = AgentPurchaseRecord::where('superior_agent_id',$data['agent_id'])->where('status',0);

        }else{
            $query = AgentPurchaseRecord::where('agent_id',$data['agent_id'])->where('status',0);

        }
        $rows = $query->orderBy("id", 'desc')->paginate($data['size'], ['*'], 'page', $data['page']);

        return ReponseData::reponsePaginationFormat($rows);
    }

    public function secretApplyAudit($request)
    {
        $id = $request['id'];
        $agent_id = $request['agent_id'];
        $status = $request['status'];
        if(!$status){
            return ReponseData::reponseFormat(2000,'审核状态必填');

        }

        if(!$id){
            return ReponseData::reponseFormat(2000,'id必填');
        }

        if(!$agent_id){
            return ReponseData::reponseFormat(2000,'代理商id必填');
        }

        $agentPurchaseRecord = AgentPurchaseRecord::where('id',$id)->first();
        if(!$agentPurchaseRecord){
            return ReponseData::reponseFormat(2000,'未找到该数据');
        }

        $agent = CuserAgent::where('id',$agent_id)->first();

        if(!$agent){
            return ReponseData::reponseFormat(2000,'未找到该代理商');
        }

        $agentPurchaseRecord->update(['status'=>$status]);
        return ReponseData::reponseFormat(200,'审核成功');
    }
}
