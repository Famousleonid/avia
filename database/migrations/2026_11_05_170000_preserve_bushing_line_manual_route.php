<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wo_bushing_lines', fn (Blueprint $table) => $table->unsignedInteger('manual_route_number')->nullable());
        DB::table('wo_bushing_lines')
            ->join('wo_bushing_processes as p', 'p.wo_bushing_line_id', '=', 'wo_bushing_lines.id')
            ->join('wo_bushing_batches as b', 'b.id', '=', 'p.batch_id')
            ->where('b.manual_partition', true)
            ->update(['wo_bushing_lines.manual_route_number' => DB::raw('b.route_number')]);
    }

    public function down(): void
    {
        Schema::table('wo_bushing_lines', fn (Blueprint $table) => $table->dropColumn('manual_route_number'));
    }
};
