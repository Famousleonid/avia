<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('email')->nullable();
            $table->string('vendor_number', 100)->nullable();
            $table->string('ship_via', 100)->nullable();
            $table->string('terms', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', fn (Blueprint $table) => $table->dropColumn([
            'address', 'contact_name', 'phone', 'email', 'vendor_number', 'ship_via', 'terms',
        ]));
    }
};
