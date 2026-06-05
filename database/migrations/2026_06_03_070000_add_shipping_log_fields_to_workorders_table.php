<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (! Schema::hasColumn('workorders', 'shipping_freight_forwarder')) {
                $table->string('shipping_freight_forwarder')->nullable()->after('customer_po');
            }

            if (! Schema::hasColumn('workorders', 'shipping_awb_no')) {
                $table->string('shipping_awb_no')->nullable()->after('shipping_freight_forwarder');
            }

            if (! Schema::hasColumn('workorders', 'shipping_notes')) {
                $table->text('shipping_notes')->nullable()->after('shipping_awb_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (Schema::hasColumn('workorders', 'shipping_notes')) {
                $table->dropColumn('shipping_notes');
            }

            if (Schema::hasColumn('workorders', 'shipping_awb_no')) {
                $table->dropColumn('shipping_awb_no');
            }

            if (Schema::hasColumn('workorders', 'shipping_freight_forwarder')) {
                $table->dropColumn('shipping_freight_forwarder');
            }
        });
    }
};
