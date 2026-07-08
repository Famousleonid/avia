<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-member F&C rows. Real Table 8001 contains rows without a mate:
     * the mating part lives in another manual (or in the aircraft structure),
     * or the row is a plain linear dimension (e.g. between bushing flanges).
     *
     * od_param_id / id_param_id become nullable — a fit now needs at least one
     * member, not both. single_kind labels the single member: 'od' | 'id' |
     * 'faces' (Between/Across Faces, linear); null for ordinary pairs. A
     * 'faces' member is stored in the od_param_id slot; single_kind is the
     * source of truth for its meaning.
     */
    public function up(): void
    {
        // FK columns: raw MODIFY keeps the existing foreign keys (FKs allow NULL)
        DB::statement('ALTER TABLE manual_fits MODIFY od_param_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE manual_fits MODIFY id_param_id BIGINT UNSIGNED NULL');

        Schema::table('manual_fits', function (Blueprint $table) {
            $table->string('single_kind', 8)->nullable()->after('id_param_id');
        });
    }

    public function down(): void
    {
        Schema::table('manual_fits', function (Blueprint $table) {
            $table->dropColumn('single_kind');
        });
        DB::statement('ALTER TABLE manual_fits MODIFY od_param_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE manual_fits MODIFY id_param_id BIGINT UNSIGNED NOT NULL');
    }
};
