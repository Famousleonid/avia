<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdrs', function (Blueprint $table): void {
            if (! Schema::hasColumn('tdrs', 'tdr_type')) {
                $table->string('tdr_type', 64)->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdrs', function (Blueprint $table): void {
            if (Schema::hasColumn('tdrs', 'tdr_type')) {
                $table->dropIndex(['tdr_type']);
                $table->dropColumn('tdr_type');
            }
        });
    }
};
