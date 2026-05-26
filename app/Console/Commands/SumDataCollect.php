<?php

namespace App\Console\Commands;

use App\Models\ComplainRecord;
use App\Models\DataCollect;
use App\Models\DepositLog;
use App\Models\DrivingRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SumDataCollect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sum-data-collect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计前一天的金额';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $data = DataCollect::select('total_sale', 'total_make', 'total_payment', 'total_refund')->where('id',1)->first();
        // 昨天 00:00:00


        // 昨天 23:59:59
        $end_time = strtotime(Carbon::yesterday()->endOfDay());

        $data['total_sale'] = DrivingRecord::where('reservation_status',4)->where('order_time','<=',$end_time)->sum('payment_amount');
        $data['total_make'] = DrivingRecord::where('order_time','<=',$end_time)->count();
        $data['total_payment'] = DepositLog::where('type',1)->where('time','<=',$end_time)->count();
        $data['total_refund'] = ComplainRecord::where('refund_type',1)->where('time','<=',$end_time)->sum('refund_amount');

        $data->save();
        $this->info('统计过往充值数据完成');
        return 0;
    }
}
