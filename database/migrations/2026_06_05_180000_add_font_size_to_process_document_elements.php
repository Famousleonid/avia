<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-element font size (pt) for process-document labels/dimensions. Null = default. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_document_elements', function (Blueprint $table) {
            $table->unsignedSmallInteger('font_size')->nullable()->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('process_document_elements', function (Blueprint $table) {
            $table->dropColumn('font_size');
        });
    }
};
