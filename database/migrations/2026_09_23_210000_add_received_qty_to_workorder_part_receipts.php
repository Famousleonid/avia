<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workorder_part_receipts', function (Blueprint $table) {
            $table->unsignedInteger('received_qty')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('workorder_part_receipts', fn (Blueprint $table) => $table->dropColumn('received_qty'));
    }
};
