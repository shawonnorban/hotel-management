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
        Schema::create('sampledata', function (Blueprint $table) {
            $table->string('brand', 30);
            $table->string('dealer_name', 30);
            $table->string('authorized', 30);
            $table->string('address', 30);
            $table->string('contact_no', 30);
            $table->string('mobile_no', 30);
            $table->string('fax', 30);
            $table->string('email_id', 30);
            $table->string('website_addr', 30);
            $table->string('state', 30);
            $table->string('city', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sampledata');
    }
};
