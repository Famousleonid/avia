<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A Fit (Fits & Clearances, manual Table 8001) is a first-class pair of an
     * OD member and an ID member. Pairing is EXPLICIT (two parameter refs), not
     * inferred from a shared point — so the members may sit on different points
     * / IPL items, one OD can take part in several fits (a pin through several
     * parts), and a component can contribute its OD parameter to one fit and its
     * ID parameter to another. Clearances belong to the fit, not to either member.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manual_fits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')
                  ->constrained('manuals')
                  ->cascadeOnDelete();
            // Two members of the pair; both reference manual_parameters.
            $table->foreignId('od_param_id')
                  ->constrained('manual_parameters')
                  ->cascadeOnDelete();
            $table->foreignId('id_param_id')
                  ->constrained('manual_parameters')
                  ->cascadeOnDelete();
            // Optional human-readable fit reference from the manual (e.g. '8001-1');
            // some formats number the fit, others number only the members.
            $table->string('ref_no', 40)->nullable();
            // Clearances are properties of the FIT (legitimately differ per pair
            // because the mating member differs). Stored from the manual = source
            // of truth (reproduces the table); derivable from the pair limits as a
            // cross-check. Values in inches, decimal:4.
            $table->decimal('assembly_clearance_min', 10, 4)->nullable();
            $table->decimal('assembly_clearance_max', 10, 4)->nullable();
            $table->decimal('permitted_clearance', 10, 4)->nullable();  // = allowable (in-service)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manual_fits');
    }
};
