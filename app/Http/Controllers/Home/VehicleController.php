<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Service\VehicleService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    //
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->service = new VehicleService();
    }
    public function vehicleList(Request $request)
    {
        return  $this->service->vehicleList($request);
    }

    public function bindingVenue(Request $request)
    {
        return  $this->service->bindingVenue($request);
    }
    public function deleteVehicle(Request $request)
    {
        return  $this->service->deleteVehicle($request);

    }

    public function downVenue(Request $request){
        return  $this->service->downVenue($request);

    }
}
