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
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('type');
        });

        DB::table('planes')->insert([
            ['type' => 'ERJ-175'],
            ['type' => 'ATR-72'],
            ['type' => 'ATR-42'],
            ['type' => 'ERJ-190/195'],
            ['type' => 'CL-601'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
