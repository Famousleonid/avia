<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_manage_locked_manual_parts')
                ->default(false)
                ->after('can_manage_locked_manual_processes');
        });

        Schema::create('manual_part_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('manual_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_part_locks');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_manage_locked_manual_parts');
        });
    }
};
