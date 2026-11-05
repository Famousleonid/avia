<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conditions', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
            $table->foreignId('replaces_id')->nullable()->constrained('conditions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conditions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaces_id');
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
