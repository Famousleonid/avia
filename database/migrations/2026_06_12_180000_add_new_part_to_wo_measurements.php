<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_measurements', function (Blueprint $table) {
            // Verification measurement of a REPLACEMENT (new) part on an
            // Order New position — judged strictly by the orig limits and
            // shown as "(new)" instead of a repair stage.
            $table->boolean('new_part')->default(false)->after('stage');
        });
    }

    public function down(): void
    {
        Schema::table('wo_measurements', function (Blueprint $table) {
            $table->dropColumn('new_part');
        });
    }
};
