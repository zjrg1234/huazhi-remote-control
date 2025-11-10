<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Service\UserService;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    //
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->service = new UserService();
    }
    public function list(Request $request)
    {
        return  $this->service->list($request);
    }

    public function details(Request $request)
    {
        return  $this->service->details($request);
    }

    public function modifyBalance(Request $request)
    {
        return  $this->service->modifyBalance($request);

    }
}
