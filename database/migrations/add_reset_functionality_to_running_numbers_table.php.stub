<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->string('reset_period')->default('never')->after('type');
            $table->timestamp('last_reset_at')->nullable()->after('reset_period');
        });
    }

    public function down()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->dropColumn(['reset_period', 'last_reset_at']);
        });
    }
};
