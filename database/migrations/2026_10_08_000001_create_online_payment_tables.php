<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gateway credentials live here (encrypted), not in the old plain-text paymentsetup table.
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 20)->unique(); // stripe, paypal, sslcommerz
            $table->unsignedInteger('payment_method_id')->nullable()->index();
            $table->boolean('live')->default(false);
            $table->string('currency', 3)->default('USD');
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->timestamps();
        });

        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bookedid')->index();
            $table->string('driver', 20);
            $table->string('reference', 120)->nullable()->index(); // gateway order / session / transaction id
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending')->index();
            $table->string('failure', 255)->nullable();
            $table->unsignedBigInteger('guest_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
        Schema::dropIfExists('payment_gateways');
    }
};
