<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('process_names', 'sequence_exempt')) {
            Schema::table('process_names', function (Blueprint $table): void {
                $table->boolean('sequence_exempt')->default(false)->after('show_in_process_picker');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('process_names', 'sequence_exempt')) {
            Schema::table('process_names', function (Blueprint $table): void {
                $table->dropColumn('sequence_exempt');
            });
        }
    }
};
