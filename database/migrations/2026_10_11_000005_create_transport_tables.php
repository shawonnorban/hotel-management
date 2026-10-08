<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('reg_no', 40)->unique();
            $table->string('type', 40);
            $table->string('make_model', 120)->nullable();
            $table->unsignedSmallInteger('seats')->default(4);
            $table->string('driver_name', 120)->nullable();
            $table->string('driver_phone', 40)->nullable();
            $table->decimal('rate_per_km', 10, 2)->default(0);
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tr_flights', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name', 150);
            $table->string('booking_number', 30)->nullable()->index();
            $table->enum('direction', ['arrival', 'departure']);
            $table->string('airline', 80)->nullable();
            $table->string('flight_no', 20);
            $table->string('airport', 120)->nullable();
            $table->dateTime('flight_at')->index();
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tr_vehicle_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('tr_vehicles')->restrictOnDelete();
            $table->foreignId('flight_id')->nullable()->constrained('tr_flights')->nullOnDelete();
            $table->string('guest_name', 150);
            $table->string('booking_number', 30)->nullable()->index();
            $table->dateTime('pickup_at')->index();
            $table->dateTime('return_at')->nullable();
            $table->string('pickup_location', 191);
            $table->string('drop_location', 191);
            $table->decimal('distance_km', 10, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['booked', 'on_trip', 'completed', 'cancelled'])->default('booked');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_vehicle_bookings');
        Schema::dropIfExists('tr_flights');
        Schema::dropIfExists('tr_vehicles');
    }
};
