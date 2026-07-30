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
        Schema::create('secret_record', function (Blueprint $table) {
            $table->id();
            $table->string('secret_name')->default('')->comment('密钥码');
            $table->integer('agent_id')->default(0)->comment('一级代理商id');
            $table->integer('secret_id')->default(0)->comment('价格表id');
            $table->integer('uid')->default(0)->comment('使用者-个人端uid');
            $table->string('agent_name')->default('')->comment('代理商名称');;
            $table->integer('second_agent_id')->default(0)->comment('二级代理商id');
            $table->integer('is_first')->default(0)->comment('是否一级代理自己使用：0 否 1 是');
            $table->integer('vehicle_id')->default(0)->comment('车辆id');
            $table->string('vehicle_name')->default('')->comment('车辆名称');
            $table->integer('is_valid')->default(1)->comment('是否有效：1有效 2已被使用');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secret_record');
    }
};
