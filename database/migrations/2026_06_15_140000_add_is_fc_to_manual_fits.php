<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A manual_fit is the mating point of two parts (any OD↔ID coupling).
     * Whether it appears in the manual's Fits & Clearances table is just an
     * attribute, not a condition for the fit to exist: is_fc flags the ones the
     * F&C table/print should show (set from the point's is_fits_clearance at
     * detection). Non-F&C fits (e.g. bushing-OD↔housing press fits) still exist
     * and feed Required Bushings / Final Fit Report / oversize.
     */
    public function up()
    {
        Schema::table('manual_fits', function (Blueprint $table) {
            $table->boolean('is_fc')->default(true)->after('ref_no');
        });
    }

    public function down()
    {
        Schema::table('manual_fits', function (Blueprint $table) {
            $table->dropColumn('is_fc');
        });
    }
};
