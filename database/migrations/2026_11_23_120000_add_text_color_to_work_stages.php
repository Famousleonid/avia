<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_stages', function (Blueprint $table) {
            $table->string('text_color', 7)->nullable();
        });

        DB::table('work_stages')->where('workflow_key', 'ovh_in_process')->update(['text_color' => '#cde1d4']);
    }

    public function down(): void
    {
        Schema::table('work_stages', function (Blueprint $table) {
            $table->dropColumn('text_color');
        });
    }
};
