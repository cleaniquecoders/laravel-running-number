<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRunningNumberTable extends Migration
{
    public function up()
    {
        Schema::create('running_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->default(0);
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('running_numbers');
    }
};
