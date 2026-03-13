<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Один и тот же part_number может быть в разных manuals.
     * Уникальность: (manual_id, part_number).
     */
    public function up(): void
    {
        $table = 'units';

        // Удалить unique только на part_number (мешает одному PN в разных manuals)
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = 'part_number' AND Non_unique = 0");
        $dropped = [];
        foreach ($indexes as $idx) {
            $indexName = $idx->Key_name;
            if ($indexName !== 'PRIMARY' && !in_array($indexName, $dropped)) {
                $seq = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                if (count($seq) === 1) {
                    try {
                        Schema::table($table, fn(Blueprint $t) => $t->dropUnique($indexName));
                        $dropped[] = $indexName;
                        break;
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        }

        // Добавить composite unique (manual_id, part_number) — один PN в одном manual
        try {
            Schema::table($table, fn(Blueprint $t) =>
                $t->unique(['manual_id', 'part_number'], 'units_manual_id_part_number_unique'));
        } catch (\Throwable $e) {
            // уже существует
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', fn(Blueprint $t) =>
            $t->dropUnique('units_manual_id_part_number_unique'));
    }
};
