<?php

namespace App\Http\Service;

use App\Models\AgentWithdrawLog;
use App\Models\ComplainRecord;
use App\Models\CuserAgent;
use App\Models\CuserWallet;
use App\Models\DepositLog;
use App\Models\ReponseData;

class PaymentService
{

    public function paymentList($request)
    {
        $data = [
            'pay_type' => $request['pay_type'] ?? null,
            'user_name' => $request['user_name'] ?? null,
            'phone_number' => $request['phone'] ?? null,
            'order_no' => $request['order_no'] ?? null,
            'pay_id' => $request['pay_id'] ?? null,
            'activity_id' => $request['activity_id'] ?? null,
            'start_finish_time' => $request['start_finish_time'] ?? null,
            'end_finish_time' => $request['end_finish_time'] ?? null,
            'start_time'  => $request['start_time'] ?? null,
            'end_time'  => $request['end_time'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];




        $list = DepositLog::select('*');
        if($data['pay_type']){
            $list->where('pay_type',$data['pay_type']);
        }

        if($data['user_name']){
            $list->where('user_name',$data['user_name']);
        }

        if($data['phone_number']){
            $list->where('phone_number',$data['phone_number']);
        }

        if($data['order_no']){
            $list->where('order_no',$data['order_no']);
        }

        if($data['pay_id']){
            $list->where('pay_id',$data['pay_id']);
        }
        if($data['activity_id']){
            $list->where('activity_id',$data['activity_id']);
        }
        if($data['finish_time']){
            $list->whereBetween('finish_time',[$data['start_finish_time'],$data['end_finish_time']]);
        }
        if($data['time']){
            $list->whereBetween('time',[$data['start_time'],$data['end_time']]);
        }
        $rows = $list->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);

        foreach($rows as $row){
            $row['finish_time'] = date('Y-m-d H:i:s',$row['finish_time']);
            $row['time'] = date('Y-m-d H:i:s',$row['time']);
        }
        return ReponseData::reponsePaginationFormat($rows);
    }

    public function withdrawList($request)
    {
        $data = [
            'agent_name' => $request['agent_name'] ?? null,
            'withdraw_type' => $request['withdraw_type'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];




        $list = AgentWithdrawLog::select('*');
        if($data['agent_name']){
            $list->where('agent_name',$data['agent_name']);
        }

        if($data['withdraw_type']){
            $list->where('withdraw_type',$data['withdraw_type']);
        }
        $rows = $list->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);
        foreach($rows as $row){
            $row['enrolment_time'] = date('Y-m-d H:i:s',$row['enrolment_time']);
            $row['audit_time'] = date('Y-m-d H:i:s',$row['audit_time']);

        }
        return ReponseData::reponsePaginationFormat($rows);
    }

    public function refundList($request)
    {
        $data = [
            'user_name' => $request['agent_name'] ?? null,
            'phone' => $request['phone'] ?? null,
            'refund_type' => $request['refund_type'] ?? null,
            'order_no'  => $request['order_no'] ?? null,
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];




        $list = ComplainRecord::select('order_no','uid','user_name','phone','refund_type','refund_amount','vehicle_name','time');
//        $list->where('refund_type','!=',0);
        if($data['user_name']){
            $list->where('user_name',$data['user_name']);
        }

        if($data['phone']){
            $list->where('phone',$data['phone']);
        }

        if($data['refund_type']){
            $list->where('refund_type',$data['refund_type']);
        }

        if($data['order_no']){
            $list->where('order_no',$data['order_no']);
        }
        $rows = $list->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);
        foreach($rows as $row){
            $row['time'] = date('Y-m-d H:i:s',$row['time']);
        }
        return ReponseData::reponsePaginationFormat($rows);
    }

    public function specialAccountList($request)
    {
        $data = [
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];
        $list = CuserAgent::select('id','agent_name','phone_number')->where('superior_agent_id',0);
        $agents = $list->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);

        if($agents->isNotEmpty()){
            foreach($agents as $agent){
                $agent['count'] = CuserWallet::where('type',$agent['id'])->count();
            }
        }

        return ReponseData::reponsePaginationFormat($agents);

    }

    public function specialDepositList($request)
    {
        $data = [
            'page' => $request['page'] ?? 1,
            'size' => $request['size'] ?? 10,
        ];
        $list = CuserAgent::select('id','agent_name','phone_number')->where('superior_agent_id',0);
        $agents = $list->orderBy("id", 'asc')->paginate($data['size'], ['*'], 'page', $data['page']);

        if($agents->isNotEmpty()){
            foreach($agents as $agent){
                $agent['deposit_amount'] = DepositLog::where('special_area',$agent['id'])->sum('amount');
                $agent['balance'] = CuserWallet::where('type',$agent['id'])->sum('balance');
            }
        }

        return ReponseData::reponsePaginationFormat($agents);

    }
}
