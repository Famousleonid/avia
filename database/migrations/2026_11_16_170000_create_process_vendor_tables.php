<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('process_vendor_repair_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 80)->nullable()->unique();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('created_by')->constrained('users');
            $table->json('document');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('process_vendor_checks', function (Blueprint $table) {
            $table->id();
            $table->string('item_key', 160)->unique();
            $table->foreignId('workorder_id')->constrained('workorders');
            $table->char('fingerprint', 64);
            $table->foreignId('checked_by')->nullable()->constrained('users');
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('repair_order_id')->nullable()->constrained('process_vendor_repair_orders');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_vendor_checks');
        Schema::dropIfExists('process_vendor_repair_orders');
    }
};
