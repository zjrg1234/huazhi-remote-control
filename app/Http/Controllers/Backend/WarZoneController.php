<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ReponseData;
use App\Models\WarZone;
use Illuminate\Http\Request;

class WarZoneController extends Controller
{
    //
    public function create(Request $request)
    {
        $data = [
             'name' => $request['name'],
        ];

        if(!$data['name']){
            return ReponseData::reponseFormat(400,'专区名称必填');
        }
        $exists = WarZone::query()->where('name', $data['name'])->first();
        if($exists){
            return ReponseData::reponseFormat(400,'该专区已存在');
        }
        WarZone::create($data);

        return ReponseData::reponseFormat(200,'创建成功');
    }

    public function list(Request $request)
    {

        $query = WarZone::select('*');
        $data = $query->orderBy("id", 'desc')->paginate($request['size']);


        return ReponseData::reponsePaginationFormat($data);
    }

}
