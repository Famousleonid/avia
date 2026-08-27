<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Linked part orders (docs/repair-routes-and-gates.md §5): a repair rule may
 * declare components to ORDER when the route is applied (e.g. both bearing-bore
 * routes order a new bearing). Applying the route proposes a linked Order New
 * TDR; tdrs.source_rule_id / source_tdr_id tie the auto-order to the rule and
 * to the carrier part's repair TDR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_parameter_rule_part_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repair_rule_id')->index();
            $table->unsignedBigInteger('component_id')->index();
            $table->unsignedInteger('qty')->default(1);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('tdrs', function (Blueprint $table) {
            $table->unsignedBigInteger('source_rule_id')->nullable()->after('replaced_by_tdr_id');
            $table->unsignedBigInteger('source_tdr_id')->nullable()->after('source_rule_id')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_parameter_rule_part_orders');
        Schema::table('tdrs', function (Blueprint $table) {
            $table->dropColumn(['source_rule_id', 'source_tdr_id']);
        });
    }
};
