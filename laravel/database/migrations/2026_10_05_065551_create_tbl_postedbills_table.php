<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_postedbills', function (Blueprint $table) {
            $table->integer('bill_id', true);
            $table->integer('bookedid');
            $table->text('taskname')->nullable();
            $table->text('rate')->nullable();
            $table->string('scharge', 100)->nullable();
            $table->decimal('complementary', 10)->nullable()->default(0);
            $table->decimal('credit')->nullable();
            $table->decimal('additional_charges', 10)->nullable()->default(0);
            $table->decimal('extrabpc', 10)->nullable()->default(0);
            $table->decimal('ex_discount', 10)->nullable()->default(0);
            $table->decimal('swimming_pool', 10)->nullable()->default(0);
            $table->decimal('restaurant', 10)->default(0);
            $table->decimal('hallroom', 10)->default(0);
            $table->decimal('car_parking', 10)->default(0);
            $table->decimal('special_discount', 10)->nullable()->default(0);
            $table->dateTime('checkoutdate')->nullable();
            $table->integer('days')->nullable();
            $table->decimal('amount', 11, 0)->nullable();
            $table->decimal('charge', 10)->nullable();
            $table->text('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_postedbills');
    }
};
