<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SCA-вкладка матрицы тренингов:
 * - training_categories.is_sca — категории-курсы SCA (отдельный режим страницы);
 * - trainings.matrix_row_id — тренинг по строке-курсу (не CMM): manuals_id
 *   становится nullable, запись курса — даты без форм 112/132;
 * - сидинг стартовых курсов из SCA-секции Excel «MINIMUM REQUIREMENTS».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_categories', function (Blueprint $table) {
            $table->boolean('is_sca')->default(false)->after('sort_order');
        });

        DB::statement('ALTER TABLE `trainings` MODIFY `manuals_id` BIGINT UNSIGNED NULL');

        Schema::table('trainings', function (Blueprint $table) {
            $table->foreignId('matrix_row_id')->nullable()->after('manuals_id')
                ->constrained('training_matrix_rows')->nullOnDelete();
        });

        // Стартовые SCA-курсы (SCA-секция файла AUG 26); дальше ведутся через Add row.
        $categoryId = DB::table('training_categories')->insertGetId([
            'name' => 'SCA',
            'sort_order' => (int) DB::table('training_categories')->max('sort_order') + 1,
            'is_sca' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courses = [
            'Section 16.0 of MPM',
            'MP-07 "Shop Certificate Authority"',
            'MP-06 "Issuance and Control of Stamps"',
            'MP-03 "Final Inspection and Maintenance Release"',
            'MP-30 "ANAC Brazil"',
            'CAR573',
            'CAR571',
            'Bombardier QD4.60 Supplement',
            'CCAR145',
            'Audit Techniques',
        ];
        foreach ($courses as $i => $course) {
            DB::table('training_matrix_rows')->insert([
                'training_category_id' => $categoryId,
                'description' => null,
                'part_number' => $course,
                'sort_order' => $i + 1,
                'manual_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matrix_row_id');
        });

        $scaCategoryIds = DB::table('training_categories')->where('is_sca', true)->pluck('id');
        DB::table('training_matrix_rows')->whereIn('training_category_id', $scaCategoryIds)->delete();
        DB::table('training_categories')->whereIn('id', $scaCategoryIds)->delete();

        Schema::table('training_categories', function (Blueprint $table) {
            $table->dropColumn('is_sca');
        });
    }
};
