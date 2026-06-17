<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - extra_anchors: extra leader anchors for a callout/part label, so several
     *   arrows from different spots can point at one name. JSON list of {x_pct,y_pct}.
     * - rotation_deg: tilt for a navigation area rectangle.
     */
    public function up()
    {
        Schema::table('manual_dimension_points', function (Blueprint $table) {
            $table->json('extra_anchors')->nullable()->after('label_y_pct');
            $table->float('rotation_deg')->nullable()->default(0)->after('extra_anchors');
        });
    }

    public function down()
    {
        Schema::table('manual_dimension_points', function (Blueprint $table) {
            $table->dropColumn(['extra_anchors', 'rotation_deg']);
        });
    }
};
