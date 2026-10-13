<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('manual_part_group_coverages', 'requires_selected_option_ids')) {
            Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
                $table->json('requires_selected_option_ids')->nullable()->after('choice_slot');
            });
        }
    }

    public function down(): void
    {
        Schema::table('manual_part_group_coverages', function (Blueprint $table): void {
            $table->dropColumn('requires_selected_option_ids');
        });
    }
};
