<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EC dimensions sheet: a page documents a "place" (a parameter / point, e.g.
 * AA3.ID 11-10). A place may span 1–2 pages. When generating the EC drawing from
 * a Machining (EC) row, only the pages of that row's place are rendered → one PDF
 * per place. Null = a generic page included in any/whole-part generation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_document_pages', function (Blueprint $table) {
            $table->unsignedBigInteger('parameter_id')->nullable()->after('document_id');
            $table->index('parameter_id');
        });
    }

    public function down(): void
    {
        Schema::table('process_document_pages', function (Blueprint $table) {
            $table->dropIndex(['parameter_id']);
            $table->dropColumn('parameter_id');
        });
    }
};
