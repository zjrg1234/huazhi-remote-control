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
        Schema::create('cuser_energy_log', function (Blueprint $table) {
            $table->id();
            $table->integer('uid')->nullable()->comment('用户id');
            $table->integer('type')->default(0)->comment('类型：1:注册赠送能量 2:充值优惠赠送');
            $table->string('type_name')->default('')->comment('类型名称');
            $table->integer('energy')->default(0)->comment('金额');
            $table->integer('surplus_energy')->default(0)->comment('余额');
            $table->string('make_order_no')->default('')->comment('预约号');
            $table->string('venue')->default('')->comment('场地');
            $table->integer('recharge_amount')->default(0)->comment('充值金额');
            $table->integer('time')->default(0)->comment('时间戳');
            $table->index('make_order_no');
            $table->index('uid');
            $table->index('time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuser_energy_log');
    }
};
