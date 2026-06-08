<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formula value_source for process document dimension elements.
 *
 * formula_expression  — arithmetic expression with [p:ID] parameter refs,
 *                       e.g. "0.7128 - [p:45]" or "([p:12] + [p:13]) / 2"
 * formula_tolerance   — ± tolerance applied to the computed result;
 *                       result is displayed as "(value - tol) – (value + tol)"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_document_elements', function (Blueprint $table) {
            $table->text('formula_expression')->nullable()->after('source_parameter_id');
            $table->decimal('formula_tol_plus',  10, 4)->nullable()->after('formula_expression');
            $table->decimal('formula_tol_minus', 10, 4)->nullable()->after('formula_tol_plus');
        });
    }

    public function down(): void
    {
        Schema::table('process_document_elements', function (Blueprint $table) {
            $table->dropColumn(['formula_expression', 'formula_tol_plus', 'formula_tol_minus']);
        });
    }
};
