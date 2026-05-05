<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('active_ipl_num')
                ->nullable()
                ->storedAs('case when deleted_at is null then ipl_num else null end')
                ->after('ipl_num');

            $table->unique(['manual_id', 'active_ipl_num'], 'components_manual_active_ipl_unique');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropUnique('components_manual_active_ipl_unique');
            $table->dropColumn('active_ipl_num');
        });
    }
};
