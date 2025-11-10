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
        Schema::create('cuser_wallet_log', function (Blueprint $table) {
            $table->id();
            $table->integer('uid')->nullable()->comment('用户id');
            $table->integer('type')->default(0)->comment('类型：1:充值 2:预约扣分 3:驾驶退还');
            $table->string('type_name')->default('')->comment('类型名称');
            $table->integer('amount')->default(0)->comment('金额');
            $table->integer('balance')->default(0)->comment('余额');
            $table->string('make_order_no')->default('')->comment('预约号');
            $table->string('venue')->default('')->comment('场地');
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
        Schema::dropIfExists('cuser_wallet_log');
    }
};
