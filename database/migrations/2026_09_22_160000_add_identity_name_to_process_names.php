<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('process_names', function (Blueprint $table): void {
            $table->string('identity_name')->nullable()->index();
        });
        // One-time capture only. Future display-name changes never remap IDs.
        foreach (DB::table('process_names')->select('id', 'name')->get() as $row) {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower($row->name));
            $identity = match ($key) {
                'machining', 'machiningat' => 'Machining',
                'machiningec' => 'Machining (EC)',
                default => trim($row->name),
            };
            DB::table('process_names')->where('id', $row->id)->update(['identity_name' => $identity]);
        }
    }

    public function down(): void
    {
        Schema::table('process_names', fn (Blueprint $table) => $table->dropColumn('identity_name'));
    }
};
