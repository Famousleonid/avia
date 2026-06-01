<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (! Schema::hasColumn('workorders', 'arrival_box_notes')) {
                $table->text('arrival_box_notes')->nullable()->after('arrival_box_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (Schema::hasColumn('workorders', 'arrival_box_notes')) {
                $table->dropColumn('arrival_box_notes');
            }
        });
    }
};
