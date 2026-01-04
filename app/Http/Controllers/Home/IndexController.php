<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Service\IndexService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    //

    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->service = new IndexService();
    }

    public function startupPage(Request $request)
    {
        return $this->service->startupPage($request);
    }

    public function index(Request $request)
    {
        return $this->service->index($request);
    }
    public function venueDetail(Request $request)
    {
        return $this->service->venueDetail($request);
    }
    public function mine(Request $request)
    {
        return $this->service->mine($request);
    }
    public function specialList(Request $request)
    {
        return $this->service->specialList($request);
    }
    public function changeSpecial(Request $request)
    {
        return $this->service->changeSpecial($request);
    }
    public function reservationList(Request $request)
    {
        return $this->service->reservationList($request);
    }

    public function drivingRecord(Request $request)
    {
        return $this->service->drivingRecord($request);
    }
    public function walletList(Request $request)
    {
        return $this->service->walletList($request);

    }
    public function wechatDeposit(Request $request)
    {
        return $this->service->wechatDeposit($request);
    }
    public function wechatNotify(Request $request)
    {
        return $this->service->wechatNotify($request);

    }
    public function alipayDeposit(Request $request)
    {
        return $this->service->alipayDeposit($request);
    }

    public function feedBack(Request $request)
    {
        return $this->service->feedBack($request);

    }
    public function deactivate(Request $request)
    {
        return $this->service->deactivate($request);

    }

    public function drivingProtocol(Request $request)
    {
        return $this->service->drivingProtocol($request);

    }

    public function complainList(Request $request)
    {
        return $this->service->complainList($request);

    }

    public function changeName(Request $request)
    {
        return $this->service->changeName($request);

    }

    public function accountCancel(Request $request)
    {
        return $this->service->accountCancel($request);

    }
}
