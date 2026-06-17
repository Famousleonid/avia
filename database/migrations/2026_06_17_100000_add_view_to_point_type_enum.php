<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'view' to manual_dimension_points.point_type — the new directional
     * "View" arrow annotation (navigates to a child figure on click).
     */
    public function up()
    {
        DB::statement("ALTER TABLE `manual_dimension_points` MODIFY `point_type` ENUM('navigation','measurement','circle','text','view') NOT NULL DEFAULT 'measurement'");
    }

    public function down()
    {
        // Revert anything created as 'view' so the narrower enum still applies.
        DB::table('manual_dimension_points')->where('point_type', 'view')->update(['point_type' => 'navigation']);
        DB::statement("ALTER TABLE `manual_dimension_points` MODIFY `point_type` ENUM('navigation','measurement','circle','text') NOT NULL DEFAULT 'measurement'");
    }
};
