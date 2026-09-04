<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->string('default_scope_type', 32)->default('full_unit')->after('description');
            $table->unsignedBigInteger('default_scope_component_id')->nullable()->after('default_scope_type');
            $table->unsignedBigInteger('default_scope_part_group_option_id')->nullable()->after('default_scope_component_id');

            $table->foreign('default_scope_component_id', 'units_default_scope_component_fk')
                ->references('id')->on('components')->restrictOnDelete();
            $table->foreign('default_scope_part_group_option_id', 'units_default_scope_option_fk')
                ->references('id')->on('manual_part_group_options')->restrictOnDelete();
        });

        Schema::table('workorders', function (Blueprint $table): void {
            // NULL is reserved for workorders created before explicit scope snapshots existed.
            $table->string('scope_type', 32)->nullable()->after('unit_id');
            $table->unsignedBigInteger('scope_component_id')->nullable()->after('scope_type');
            $table->unsignedBigInteger('scope_part_group_option_id')->nullable()->after('scope_component_id');

            $table->foreign('scope_component_id', 'workorders_scope_component_fk')
                ->references('id')->on('components')->restrictOnDelete();
            $table->foreign('scope_part_group_option_id', 'workorders_scope_option_fk')
                ->references('id')->on('manual_part_group_options')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            $table->dropForeign('workorders_scope_component_fk');
            $table->dropForeign('workorders_scope_option_fk');
            $table->dropColumn(['scope_type', 'scope_component_id', 'scope_part_group_option_id']);
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->dropForeign('units_default_scope_component_fk');
            $table->dropForeign('units_default_scope_option_fk');
            $table->dropColumn([
                'default_scope_type',
                'default_scope_component_id',
                'default_scope_part_group_option_id',
            ]);
        });
    }
};
