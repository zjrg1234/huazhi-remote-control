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
        Schema::create('agent_purchase_record', function (Blueprint $table) {
            $table->id();
            $table->integer('agent_id')->default(0)->comment('申请代理商id');
            $table->string('agent_name')->default('')->comment('申请代理商的名称');
            $table->string('order_no')->default('')->comment('支付订单号');
            $table->integer('secret_price_id')->default(0)->comment('密令表id');
            $table->integer('driving_time')->default(0)->comment('驾驶时长');
            $table->integer('amount')->default(0)->comment('金额');
            $table->integer('superior_agent_id')->default(0)->comment('上级代理商id');
            $table->integer('num')->default(0)->comment('次数');
            $table->integer('status')->default(0)->comment('0待审核 1通过 2拒绝');
            $table->integer('is_payment')->default(0)->comment('是否已付款 0否 1是');
            $table->integer('payment_time')->default(0)->comment('支付时间');
            $table->integer('payment_amount')->default(0)->comment('实际支付金额整数');
            $table->integer('third_order_no')->default(0)->comment('三方单号：微信或支付宝');
            $table->integer('deduction_amount')->default(0)->comment('抵扣金额');
            $table->index('agent_id');
            $table->index('secret_price_id');
            $table->index('superior_agent_id');
            $table->index('order_no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_purchase_record');
    }
};
