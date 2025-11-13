<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->string('scope')->nullable()->after('type');

            // Drop the old unique constraint on type if it exists
            // Add composite unique constraint on type + scope
            $table->unique(['type', 'scope'], 'running_numbers_type_scope_unique');
        });
    }

    public function down()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->dropUnique('running_numbers_type_scope_unique');
            $table->dropColumn('scope');
        });
    }
};
