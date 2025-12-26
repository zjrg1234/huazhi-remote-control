<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AdminUser;
use App\Models\AgentWalletLog;
use App\Models\CuserAgent;
use App\Models\CuserEnergyLog;
use App\Models\CuserWalletLog;
use App\Models\DrivingRecord;
use App\Models\ReceiverTransmitterConfig;
use App\Models\Vehicle;
use App\Models\WarZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
//         \App\Models\User::factory(10)->create();
//
//         \App\Models\User::factory()->create([
//             'name' => 'Test User',
//             'email' => 'test@example.com',
//         ]);
//        $this->run(UserService::class);
//        $data = [
//            'username'=>'admin',
//            'password'=>Hash::make('7vmKNu1QACDzx'),
//            'type'=>1,
//        ];
//        AdminUser::create($data);
          //代理
//            $data = [
//                'uid' => 1,
//                'agent_name'=>'笆斗先生',
//                'level'=>1,
//                'phone_number'=>'1238917281',
//                'venue_quantity'=>0,
//                'create_site_quantity'=>100,
//                'is_support'=>0,
//            ];
//

//             $data = [
//                 'vehicle_id' => 1,
//                 'receiver_id' => '42126194',
//                 'transmitter_id' => '42321112',
//             ];
//            ReceiverTransmitterConfig::create($data);
//            $data = [
//                'uid' => 1,
//                'agent_name'=>'笆斗先生',
//                'level'=>1,
//                'phone_number'=>'1238917281',
//                'venue_quantity'=>0,
//                'create_site_quantity'=>100,
//                'is_support'=>0,
//                'superior_agent_id'=>0,
//                'withdrawal_amount'=>100,
//                'first_handling_fee'=>'0',
//                'company_handling_fee'=>20,
//            ];
////
//	   CuserAgent::create($data);
//             $data = [
//                 'vehicle_id' => 1,
//                 'receiver_id' => '42126194',
//                 'transmitter_id' => '42321112',
//             ];
//            ReceiverTransmitterConfig::create($data);
//        $data = [
//            'agent_id'=>1,
//            'name'=>'飞天专区',
//        ];
//        WarZone::create($data);


        $data =[
            'uid'=>9,
            'user_name'=>'大笆斗',
            'order_no'=>'aaacasd13213121',
            'phone'=>'13785889191',
            'venue_id'=>1,
            'venue_name'=>'测试',
            'vehicle_id'=>12,
            'vehicle_name'=>'飞车21111',
            'payment_type'=>1,
            'reservation_status'=>2,
            'payment_amount'=>10,
            'start_time'=>1766671612,
            'end_time'=>1766671618,
            'order_time'=>1766671601,
            'billing_method'=>0,
            'appeal_status'=>0,
            'billing_rules'=>'20电池每分钟',
            'special_area'=>1,
            'special_area_name'=>'笆斗',

        ];
        DrivingRecord::create($data);
    }
}
