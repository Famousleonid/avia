<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->date('expected_completion_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workorders', fn (Blueprint $table) => $table->dropColumn('expected_completion_date'));
    }
};
