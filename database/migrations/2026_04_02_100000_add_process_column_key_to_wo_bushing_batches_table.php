<?php

use App\Models\Process;
use App\Support\WoBushingProcessColumnKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            $table->string('process_column_key', 32)->nullable()->after('process_id');
        });

        DB::table('wo_bushing_batches')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                if (! $row->process_id) {
                    continue;
                }
                $p = Process::query()->find($row->process_id);
                $key = WoBushingProcessColumnKey::fromProcess($p);
                DB::table('wo_bushing_batches')->where('id', $row->id)->update(['process_column_key' => $key]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('wo_bushing_batches', function (Blueprint $table) {
            $table->dropColumn('process_column_key');
        });
    }
};
