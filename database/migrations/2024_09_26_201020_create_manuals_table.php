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
            $table->date('revision_date')->nullable();
            $table->string('lib')->nullable();
            $table->boolean('active')->default(true);
            $table->string('units_pn')->nullable();
            $table->string('units_tr')->nullable();
            $table->foreignId('planes_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('builders_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('scopes_id')->nullable()->constrained()->onDelete('set null');
            $table->softDeletes();

        });

        $csvFile = public_path('data/units.csv');
        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file);
        $i=0;
        while (($row = fgetcsv($file)) !== false) {
            $i++;
            DB::table('manuals')->insert([
                'number' => '32-10-' . $i . 'TEST',
                'title' => '',
                'lib' => $row[4],
                'units_pn' => $row[1],
                'units_tr' => 'training name',
            ]);
        }
        fclose($file);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manuals');
    }
};
