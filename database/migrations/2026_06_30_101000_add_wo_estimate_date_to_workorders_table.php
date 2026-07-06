<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (! Schema::hasColumn('workorders', 'wo_estimate_date')) {
                $table->date('wo_estimate_date')->nullable()->after('open_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (Schema::hasColumn('workorders', 'wo_estimate_date')) {
                $table->dropColumn('wo_estimate_date');
            }
        });
    }
};
