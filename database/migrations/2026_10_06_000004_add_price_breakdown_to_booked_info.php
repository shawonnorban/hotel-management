<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Keep the price breakdown with the booking so invoices stay correct when taxes or rates change later. */
    public function up(): void
    {
        Schema::table('booked_info', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->nullable()->after('total_price');
            $table->decimal('discount_amount', 12, 2)->nullable()->after('subtotal');
            $table->decimal('tax_amount', 12, 2)->nullable()->after('discount_amount');
            $table->decimal('service_amount', 12, 2)->nullable()->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('booked_info', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_amount', 'tax_amount', 'service_amount']);
        });
    }
};
