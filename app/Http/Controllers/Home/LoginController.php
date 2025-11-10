<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Service\LoginService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    //
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->service = new LoginService();
    }
    public function login(Request $request)
    {
        return $this->service->login($request);
    }

    public function register(Request $request)
    {
        return $this->service->register($request);
    }

    public function getLoginCode(Request $request)
    {
        return $this->service->getLoginCode($request);
    }

    public function logout(Request $request){
        return $this->service->logout($request);
    }

    public function uploadPicture(Request $request)
    {
        return $this->service->uploadPicture($request);

    }
}
