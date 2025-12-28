<?php

namespace App\Http\Service;

use App\Models\CommonProblem;
use App\Models\FeedBack;
use App\Models\ProtocolManage;
use App\Models\ReponseData;

class PlatformConfigService{

    public function commonProblemList($request)
    {
        $data = [
            'problem'=> $request['problem'] ?? null,
        ];
        $query = CommonProblem::select('*');
        if($data['problem']){
            $query->where('name',$data['problem']);
        }
        $list = $query->get();


        return ReponseData::reponseFormatList(200,'获取成功',$list);
    }

    public function commonProblemCreate($request)
    {
        $data = [
            'name'=> $request['name'] ?? null,
            'detail' => $request['detail'] ?? null,
            'sort' => $request['sort'] ?? 1,
            'status' => $request['status'] ?? null,
        ];

        if(!$data['name']){
            return ReponseData::reponseFormat(2000,'问题标题必须填');
        }
        if(!$data['detail']){
            return ReponseData::reponseFormat(2000,'详情必须填');
        }

        if(!$data['status']){
            return ReponseData::reponseFormat(2000,'状态必须填');

        }

        CommonProblem::create($data);


        return  ReponseData::reponseFormat(200,'新增成功');
    }

    public function commonProblemUpdate($request)
    {
        $id = $request['id'] ?? null;

        $data = [
            'name'=> $request['name'] ?? null,
            'detail' => $request['detail'] ?? null,
            'sort' => $request['sort'] ?? 1,
            'status' => $request['status'] ?? null,
        ];

        if(!$id){
            return ReponseData::reponseFormat(2000,'id必填');

        }
        if(!$data['name']){
            return ReponseData::reponseFormat(2000,'问题标题必须填');
        }
        if(!$data['detail']){
            return ReponseData::reponseFormat(2000,'详情必须填');
        }

        if(!$data['status']){
            return ReponseData::reponseFormat(2000,'状态必须填');

        }

        $list = CommonProblem::where('id',$id)->first();
        if(!$list){
            return ReponseData::reponseFormat(2000,'未找到该数据哦');
        }
        $list->update($data);

        return  ReponseData::reponseFormat(200,'更新成功');
    }

    public function commonProblemDelete($request)
    {
        $id = $request['id'] ?? null;
        if(!$id) {
            return ReponseData::reponseFormat(2000, 'id必传!');
        }
        $list = CommonProblem::select('*')->where('id', $id)->first();
        if(!$list){
            return ReponseData::reponseFormat(2001,'未找到该数据哦!');
        }
        $list->delete();
        return ReponseData::reponseFormat(200,'删除成功');
    }


    public function protocolManageList($request)
    {
        $data = [
            'name'=> $request['name'] ?? null,
        ];
        $query = ProtocolManage::select('*');
        if($data['name']){
            $query->where('name',$data['name']);
        }
        $list = $query->get();


        return ReponseData::reponseFormatList(200,'获取成功',$list);
    }


    public function protocolManageUpdate($request)
    {
        $id = $request['id'] ?? null;

        $data = [
            'content' => $request['content'] ?? null,
        ];

        if(!$id){
            return ReponseData::reponseFormat(2000,'id必填');
        }

        if(!$data['content']){
            return ReponseData::reponseFormat(2000,'详情必须填');
        }


        $list = ProtocolManage::where('id',$id)->first();
        if(!$list){
            return ReponseData::reponseFormat(2000,'未找到该数据哦');
        }
        $list->update($data);

        return  ReponseData::reponseFormat(200,'更新成功');
    }


    public function feedBackList($request)
    {
        $query_params = [
            'page'                 => $request['page'] ?? 1,
            'size'                 => $request['size'] ?? 10,
            'user_name'         => $request['user_name'] ?? null,
            'type'            => $request['type'] ?? null,


        ];
        $query = FeedBack::select('*');
        if(isset($query_params['user_name'])){
            $query->where('user_name',$query_params['user_name']);
        }

        if(isset($query_params['type'])){
            $query->where('type',$query_params['type']);
        }

        $rows = $query->orderBy("id", 'asc')->paginate($query_params['size'], ['*'], 'page', $query_params['page']);
        foreach ($rows as $value){
            $value['time'] = date('Y-m-d H:i:s', $value['time']);
        }

        return ReponseData::reponsePaginationFormat($rows);
    }

    public function feedBackUpdate($request)
    {
        $id = $request['id'] ?? null;

        $data = [
            'remark' => $request['remark'] ?? null,
            'type' => $request['type'] ?? null,
        ];

        if(!$id){
            return ReponseData::reponseFormat(2000,'id必填');
        }

        if(!$data['remark']){
            return ReponseData::reponseFormat(2000,'备注必须填');
        }
        if(!$data['type']){
            return ReponseData::reponseFormat(2000,'处理状态必须填');
        }

        $list = FeedBack::where('id',$id)->first();
        if(!$list){
            return ReponseData::reponseFormat(2000,'未找到该数据哦');
        }
        $list->update($data);

        return  ReponseData::reponseFormat(200,'更新成功');
    }

}
