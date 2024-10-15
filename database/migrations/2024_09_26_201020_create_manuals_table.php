<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('manuals', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('number')->unique();
            $table->string('title')->nullable();
            $table->string('img')->nullable();
            $table->date('revision_date')->nullable();
            $table->string('lib')->nullable();
            $table->boolean('active')->default(true);
            $table->string('units_pn')->nullable();
            $table->string('units_tr')->nullable();
            $table->foreignId('planes_id')->constrained()->onDelete('cascade');
            $table->foreignId('builders_id')->constrained()->onDelete('cascade');
            $table->foreignId('scopes_id')->constrained()->onDelete('cascade');

        });

        DB::table('manuals')->insert([
            'number' => '32-11-15RM',
            'lib' => '195',
            'units_pn' => '',
            'units_tr' => '',
            'planes_id' => 1,
            'builders_id' => 1,
            'scopes_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manuals');
    }
};
