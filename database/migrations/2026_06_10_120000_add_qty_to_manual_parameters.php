<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            // Number of bushings installed at this position (e.g. 2 pressed
            // from both sides of one lug). Used for sketch qty and ordering.
            $table->unsignedTinyInteger('qty')->default(1)->after('requires_value');
        });
    }

    public function down(): void
    {
        Schema::table('manual_parameters', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
