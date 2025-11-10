<?php

namespace App\Http\Service;

class WalletService{

    /**
     * 安全减 / (amount 为正数)
     * [
     * 'uid' => , 'platform_id' => ,'order_no' => '', 'platform_order_no' => ,'type'   => , 'amount' => , 'currency' => '', ,'msg' => '',
     * ];
     * 不能减为负数 , 需要减为负数请用 ->add
     * 需要在调用之前 获得 锁和 DB::beginTransaction() ,  结束活异常之后
     * @param $data
     */
    static function safeAdjust($data) {
        $balance = CUserWallet::getBalance($data['uid'], $data['currency']);

        $updateQuery = CUserWallet::where(['uid' => $data['uid'],'currency' => $data['currency']]);
        if ($data['amount'] < 0) {
            $updateQuery->where('balance','>=', abs($data['amount']));
        }
        $affected = $updateQuery->update(['balance' => DB::raw( "balance+{$data['amount']}")]);
        if ($affected != 1) {
            throw new InsufficientBalanceException("want {$data['amount']}, has {$balance['balance']}");
        }

        $userInfo=Cuser::cacheFor(QUERY_CACHE_SECOND)->find($data['uid']);
        if(!$userInfo){
            return false;
        }
        Log::info("safeAdjust : " .json_encode($data, 320));
        // Log::info('safeAdjust : source_code '.$userInfo->source_code);

        $ticketInfo=GameTicket::query()->where('order_no',$data['order_no'])->first();
        $msg='';
        if($ticketInfo){
            if($ticketInfo->game_id){
                $msg=GameList::cacheFor(QUERY_CACHE_SECOND)->where('id',$ticketInfo->game_id)->value('title');
            }
            $msg=$msg?$msg.','.$ticketInfo->third_order_no:$ticketInfo->third_order_no;
        }

        if(isset($data['msg']) && $data['msg'] != ''){
            $msg = $data['msg'];
        }

        $logItem = Wallet::create([
            'app_platform' => getDatasourcePlatform(),
            'uid'               => $data['uid'],
            'platform_id'       => $data['platform_id']??0,
            'order_no'          => $data['order_no'],
            'platform_order_no' => $data['platform_order_no']??'',
            'type'              => $data['type'],
            'currency'          => $data['currency'],
            'amount'            => $data['amount'],
            'before_balance'    => $balance['balance'],
            'balance'           => $balance['balance'] + $data['amount'],
            'create_time'       => $data['create_time']??Carbon::now(), // 如果有就用，如果没有就用现在时间
            'remark'            => $data['remark']??'',
            'agent_name'        => $userInfo->source_code??'admin',//$agentName?$agentName:'admin',
            'vip'               => $userInfo->vip,
            'account_type'      => $userInfo->account_type,
            'msg'               => $msg,
        ]);

        if(in_array($data['type'],wallet::$bonusTypes)){
            $userArr =$userInfo->toArray();
            KafkaMessageService::bonus($userArr,$logItem);
        }

        //全部发送
        if(1 || in_array($data['type'],wallet::$WalletChangeTypes)){
            $userArr =$userInfo->toArray();
            KafkaMessageService::walletChange($userArr,$logItem);
        }

        return $logItem->id;
    }
}
