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
        Schema::create('secret_driving_record', function (Blueprint $table) {
            $table->id();
            $table->integer('uid')->default(0)->comment('用户id');
            $table->string('show_id')->default(0)->comment('用户show_id');
            $table->integer('agent_id')->default(0)->comment('企业商id');
            $table->string('user_name')->default(0)->comment('用户名称');
            $table->integer('vehicle_id')->default(0)->comment('车辆id');
            $table->string('vehicle_name')->default('')->comment('车辆名称');
            $table->string('order_no')->default('')->comment('订单编号/预约号');
            $table->integer('driving_status')->default(0)->comment('订单状态 1已完成');
            $table->integer('payment_amount')->default(0)->comment('支付金额');
            $table->integer('start_time')->default(0)->comment('开始时间');
            $table->integer('end_time')->default(0)->comment('结束时间');
            $table->string('transmitter_id')->default('')->comment('发射机');
            $table->string('receiver_id')->default('')->comment('接收机');
            $table->string('password_code')->default('')->comment('口令号');
            $table->index('uid');
            $table->index('agent_id');
            $table->index('order_no');
            $table->index('vehicle_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secret_drivin_decord');
    }
};
