<?php

namespace App\Console\Commands;

use App\Models\AgentWallet;
use App\Models\AgentWalletLog;
use App\Models\Cuser;
use App\Models\DrivingRecord;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class disposeTimeOutDrivingRecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispose-time-out-driving-record';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //

        $currentTime = time();
        $drivingRecords = DrivingRecord::where('reservation_status',3)->get();
        foreach ($drivingRecords as $drivingRecord) {
            $billing_rules = json_decode($drivingRecord->billing_rules);
            if(!$billing_rules){
                $this->info('测试订单忽略:  '.$drivingRecord['uid']);
                continue;
            }
            $user = Cuser::where('id',$drivingRecord['uid'])->first();
            if(!$user){
                $this->info('未找到用户:  '.$drivingRecord['uid']);
                continue;
            }
            $vehicle = Vehicle::where('id',$drivingRecord['vehicle_id'])->first();
            if(!$vehicle){
                $this->info('未找到车辆:  '.$vehicle['id']);
                continue;
            }
            if($drivingRecord['billing_method'] != 1){
                $rulesAmount = $billing_rules['battery'];
                $rulesTime = $billing_rules['time'] * 60;
                $orderAmount = $drivingRecord['payment_amount'];
                $startTime = $drivingRecord['start_time'];
                //已继续驾驶的次数
                $count = $orderAmount / $rulesAmount;
                //算出当前结束时间
                $endTime = $startTime + 10 + ($rulesTime * $count); //增加10秒钟冗余时间 防止接口超时等错误导致误操作
                if($currentTime > $endTime){
                    Redis::del($drivingRecord['transmitter_id']); //解绑绑定车辆接收机、发射机id
                    $drivingRecord->update([
                        'reservation_status' => 4,
                        'end_time'=>$currentTime,
                        'transmitter_id' => '0',//释放发射机id
                    ]);
                    $receiverJson = json_decode(Redis::get($drivingRecord['receiver_id'].'_receiver'),true);
                    $receiverJson['transmitter_id'] = '0';
                    $receiverJson['transmitter_host_port'] = '';
                    Redis::set($drivingRecord['receiver_id'].'_receiver',json_encode($receiverJson));
                    $agentWallet = AgentWallet::getBalance($user['special_area']);
                    $updateQuery = AgentWallet::where(['agent_id' => $drivingRecord['agent_id']]);
                    $affected = $updateQuery->update(['balance' => DB::raw("balance+{$drivingRecord['payment_amount']}")]);
                    if($affected != 1){
                        Log::info("结束驾驶收入金额： {$drivingRecord['amount']}, 增加失败： {$agentWallet['balance']}");
                    }
                    AgentWalletLog::create([
                        'agent_id' => $drivingRecord['agent_id'],
                        'type'=>1,
                        'type_name'=>'收入',
                        'amount'=>$drivingRecord['payment_amount'],
                        'balance'=>$agentWallet['balance'] + $drivingRecord['payment_amount'],
                        'time'=>time(),
                    ]);
                    $vehicle->update(['vehicle_state' => 1]);
                }
            }
            if($drivingRecord['billing_method'] == 1){
                $rulesTime = $billing_rules['time'] * 60;
                $startTime = $drivingRecord['start_time'];
                $endTime = $startTime + 10 + $rulesTime;
                if($currentTime > $endTime) {
                    Redis::del($drivingRecord['transmitter_id']); //解绑绑定车辆接收机、发射机id
                    $drivingRecord->update([
                        'reservation_status' => 4,
                        'end_time'=>$currentTime,
                        'transmitter_id' => '0',//释放发射机id
                    ]);
                    $receiverJson = json_decode(Redis::get($drivingRecord['receiver_id'].'_receiver'),true);
                    $receiverJson['transmitter_id'] = '0';
                    $receiverJson['transmitter_host_port'] = '';
                    Redis::set($drivingRecord['receiver_id'].'_receiver',json_encode($receiverJson));

                    $agentWallet = AgentWallet::getBalance($user['special_area']);
                    AgentWalletLog::create([
                        'agent_id' => $drivingRecord['agent_id'],
                        'type'=>1,
                        'type_name'=>'收入',
                        'amount'=>$drivingRecord['payment_amount'],
                        'balance'=>$agentWallet['balance'] + $drivingRecord['payment_amount'],
                        'time'=>time(),
                    ]);
                    $vehicle->update(['vehicle_state' => 1]);
                }
            }
        }
        $this->info('异常退出订单处理成功');

        $reservationRecords = DrivingRecord::whereIn('reservation_status',[1,2])->get();
        foreach($reservationRecords as $reservationRecord){
            $time =  time();
            $star_time = $reservationRecord['order_time'];
            $current_time = $time - $star_time;
            if($current_time > 90){
                $vehicleCount = Vehicle::where('id',$reservationRecord['vehicle_id'])->count();
                if($vehicleCount <= 1){
                    $reservationRecord->update([
                        'reservation_status' => 5,
                    ]);
                }
            }
        }
        $this->info('超时预约单处理成功');

    }
}
