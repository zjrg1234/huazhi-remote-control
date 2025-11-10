<?php

namespace App\Http\Middleware;

use App\Models\Cuser;
use App\Models\ReponseData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(env('APP_ENV')=='local' && env('ISBACKENDTOKEN')=='NONONO'){
            return $next($request);
        }

        $aesKey = config('aes.aes_key');
        $request = json_decode(aesDecrypt($request['data'],'aes-128-ecb',$aesKey),true);
        if (!isset($request['session_key']) || !isset($request['id'])) {
            return ReponseData::reponseFormat(100, '请先登陆!');
        }
        $uid = $request['uid'];
        $key = 'token_' . $uid;
        $userToken = Redis::get($key);
        if (!$userToken) {
            return ReponseData::reponseFormat(100, '请先登陆!');
        }
        if ($userToken != $request['session_key']) {
            return ReponseData::reponseFormat(130, 'token 验证错误!');
        }
        $user = Cuser::find($uid);
        if(!$user) {
            return ReponseData::reponseFormat(130, '未找到该用户!');
        }
//        if($user['is_locket'] == 1){
//            return ReponseData::reponseFormat(130, '该账户已被锁定，请联系管理员!');
//        }
        return $next($request);
    }
}
