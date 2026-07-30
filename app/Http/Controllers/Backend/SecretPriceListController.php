<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Service\SecretPriceListService;
use Illuminate\Http\Request;

class SecretPriceListController extends Controller
{
    protected $service;

    public function __construct(SecretPriceListService $service)
    {
        $this->service = $service;
    }

    public function list(Request $request)
    {
        return $this->service->list($request);
    }

    public function create(Request $request)
    {
        return $this->service->create($request);
    }

    public function update(Request $request)
    {
        return $this->service->update($request);
    }

    public function delete(Request $request)
    {
        return $this->service->delete($request);
    }

    public function secretPurchase(Request $request)
    {
        return $this->service->secretPurchase($request);

    }

    public function secretApply(Request $request)
    {
        return $this->service->secretApply($request);

    }

    public function wechatSecretPurchase(Request $request){
        return $this->service->wechatSecretPurchase($request);
    }

    public function alipaySecretPurchase(Request $request){
        return $this->service->alipaySecretPurchase($request);
    }

    public function deductionSecretPurchase(Request $request){
        return $this->service->deductionSecretPurchase($request);
    }

    public function secretCreate(Request $request)
    {
        return $this->service->secretCreate($request);

    }

    public function secretRecord(Request $request)
    {
        return $this->service->secretRecord($request);

    }

    public function getSecretStatus(Request $request)
    {
        return $this->service->getSecretStatus($request);

    }

    public function secretApplyList(Request $request)
    {
        return $this->service->secretApplyList($request);
    }

    public function secretApplyAudit(Request $request)
    {
        return $this->service->secretApplyAudit($request);
    }
}
