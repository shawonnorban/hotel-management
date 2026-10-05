<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booked_info', function (Blueprint $table) {
            $table->decimal('extras_amount', 12, 2)->default(0)->after('service_amount');
            $table->unsignedBigInteger('revenue_entry_id')->nullable()->after('extras_amount');
            $table->string('source', 30)->default('website')->after('revenue_entry_id');
        });

        Schema::table('tbl_guestpayments', function (Blueprint $table) {
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
        });

        Schema::table('payment_method', function (Blueprint $table) {
            $table->unsignedBigInteger('ledger_account_id')->nullable();
        });

        Schema::create('folio_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bookedid')->index();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('charged_on');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bookedid')->index();
            $table->string('event', 40);
            $table->string('detail')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_events');
        Schema::dropIfExists('folio_charges');
        Schema::table('payment_method', fn (Blueprint $t) => $t->dropColumn('ledger_account_id'));
        Schema::table('tbl_guestpayments', fn (Blueprint $t) => $t->dropColumn(['journal_entry_id', 'created_by']));
        Schema::table('booked_info', fn (Blueprint $t) => $t->dropColumn(['extras_amount', 'revenue_entry_id', 'source']));
    }
};
