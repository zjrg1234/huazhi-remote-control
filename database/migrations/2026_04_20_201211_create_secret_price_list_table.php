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
        Schema::create('secret_price_list', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->default(0)->comment('次数');
            $table->integer('time')->default(0)->comment('驾驶时长');
            $table->string('name')->default('')->comment('名称');
            $table->integer('price')->default(0)->comment('价格');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secret_price_list');
    }
};
