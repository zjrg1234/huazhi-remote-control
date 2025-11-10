<?php
namespace App\Http\Service;

use App\Http\Repo\LoginRepo;
use App\Models\WarZone;
use App\Models\Cuser;
use App\Models\ReponseData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\CuserWallet;

class LoginService
{
    protected $repo;
    public function __construct()
    {
        $this->repo = new LoginRepo();
    }
    public function login($request)
    {
        $ip = getIp($request);
        $request = $this->decrypt($request['data']);

        $data = [
            'phone' => $request['phone'],
            'captcha'  => $request['captcha'] ?? null,
            'type'     => $request['type'] ?? null,
        ];
        if(isset($request['password'])){
            $data['password'] = Hash::make($request['password']);
        }else{
            $data['password'] = null;
        }
        $this->validateRequestLogin($data);
        $userInfo = $this->repo->getUserByMobile($data['phone']);
        if(!isset($userInfo)){
            return ReponseData::reponseFormat(2003,'账号未注册，请先注册哦！');
        }
        if($data['type'] == '1'){ //用户端登陆
            if(isset($data['password']) && $userInfo['password'] != $data['password']){
                return ReponseData::reponseFormat(2003,'账号密码错误！');

            }

            if(isset($data['captcha']) && $data['captcha'] != '666666'){
                return ReponseData::reponseFormat(2003,'验证码错误！');
            }
            $nowTime                 = time();
            $sessionKey              = base64_encode(md5($userInfo['id'].$userInfo['user_name'].$nowTime));
            $key = 'token_'.$userInfo['id'];
            Redis::set($key, $sessionKey);
            $updateData = [
                'last_online_time' => $nowTime,
                'login_ip' => $ip,
                'session_key' => $sessionKey,
            ];
            Cuser::where('id', $userInfo['id'])->update($updateData);
            $response =  [
                'id' => $userInfo['id'],
                'session_key' => $sessionKey,
            ];
            $responseData = $this->encrypt($response);
            return ReponseData::reponseFormatList(200,'成功',$responseData);
        }else{
            if(isset($data['captcha']) && $data['captcha'] != '666666'){
                return ReponseData::reponseFormat(2003,'验证码错误！');
            }
            $nowTime                 = time();
            $sessionKey              = base64_encode(md5($userInfo['id'].$userInfo['user_name'].$nowTime));
            $key = 'token_'.$userInfo['id'];
            Redis::set($key, $sessionKey);
            $updateData = [
                'last_online_time' => $nowTime,
                'login_ip' => $ip,
                'session_key' => $sessionKey,
            ];
            Cuser::where('id', $userInfo['id'])->update($updateData);
            $response =  [
                'id' => $userInfo['id'],
                'session_key' => $sessionKey,
            ];
            $responseData = $this->encrypt($response);
            return ReponseData::reponseFormatList(200,'成功',$responseData);

        }


//        CuserCacheService::setUserLoginInfo($userInfo['id'], $deviceInfo->toArray());

    }

