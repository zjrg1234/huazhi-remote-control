<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AdminUser;
use App\Models\AgentWalletLog;
use App\Models\CuserAgent;
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
            'agent_id' => 1,
            'type' => 1,
            'type_name'=>'提现',
            'amount'=>10,
            'balance'=>10,
            'make_order_no'=>'aaaaaaaca',
            'make_phone'=>'1375558339',
            'time'=>1766494114,
            'first_handling_fee'=>'2',
            'company_handling_fee'=>'20',
            'first_amount' => 2,
            'company_amount'=>2,
        ];
        AgentWalletLog::create($data);
    }
}
