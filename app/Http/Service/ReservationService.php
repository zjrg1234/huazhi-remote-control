<?php

namespace App\Http\Service;


use App\Models\ComplainRecord;
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
    }
}
