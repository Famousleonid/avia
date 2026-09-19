<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['general' => 'General', 'ec_approved' => 'EC Approved (OEM)'] as $key => $name) {
            DB::table('document_categories')->insert(['key' => $key, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_categories');
    }
};
