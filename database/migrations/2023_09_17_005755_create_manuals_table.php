<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
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
            $table->string('number')->unique();
            $table->string('title')->nullable();
            $table->string('lib')->nullable();
            $table->date('revision_date')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('unit_name_training')->nullable();
            $table->string('training_hours')->nullable();
            $table->foreignId('planes_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('builders_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('scopes_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

        });

        $csvFile = public_path('data/manual.csv');
        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file, 0,';');
        $i=0;
        while (($row = fgetcsv($file,0,';')) !== false) {

            $rawDate = trim($row[3]);

            try {
                $revisionDate = Carbon::createFromFormat('d.M.Y', $rawDate)->format('Y-m-d');
            } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                $revisionDate = null;
            }

            DB::table('manuals')->insert([
                'number' => $row[0],
                'title' => $row[1],
                'lib' => $row[2],
                'revision_date' => $revisionDate,
                'unit_name' => $row[4],
                'unit_name_training' => $row[5],
                'training_hours' => $row[6],
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
