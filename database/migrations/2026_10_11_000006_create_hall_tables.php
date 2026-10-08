<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hall_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->foreignId('type_id')->constrained('hall_types')->restrictOnDelete();
            $table->unsignedInteger('capacity');
            $table->decimal('rate_per_hour', 12, 2)->default(0);
            $table->decimal('rate_per_day', 12, 2)->default(0);
            $table->string('location', 120)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hall_facility_hall', function (Blueprint $table) {
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('hall_facilities')->cascadeOnDelete();
            $table->primary(['hall_id', 'facility_id']);
        });

        Schema::create('hall_seat_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('layout', ['theatre', 'classroom', 'banquet', 'u_shape', 'boardroom', 'cocktail']);
            $table->unsignedInteger('seats');
            $table->unsignedSmallInteger('tables')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hall_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('hall_id')->constrained('halls')->restrictOnDelete();
            $table->foreignId('seat_plan_id')->nullable()->constrained('hall_seat_plans')->nullOnDelete();
            $table->string('customer_name', 150);
            $table->string('phone', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('booking_number', 30)->nullable();
            $table->string('event_name', 150);
            $table->date('event_date')->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedInteger('guests')->default(1);
            $table->enum('status', ['tentative', 'confirmed', 'completed', 'cancelled'])->default('confirmed')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hall_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('hall_bookings')->cascadeOnDelete();
            $table->date('paid_on')->index();
            $table->decimal('amount', 12, 2);
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('reference', 80)->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['hall_payments', 'hall_bookings', 'hall_seat_plans', 'hall_facility_hall', 'halls', 'hall_facilities', 'hall_types'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
