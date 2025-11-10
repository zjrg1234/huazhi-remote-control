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
        Schema::create('agent_wallet_log', function (Blueprint $table) {
            $table->id();
            $table->integer('agent_id')->nullable()->comment('代理商id');
            $table->integer('type')->default(0)->comment('类型：1:预约收入 2:提现');
            $table->string('type_name')->default('')->comment('类型名称');
            $table->integer('amount')->default(0)->comment('金额');
            $table->integer('balance')->default(0)->comment('余额');
            $table->string('make_order_no')->default('')->comment('预约号');
            $table->string('venue')->default('')->comment('场地');
            $table->string('make_user_name')->default('')->comment('预约用户名称');
            $table->integer('make_phone')->default(0)->comment('预约用户电话');
            $table->integer('time')->default(0)->comment('时间戳');
            $table->index('agent_id');
            $table->index('time');
            $table->index('make_phone');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_wallet_log');
    }
};
