<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quantum_ro_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('quantum');
            $table->string('bridge_id')->nullable();
            $table->string('status')->default('received');
            $table->json('filters')->nullable();
            $table->unsignedInteger('rows_received')->default(0);
            $table->unsignedInteger('rows_inserted')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_unchanged')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['source', 'created_at']);
            $table->index('status');
        });

        Schema::create('quantum_ro_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('last_sync_run_id')
                ->nullable()
                ->constrained('quantum_ro_sync_runs')
                ->nullOnDelete();

            $table->string('source_uid')->unique();
            $table->unsignedBigInteger('roh_auto_key')->nullable();
            $table->unsignedBigInteger('rod_auto_key')->nullable();
            $table->unsignedBigInteger('wob_auto_key')->nullable();
            $table->unsignedBigInteger('woo_auto_key')->nullable();
            $table->unsignedBigInteger('pnm_auto_key')->nullable();

            $table->string('ro_number', 40)->nullable();
            $table->string('wo_number', 40)->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('pn')->nullable();
            $table->text('description')->nullable();
            $table->string('class', 80)->nullable();

            $table->timestamp('entry_date')->nullable();
            $table->timestamp('out_date')->nullable();
            $table->timestamp('returned_date')->nullable();
            $table->timestamp('ro_last_modified')->nullable();
            $table->timestamp('detail_last_modified')->nullable();
            $table->timestamp('source_last_modified')->nullable();

            $table->decimal('qty_repair', 14, 4)->nullable();
            $table->decimal('qty_reserved', 14, 4)->nullable();
            $table->decimal('qty_repaired', 14, 4)->nullable();

            $table->string('source_hash', 64);
            $table->json('raw_payload')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('rod_auto_key');
            $table->index('roh_auto_key');
            $table->index('ro_number');
            $table->index('wo_number');
            $table->index('pn');
            $table->index('class');
            $table->index('returned_date');
            $table->index('source_last_modified');
            $table->index(['ro_number', 'returned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quantum_ro_lines');
        Schema::dropIfExists('quantum_ro_sync_runs');
    }
};
