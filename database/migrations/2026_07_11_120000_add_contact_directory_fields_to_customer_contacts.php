<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table): void {
            $table->string('email_2')->nullable()->after('email');
            $table->string('cell_phone')->nullable()->after('phone');
            $table->string('contact_type', 40)->nullable()->after('cell_phone');
        });
    }

    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table): void {
            $table->dropColumn(['email_2', 'cell_phone', 'contact_type']);
        });
    }
};
