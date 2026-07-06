<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (! Schema::hasColumn('workorders', 'wo_terms')) {
                $table->string('wo_terms', 120)->nullable()->after('customer_po');
            }

            if (! Schema::hasColumn('workorders', 'wo_estimate_amount')) {
                $table->decimal('wo_estimate_amount', 14, 2)->nullable()->after('wo_terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (Schema::hasColumn('workorders', 'wo_estimate_amount')) {
                $table->dropColumn('wo_estimate_amount');
            }

            if (Schema::hasColumn('workorders', 'wo_terms')) {
                $table->dropColumn('wo_terms');
            }
        });
    }
};