    public function register($request)
    {
        $ip = getIp($request);
        $request = $this->decrypt($request['data']);
        $data = [
            'phone' => $request['phone'],
            'password' => md5($request['password']),
            'noteVerify' => $request['noteVerify'],
        ];
        $validator = $this->validateRequestRegister($data);
        if($validator->fails()){
            return ReponseData::reponseFormat(2002,$validator->errors()->first());
        }

        $lock_key = "Register::".$data['phone'];
        $ret      = Redis::set($lock_key, '1', 'ex', '3', 'nx');
        if(!$ret){
            return ReponseData::reponseFormat(2002,'请勿重复点击哦!');
        }
        $userExists = $this->repo->getUsers($data['phone']);
        if(isset($userExists)){
            return ReponseData::reponseFormat(2002,'该用户已注册!');
        }
        if(!$data['noteVerify']){
            return ReponseData::reponseFormat(2002,'验证码必填!');
        }
        if($data['noteVerify'] != '666666'){
            return ReponseData::reponseFormat(2002,'验证码错误!');
        }

        $minId = WarZone::query()->min('id');
        $maxId = WarZone::query()->max('id');
        $roundId = mt_rand($minId, $maxId);
        $special_area = WarZone::where('id','>=',$roundId)->first();
        $insertData = [
            'phone_number' => $data['phone'],
            'password' => md5($data['password']),
            'special_area' => $special_area['id'],
            'special_area_name' => $special_area['name'],
            'register_time' => time(),
            'login_ip' => $ip,
        ];

        $user = $this->repo->createUsers($insertData);
        $balance = CuserWallet::getBalance($user['id']);
        if($user && isset($balance)){
            $response = $this->encrypt($this->registerLogin($user));
            return ReponseData::reponseData($response);
        }else{
            return ReponseData::reponseFormat(2002,'注册出错！');
        }
    }
    public function registerLogin($userInfo)
    {
        $nowTime                 = time();
        $sessionKey              = base64_encode(md5($userInfo['id'].$userInfo['user_name'].$nowTime));
        $key = 'token_'.$userInfo['id'];
        Redis::set($key, $sessionKey);
        $updateData = [
            'last_online_time' => $nowTime,
            'session_key' => $sessionKey,
        ];
        Cuser::where('id', $userInfo['id'])->update($updateData);
        return [
            'id' => $userInfo['id'],
            'session_key' => $sessionKey,
        ];
    }
    public function getLoginCode($request){

        $request = $this->decrypt($request['data']);
        $rules = [
            'phone'            => 'required|regex:/^1[3-9]\d{9}$/|digits:11',
        ];

        $message = [
            'phone.required'            => '手机号不能为空',
            'phone.regex'                 => '手机号格式错误',
            'phone.digits'                 => '手机号必须为11位数字',

        ];
        $validator = Validator::make($request, $rules, $message);
        if($validator->fails()){
            return ReponseData::reponseFormat(2001,$validator->errors()->first());
        }
        $code = '666666';
        $data = [
            'code' => $code,
        ];
        $response = $this->encrypt($data);

        return ReponseData::reponseFormatList(200,'获取成功',$response);
    }

    public function logout($request)
    {
        $request = $this->decrypt($request['data']);
        $uid = $request['uid'];
        $key = 'token_'.$uid;
        Redis::del($key);

        return ReponseData::reponseFormat(200,'退出成功！');
    }


    protected function validateRequestLogin($data)
    {
        $rules = [
            'phone'            => 'required|regex:/^1[3-9]\d{9}$/|digits:11',
        ];

        $message = [
            'phone.required'            => '手机号不能为空',
            'phone.regex'                 => '手机号格式错误',
            'phone.digits'                 => '手机号必须为11位数字',

        ];

        return Validator::make($data, $rules, $message);

    }

    protected function validateRequestRegister($data)
    {
        $rules = [
            'phone'         => 'required|regex:/^1[3-9]\d{9}$/|digits:11',
            'password'             => 'required',
            'noteVerify'         => 'required',
        ];

        $message = [
            'phone.required'            => '手机号不能为空',
            'phone.regex'                 => '手机号格式错误',
            'phone.digits'                 => '手机号必须为11位数字',
            'password.required'             => '密码不能为空',
            'noteVerify.required'    => '验证码不能为空',
        ];

        return Validator::make($data, $rules, $message);

    }

    public function encrypt($data)
    {
        $aesKey = config('aes.aes_key');
        $json = json_encode($data);
        return aesEncrypt($json,'aes-128-ecb',$aesKey);
    }
    public function decrypt($data)
    {
        $aesKey = config('aes.aes_key');
        return json_decode(aesDecrypt($data,'aes-128-ecb',$aesKey),true);
    }

    public function uploadPicture($request)
    {
        $data = $this->decrypt($request['data']);
        $imageContent = $request->File('imageFile');
        $base64Image = $imageContent->get();
//        $binaryData =  base64_decode($base64Image);
        $fileName = time() . '.' . 'jpeg';
        Storage::put('public/images/' . $fileName, $base64Image); //上传至阿里云oss 或者存入本地先


    }



}
