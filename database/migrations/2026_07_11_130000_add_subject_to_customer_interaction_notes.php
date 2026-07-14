<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_interaction_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_interaction_notes', 'subject')) {
                $table->string('subject')->nullable()->after('contact_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_interaction_notes', function (Blueprint $table) {
            if (Schema::hasColumn('customer_interaction_notes', 'subject')) {
                $table->dropColumn('subject');
            }
        });
    }
};
