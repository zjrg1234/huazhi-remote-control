<?php
namespace App\Http\Service;

use App\Models\Cuser;
use App\Models\CUserWallet;
use App\Models\ReponseData;

class UserService
{

    public function list($request)
    {
        $query_params = [
            'page'                 => $request['page'] ?? 1,
            'size'                 => $request['size'] ?? 10,
            'phone_number'         => $request['phone_number'] ?? null,
            'nick_name'            => $request['nick_name'] ?? null,
            'is_cancel'            => $request['is_cancel'] ?? null,
            'special_area'         => $request['special_area'] ?? null,
            'start_register_time'  => $request['start_register_time'] ?? null,
            'end_register_time'    => $request['end_register_time'] ?? null,
        ];
        $query = Cuser::select('id','username','phone_number','special_area_name','head_shot','is_real_name','real_name','register_time');
        if(isset($query_params['phone_number'])){
            $query->where('phone_number',$query_params['phone_number']);
        }

        if(isset($query_params['nick_name'])){
            $query->where('nick_name',$query_params['nick_name']);
        }

        if(isset($query_params['special_area'])){
            $query->where('special_area',$query_params['special_area']);
        }

        if(isset($query_params['is_cancel'])){
            $query->where('is_cancel',$query_params['is_cancel']);
        }

        if(isset($query_params['special_area'])){
        $query->where('special_area',$query_params['special_area']);
        }

        if(isset($query_params['start_register_time']) && isset($query_params['end_register_time'])){
            $query->whereBetween('register_time',[$query_params['start_register_time'],$query_params['end_register_time']]);
        }

        $rows = $query->orderBy("id", 'asc')->paginate($query_params['size'], ['*'], 'page', $query_params['page']);
        $uids = array_column($rows->items(), 'id');
        $userBalanceWallet = CUserWallet::query()
            ->whereIn('uid', $uids)
            ->pluck('balance', 'uid')
            ->toArray();

        $userEnergyWallet = CUserWallet::query()
            ->whereIn('uid', $uids)
            ->pluck('energy', 'uid')
            ->toArray();
        foreach ($rows as $value){
            $value['balance'] = $userBalanceWallet[$value['id'] ?? '0'];
            $value['energy'] = $userEnergyWallet[$value['id'] ?? '0'];
            $value['register_time'] = date('Y-m-d H:i:s', $value['register_time']);

        }
        return ReponseData::reponsePaginationFormat($rows);
    }

    public function details($request)
    {
        $id = $request['id'] ?? null;

        if(!$id){
            return ReponseData::reponseFormat(2001,'id必传!');
        }
        $user = Cuser::select('special_area_name','phone_number','nick_name','head_shot')->where('id', $id)->first();
        if(!$user){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }

        return ReponseData::reponseFormatList(200,'成功',$user);
    }

    public function modifyBalance($request)
    {
        $id = $request['id'] ?? null;
        $money = $request['money'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2001,'id必传!');
        }
        if(!$money){
            return ReponseData::reponseFormat(2001,'金额必填!');
        }
        $user = Cuser::where('id', $id)->first();
        if(!$user){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }
        $userWallet = CUserWallet::where('uid', $id)->first();
        if(!$userWallet){
            return ReponseData::reponseFormat(2001,'未找到该用户哦!');
        }

    }
}
