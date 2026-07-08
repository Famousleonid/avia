<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_marketing_profiles', function (Blueprint $table) {
            $table->string('post_code', 40)->nullable()->after('state_province');
            $table->json('address_categories')->nullable()->after('company_notes');
        });
    }

    public function down(): void
    {
        Schema::table('customer_marketing_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'post_code',
                'address_categories',
            ]);
        });
    }
};
