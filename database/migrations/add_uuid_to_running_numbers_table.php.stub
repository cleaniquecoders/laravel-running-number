<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Generate UUIDs for existing records
        $model = config('running-number.model');
        $model::whereNull('uuid')->each(function ($record) {
            $record->uuid = (string) \Illuminate\Support\Str::uuid();
            $record->save();
        });
    }

    public function down()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
