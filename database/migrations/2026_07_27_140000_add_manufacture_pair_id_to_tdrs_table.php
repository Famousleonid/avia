<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdrs', function (Blueprint $table): void {
            if (! Schema::hasColumn('tdrs', 'manufacture_pair_id')) {
                $table->uuid('manufacture_pair_id')->nullable()->after('tdr_type')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdrs', function (Blueprint $table): void {
            if (Schema::hasColumn('tdrs', 'manufacture_pair_id')) {
                $table->dropIndex(['manufacture_pair_id']);
                $table->dropColumn('manufacture_pair_id');
            }
        });
    }
};
