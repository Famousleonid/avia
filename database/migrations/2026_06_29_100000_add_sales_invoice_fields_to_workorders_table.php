<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (! Schema::hasColumn('workorders', 'sales_invoice_amount')) {
                $table->decimal('sales_invoice_amount', 14, 2)->nullable()->after('customer_po');
            }

            if (! Schema::hasColumn('workorders', 'sales_invoice_date')) {
                $table->date('sales_invoice_date')->nullable()->after('sales_invoice_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table): void {
            if (Schema::hasColumn('workorders', 'sales_invoice_date')) {
                $table->dropColumn('sales_invoice_date');
            }

            if (Schema::hasColumn('workorders', 'sales_invoice_amount')) {
                $table->dropColumn('sales_invoice_amount');
            }
        });
    }
};
