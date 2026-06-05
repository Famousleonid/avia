<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (! Schema::hasColumn('workorders', 'shipping_shipment_at')) {
                $table->date('shipping_shipment_at')->nullable()->after('shipping_awb_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (Schema::hasColumn('workorders', 'shipping_shipment_at')) {
                $table->dropColumn('shipping_shipment_at');
            }
        });
    }
};
