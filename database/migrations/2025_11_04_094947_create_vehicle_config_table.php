<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_config', function (Blueprint $table) {
            $table->id();
            $table->integer('vehicle_id')->default(0)->comment('车辆id');
            $table->integer('turn_direction')->default(100)->comment('方向中位微调');
//            $table->integer('turn_left')->default(100)->comment('方向左转微调'); //去除
//            $table->integer('turn_right')->default(100)->comment('方向右转微调');//去除
            $table->integer('oil_strength')->default(100)->comment('油门中位微调');
            $table->integer('turn_strength')->default(100)->comment('方向力度微调');
            $table->integer('oil_direction')->default(100)->comment('油门力度微调');
            $table->integer('video_definition')->default(0)->comment('视频清晰度：1户外、2标清、3高清、4超清');
            $table->integer('rear_camera_type')->default(0)->comment('后置摄像头状态：1左上角、2右上角、0关闭');
            $table->integer('operation_mode')->default(0)->comment('操作选择');
            $table->text('vehicle_config_detail')->nullable()->comment('配置详情');
            $table->index('vehicle_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_config');
    }
};
