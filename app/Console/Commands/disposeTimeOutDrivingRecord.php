<?php

namespace App\Console\Commands;

use App\Http\Service\WalletService;
use App\Models\AgentWallet;
use App\Models\AgentWalletLog;
use App\Models\Cuser;
use App\Models\CuserWalletLog;
use App\Models\DrivingRecord;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use function Symfony\Component\Translation\t;

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
            $billing_rules = json_decode($drivingRecord['billing_rules'],true);
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
                $this->info('未找到车辆:  ' . $drivingRecord['vehicle_id']);
                continue;
            }
            if($drivingRecord['billing_method'] != 1){ //按时间
                $rulesAmount = $billing_rules['battery'];
                $rulesTime = $billing_rules['time'] * 60;
                $orderAmount = $drivingRecord['payment_amount'];
                $startTime = $drivingRecord['start_time'];
                if($startTime === 0 && $drivingRecord['reservation_status'] == 3){ //如果没有开始驾驶时间 脏的单子 直接取消
                    $drivingRecord->update([
                        'reservation_status' => 5,
                        'transmitter_id' => '0',//释放发射机id
                    ]);
                }
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
                    if($drivingRecord['payment_type'] == 1) {
                        $agentWallet = AgentWallet::getBalance($drivingRecord['agent_id']);
                        $balance = $agentWallet['balance'];
                        $updateQuery = AgentWallet::where(['agent_id' => $drivingRecord['agent_id']]);
                        $affected = $updateQuery->update(['balance' => DB::raw("balance+{$drivingRecord['payment_amount']}")]);
                        if ($affected != 1) {
                            Log::info("结束驾驶收入金额： {$drivingRecord['amount']}, 增加失败： {$agentWallet['balance']}");
                        }
                        $afterBalance = $balance - $drivingRecord['payment_amount'];
                        AgentWalletLog::create([
                            'agent_id' => $drivingRecord['agent_id'],
                            'type' => 1,
                            'type_name' => '收入',
                            'amount' => $drivingRecord['payment_amount'],
                            'make_order_no' => $drivingRecord['order_no'],
                            'balance' => $afterBalance,
                            'time' => time(),
                        ]);
                    }
                    $vehicle->update(['vehicle_state' => 1]);
                    $this->info('已处理异常单子： ' . $drivingRecord['order_no']);
                }
            }
            if($drivingRecord['billing_method'] == 1){ //按次
                $rulesTime = $billing_rules['time'] * 60;
                $startTime = $drivingRecord['start_time'];
                Redis::del($drivingRecord['transmitter_id']); //解绑绑定车辆接收机、发射机id
                if($startTime === 0 && $drivingRecord['reservation_status'] == 3){ //如果没有开始驾驶时间 脏的单子 直接取消
                    $drivingRecord->update([
                        'reservation_status' => 5,
                        'transmitter_id' => '0',//释放发射机id
                    ]);

                }
                $endTime = $startTime + 10 + $rulesTime;
                $time = time();
                $count = intval(($time - $startTime) / $rulesTime) + 1; //已进行次数
                $shouldTime = $startTime + ($rulesTime * $count); //当前阶段应该结束时间
                $shouldTime2 = $shouldTime - $time; //阶段剩余多少时间
                $shouldTime3 = $rulesTime - $shouldTime2; //阶段时间-剩余时间
                $rulesAmount = $billing_rules['battery']; //金额
                $num = $shouldTime3 / $rulesTime;
                if($currentTime > $endTime) {

//                if($num < 0.75){
//                    if($num < 0.25){
//                        $returnAmount = intval($rulesAmount * ($shouldTime2 / $rulesTime)); //返回金额 = 阶段金额*当前剩余时间/阶段时间
//                    }else{
//                        $returnAmount = intval($rulesAmount * ($shouldTime2 / $rulesTime)); //返回金额 = 阶段金额*当前剩余时间/阶段时间
//                    }
//
//                    if($drivingRecord['payment_type'] == 1){
//                        WalletService::safeAdjust([
//                            'uid' => $user->id,
//                            'type' => CuserWalletLog::TypeReturn,
//                            'type_name'=>'提前结束驾驶退还',
//                            'make_order_no' => $drivingRecord['order_no'],
//                            'amount' => $returnAmount,
//                            'venue'  => $user->special_area_name,
//                            'special_area' => $user->special_area,
//                        ]);
//                    }
//                    if($drivingRecord['payment_type'] == 2){
//                        WalletService::safeAdjustEnergy([
//                            'uid' => $user->id,
//                            'type' => CuserWalletLog::TypeReturn,
//                            'type_name'=>'提前结束驾驶退还',
//                            'make_order_no' => $drivingRecord['order_no'],
//                            'amount' => $returnAmount,
//                            'venue'  => $user->special_area_name,
//                            'special_area' => $user->special_area,
//                        ]);
//                    }
//                }
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

                    if($drivingRecord['payment_type'] == 1) {
                        $agentWallet = AgentWallet::getBalance($drivingRecord['agent_id']);
                        $balance = $agentWallet['balance'];
                        $updateQuery = AgentWallet::where(['agent_id' => $drivingRecord['agent_id']]);
                        $affected = $updateQuery->update(['balance' => DB::raw("balance+{$drivingRecord['payment_amount']}")]);
                        if ($affected != 1) {
                            Log::info("结束驾驶收入金额： {$drivingRecord['amount']}, 增加失败： {$agentWallet['balance']}");
                        }
                        $afterBalance = $balance - $drivingRecord['payment_amount'];

                        AgentWalletLog::create([
                            'agent_id' => $drivingRecord['agent_id'],
                            'type' => 1,
                            'type_name' => '收入',
                            'amount' => $drivingRecord['payment_amount'],
                            'make_order_no' => $drivingRecord['order_no'],
                            'balance' => $afterBalance,
                            'time' => time(),
                        ]);
                    }
                    $vehicle->update(['vehicle_state' => 1]);
                    $this->info('已处理异常单子： ' . $drivingRecord['order_no']);

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
