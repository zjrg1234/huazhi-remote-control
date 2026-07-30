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
        Schema::table('cuser_agent', function (Blueprint $table) {
            $table->integer('mange')->default(0)->after('show_id')->comment('管理员等级0啥都不是 1为最高 依次类推');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuser_agent', function (Blueprint $table) {
            //
        });
    }
};
