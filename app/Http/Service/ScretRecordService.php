<?php

namespace App\Http\Service;

use App\Models\CuserAgent;
use App\Models\ScretRecord;
use App\Models\ReponseData;
use App\Models\ShareRecord;
use Illuminate\Support\Facades\Redis;
use Vinkla\Hashids\Facades\Hashids;

class ScretRecordService
{
    /**
     * 获取列表页数据(后台)
     * * @param array $params 前端传来的过滤参数
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function adminList($request)
    {

        $data = [
                    'uid' => $request['uid'] ?? null,
                    'page' => $request['page'] ?? 1,
                    'size' => $request['size'] ?? 20,
                ];

        $query = ScretRecord::select('*');


        // if (isset($params['status'])) {
        //     $query->where('status', $params['status']);
        // }
        $rows = $query->orderBy("id", 'desc')->paginate($data['size'], ['*'], 'page', $data['page']);
        return ReponseData::reponsePaginationFormat($rows);
    }


        public function list($request)
        {
            $data = [
                'secret_name' => $request['secret_name'] ?? null,
                'vehicle_id' => $request['vehicle_id'] ?? null,
                'agent_id'=>$request['agent_id'] ?? null,
                'second_agent_id' => $request['second_agent_id'] ?? null,
                'page' => $request['page'] ?? 1,
                'size' => $request['size'] ?? 20,
            ];

            $query = ScretRecord::select('*');

            if(isset($data['agent_id'])){
                $query = $query->where("agent_id",$data['agent_id']);
            }

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

     /**
     Hashids加密方法：
     *按数组排列暂定：
     * 0为uid或agent_id 1为车辆id 2为state（1为临时共享车辆,2为绑定共享 3为口令码）3为随机生成的8位数,4为共享的id
     **/
    public function create($request)
    {
        // 注意：这要求你的 Model 里配置好 $fillable 或 $guarded
        $data = [
            'agent_id' => $request['agent_id'] ?? null,
            'second_agent_id' => $request['second_agent_id'] ?? null,
            'vehicle_id' => $request['vehicle_id'] ?? 0,
            'num' => $request['num'] ?? 1,
        ];
        $count = ScretRecord::where(['agent_id',$request['agent_id'],'is_valid'=>1])->count();
        $number = $data['num'] + $count;
        $agent = CuserAgent::where('agent_id',$request['agent_id'])->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }

        if(!$data['second_agent_id']){
            return ReponseData::reponseFormat(2000,'代理商id必须传');
        }
        if($number > 50){
           return ReponseData::reponseFormat(200,'有效密令还有'.$count.'个，请调整新增个数哦！最高为50个哦');
        }



        for($i = 0; $i < $data['num']; $i++){
            $hashArray = [
                $data['uid'],
                $data['vehicle_id'],
                $data['type'],
                mt_rand(1, 99999),
                $data['agent_id']
            ];
            $hash = Hashids::connection('main')->encode($hashArray);
            Redis::set($hash,1);
            $secret_name =
            $insertData[] = [
                'agent_id' => $data['agent_id'],
                'second_agent_id' => $data['second_agent_id'],
                'vehicle_id' => $data['vehicle_id'],
                'is_first' => 1,
                'secret_name'=>$secret_name,
            ];
        }

        ScretRecord::create($data);
        return ReponseData::reponseFormat(200,'新建成功!');
    }

    /**
     * 更新数据
     */
    public function update($request)
    {
        $id = $request['id'] ?? null;
        if(!$id){
            return ReponseData::reponseFormat(2000,'id必传!');
        }
        $model = ScretRecord::where('id',$id)->first();
        if(!$model){
            return ReponseData::reponseFormat(2000,'未找到该数据!');
        }
        $data = [
                    'secret_name' => $request['secret_name'] ?? '',
            'agent_id' => $request['agent_id'] ?? '',
            'uid' => $request['uid'] ?? '',
            'agent_name' => $request['agent_name'] ?? '',
            'second_agent_id' => $request['second_agent_id'] ?? '',
            'is_first' => $request['is_first'] ?? '',
            'vehicle_id' => $request['vehicle_id'] ?? '',
            'vehicle_name' => $request['vehicle_name'] ?? '',
            'is_valid' => $request['is_valid'] ?? '',
            'created_at' => $request['created_at'] ?? '',
            'updated_at' => $request['updated_at'] ?? '',
                ];

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
        $model = ScretRecord::where('id',$id)->first();
        if(!$model){
            return ReponseData::reponseFormat(2000,'未找到该数据!');
        }
        $model->delete();
        // 直接根据主键删除，如果是软删除，确保 Model 引入了 SoftDeletes
        return ReponseData::reponseFormat(200,'删除成功!');

    }

    public function changeNum($request)
    {
        $agent_id = $request['agent_id'] ?? null;
        $second_agent_id = $request['second_agent_id'] ?? null;
        $num = $request['num'] ?? null;
        if(!$agent_id || !$second_agent_id){
            return ReponseData::reponseFormat(2000,'代理商id必传');
        }
        if(!$num){
            return ReponseData::reponseFormat(2000,'划转次数必填');
        }
        $agent = CuserAgent::where('agent_id',$agent_id)->first();
        if(!$agent){
            return ReponseData::reponseFormat(2000,'代理商不存在');
        }
        $second_agent = CuserAgent::where('agent_id',$second_agent_id)->first();
        if(!$second_agent){
            return ReponseData::reponseFormat(2000,'二级代理商不存在');
        }

        if($agent->secret_num < $num){
            return ReponseData::reponseFormat(2000,'可划转次数不足');
        }
        $agent->num = $agent->num - $num;
        $agent->save();
        $second_agent->num = $second_agent->num + $num;
        $second_agent->save();

        return ReponseData::reponseFormat(200,'划转成功');
    }
}
