<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EC gate / plan structure: classify each ProcessName by
 *   stage = start | prep | ndt | post | finish   (order; gate sits at `ndt`,
 *           EC holds everything after it: post + finish)
 *   scope = point | part                           (per-point rows vs one row)
 *
 * Localized treatments (machining, chrome/nickel strip & plate) are POINT-scope;
 * whole-part treatments (cad, NDT, shot peen, bake, paint, …) are PART-scope.
 *
 * Draft defaults below — review/adjust in the Processes UI. Unknown ones left null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->string('stage', 12)->nullable()->after('name'); // start|prep|ndt|post|finish
            $table->string('scope', 8)->nullable()->after('stage'); // point|part
        });

        // name => [stage, scope]
        $map = [
            'Stress Relief'           => ['start',  'part'],
            'Bake (Stress relief)'    => ['start',  'part'],
            'STD Stress relief List'  => ['start',  'part'],
            'Cad stripping'           => ['prep',   'part'],
            'Paint stripping'         => ['prep',   'part'],
            'Chrome stripping'        => ['prep',   'point'],
            'HVOF stripping'          => ['prep',   'point'],
            'Silver stripping'        => ['prep',   'point'],
            'E - Nickel stripping'    => ['prep',   'point'],
            'S - Nickel stripping'    => ['prep',   'point'],
            'Machining'               => ['prep',   'point'],
            'Machining (EC)'          => ['prep',   'point'],
            'S - Nickel Machining'    => ['prep',   'point'],
            'Nital Etch Inspection'   => ['ndt',    'part'],  // inspection on the whole part — like NDT
            'NDT-1'                   => ['ndt',    'part'],
            'NDT-4'                   => ['ndt',    'part'],
            'NDT-6'                   => ['ndt',    'part'],
            'NDT-7'                   => ['ndt',    'part'],
            'STD NDT List'            => ['ndt',    'part'],
            'Eddy Current Test'       => ['ndt',    'part'],
            'INSPECT'                 => ['ndt',    'part'],
            'Shot peening'            => ['post',   'part'],
            'Chrome plating'          => ['post',   'point'],
            'S - Nickel plating'      => ['post',   'point'],
            'E - Nickel plating'      => ['post',   'point'],
            'Silver plating'          => ['post',   'point'],
            'HVOF plating'            => ['post',   'point'],
            'Passivation'             => ['post',   'part'],
            'Anodizing'               => ['post',   'part'],
            'Cad plate'               => ['post',   'part'],
            'STD CAD List'            => ['post',   'part'],
            'Xylan coating'           => ['finish', 'part'],
            'Paint'                   => ['finish', 'part'],
            'STD Paint List'          => ['finish', 'part'],
            // EC marker — not a stage process
            'EC'                      => ['post',   'part'],
        ];

        foreach ($map as $name => [$stage, $scope]) {
            DB::table('process_names')->where('name', $name)->update(['stage' => $stage, 'scope' => $scope]);
        }
    }

    public function down(): void
    {
        Schema::table('process_names', function (Blueprint $table) {
            $table->dropColumn(['stage', 'scope']);
        });
    }
};
