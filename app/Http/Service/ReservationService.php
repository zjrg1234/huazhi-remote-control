<?php

namespace App\Http\Service;


use App\Models\ComplainRecord;
use App\Models\Cuser;
use App\Models\CuserWalletLog;
use App\Models\DrivingRecord;
use App\Models\ReponseData;

class ReservationService{


    public function reservationRecord($request)
    {
        $query_params = [
            'page'                 => $request['page'] ?? 1,
            'size'                 => $request['size'] ?? 10,
            'phone'         => $request['phone'] ?? null,
            'user_name'            => $request['user_name'] ?? null,
            'order_no'            => $request['order_no'] ?? null,
            'reservation_status'         => $request['reservation_status'] ?? null,
            'billing_method'  => $request['billing_method'] ?? null,
            'venue_id'    => $request['venue_id'] ?? null,
            'appeal_status'         => $request['appeal_status'] ?? null,

        ];
        $query = DrivingRecord::select(
            'id',
            'user_name',
            'order_no',
            'phone',
            'venue_id',
            'venue_name',
            'vehicle_id',
            'vehicle_name',
            'payment_type',
            'reservation_status',
            'payment_amount',
            'start_time',
            'end_time',
            'order_time',
            'billing_method',
            'appeal_status',
            'billing_rules',
            'special_area',
            'special_area_name');

        if(isset($query_params['phone'])){
            $query->where('phone',$query_params['phone']);
        }

        if(isset($query_params['user_name'])){
            $query->where('user_name',$query_params['user_name']);
        }

        if(isset($query_params['order_no'])){
            $query->where('order_no',$query_params['order_no']);
        }

        if(isset($query_params['reservation_status'])){
            $query->where('reservation_status',$query_params['reservation_status']);
        }

        if(isset($query_params['appeal_status'])){
            $query->where('appeal_status',$query_params['appeal_status']);
        }

        if(isset($query_params['billing_method'])){
            $query->where('billing_method',$query_params['billing_method']);
        }
        if(isset($query_params['venue_id'])){
            $query->where('venue_id',$query_params['venue_id']);
        }
        $rows = $query->orderBy("id", 'asc')->paginate($query_params['size'], ['*'], 'page', $query_params['page']);
        foreach ($rows as $value) {
            $value['start_time'] = date('Y-m-d H:i:s',$value['start_time']);
            $value['end_time'] = date('Y-m-d H:i:s',$value['end_time']);
            $value['order_time'] = date('Y-m-d H:i:s',$value['order_time']);
        }

        return ReponseData::reponsePaginationFormat($rows);
    }

    public function complaintRecord($request)
    {
        $query_params = [
            'page'                 => $request['page'] ?? 1,
            'size'                 => $request['size'] ?? 10,
            'user_name'            => $request['user_name'] ?? null,
            'order_no'            => $request['order_no'] ?? null,
            'reservation_status'         => $request['reservation_status'] ?? null,
            'billing_method'  => $request['billing_method'] ?? null,
            'venue_id'    => $request['venue_id'] ?? null,
            'appeal_status'         => $request['appeal_status'] ?? null,
        ];

        $query = ComplainRecord::select(
            'id',
            'user_name',
            'order_no',
            'image',
            'phone',
            'venue_id',
            'venue_name',
            'vehicle_id',
            'vehicle_name',
            'reservation_status',
            'amount',
            'time',
            'billing_method',
            'appeal_status',
            'refund_amount',
            'refund_type',
            'refund_cause',
            'platform_reply'
        );



        if(isset($query_params['user_name'])){
            $query->where('user_name',$query_params['user_name']);
        }

        if(isset($query_params['order_no'])){
            $query->where('order_no',$query_params['order_no']);
        }

        if(isset($query_params['reservation_status'])){
            $query->where('reservation_status',$query_params['reservation_status']);
        }

        if(isset($query_params['appeal_status'])){
            $query->where('appeal_status',$query_params['appeal_status']);
        }

        if(isset($query_params['billing_method'])){
            $query->where('billing_method',$query_params['billing_method']);
        }
        if(isset($query_params['venue_id'])){
            $query->where('venue_id',$query_params['venue_id']);
        }

        $rows = $query->orderBy("id", 'asc')->paginate($query_params['size'], ['*'], 'page', $query_params['page']);
        foreach ($rows as $value) {
            $value['time'] = date('Y-m-d H:i:s',$value['time']);
        }

        return ReponseData::reponsePaginationFormat($rows);

    }

    public function complaintUpdate($request)
    {
        $id = $request['id'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传');
        }
        $complaint = ComplainRecord::where('id', $id)->first();
        if(!$complaint){
            return ReponseData::reponseFormat(2000,'未找到该数据');
        }
        if($complaint['appeal_status'] == 2){
            return ReponseData::reponseFormat(200,'成功');
        }
        $update = [
            'refund_type' => $request['refund_type'] ?? $complaint['refund_type'],
            'refund_cause' => $request['refund_cause'] ?? $complaint['refund_cause'],
            'platform_reply' => $request['platform_reply'] ?? $complaint['platform_reply'],
            'refund_amount' => $request['refund_amount'] ?? 0,
        ];
        $complaint->update($update);
        $user = Cuser::where('id', $complaint['uid'])->first();
        WalletService::safeAdjust([
            'uid' => $user->id,
            'type' => CuserWalletLog::TypeChange,
            'type_name'=>'管理员修改余额',
            'make_order_no' => orderNo('CG'),
            'amount' => $update['refund_amount'],
            'venue'  => $user->special_area_name,
            'special_area' => $user->special_area,
        ]);
        return ReponseData::reponseFormat(200,'成功');
    }
}
