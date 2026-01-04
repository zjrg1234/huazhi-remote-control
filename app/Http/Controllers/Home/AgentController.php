<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Service\AgentService;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->service = new AgentService();
    }

    public function agentMine(Request $request)
    {
        return $this->service->agentMine($request);
    }

    public function agentDrivingRecord(Request $request)
    {
        return $this->service->agentDrivingRecord($request);

    }
    public function agentDriving(Request $request)
    {
        return $this->service->agentDriving($request);

    }

    public function agentWalletLog(Request $request)
    {
        return $this->service->agentWalletLog($request);

    }
}
