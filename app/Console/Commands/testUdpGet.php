<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class testUdpGet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-udp-get {test}';

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
//        $redis = Redis::get('42126194');
//        Redis::set('42126194',"42156194");
//        dd($redis);
//        exit;
        $test = $this->argument('test');
//        $data = '5A431500283432313236313934001BF5000fE8431F4561094B58C2129600000000000000000000000000EF0d'; //开机
//        $data = '5A431000283432313536313934001BF5000fE8431F4561094B58C2129600000000000000000000000000EF0d'; //发射机发送
        $data = 'ZC28001308&&98930212111203928112977'; //心跳
//          $data = 'ZCD28001308&';
//        $host = $request['host'];
//          $data = [
//        90, 67, 22, 17, 0, 50, 56, 48, 48, 49, 51, 48, 56, 38, 0, 4, 156, 255, 255,
//		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
//		0, 0, 0, 0, 0, 0, 255, 255, 255, 0, 13,
//	];
        $port = 8899;
        $lport = 8878;
        if($test == 'test'){
            $host = '127.0.0.1';
        }else{
            $name = 'huazhi.localtest.me';
            $host = gethostbyname($name);
        }

        $socket = socket_create(AF_INET, SOCK_DGRAM, 0);
        socket_bind($socket, $host, $lport);

        if ($socket === false) {
            echo "创建套接字失败：" . socket_strerror(socket_last_error()) . PHP_EOL;
            return false;
        }
//        $data = '5A431500283432313236313934001BF5000fE8431F4561094B58C2129600000000000000000000000000EF0d'; //15为接收机开机自动发送
//        $data = '5A431000283432313236313934001B00000fE8431F4561094B58C2129600000000000000000000000000EF0d';//10为发射机传入、
//        $host = 'zhuanfa.localhtest.me';
//        $host = 'xhzzf.huazyk.cn';
//        $port = '8899';
//        $host = 'xhzzf.huazyk.cn';
//        $port = '8899';
        $sendLen = socket_sendto($socket, $data, strlen($data), 0, $host, $port);
        if ($sendLen === false) {
            echo "发送失败：" . socket_strerror(socket_last_error($socket)) . PHP_EOL;
            return false;
        }
        echo "发送成功，字节数：{$sendLen}，数据：{$data}" . PHP_EOL;
        echo "UDP 接收端已启动，监听端口：{$lport}，等待数据..." . PHP_EOL;
        exit;
// 循环接收数据
        while (true) {
            $data = '';
            $clientIp = '';
            $clientPort = 0;

            // 接收数据（缓冲区大小 1024 字节，可调整）
            $recvLength = socket_recvfrom(
                $socket,
                $data,
                1024,
                0,
                $clientIp,
                $clientPort
            );

            if ($recvLength > 0) {
                echo "收到数据：来自 {$clientIp}:{$clientPort}，数据：{$data}，字节数：{$recvLength}" . PHP_EOL;
            }
        }
        socket_close($socket);
    }
}
