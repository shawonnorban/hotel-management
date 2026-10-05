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
        Schema::create('customerinfo', function (Blueprint $table) {
            $table->integer('customerid', true);
            $table->string('customernumber', 100)->nullable();
            $table->integer('membership_type')->nullable()->comment('1=bronze,2=silver,3=gold,4=platinum,5=vip');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->text('fathername')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('profession', 100)->nullable();
            $table->string('isnationality', 100)->nullable();
            $table->text('pid')->nullable();
            $table->text('pitype')->nullable();
            $table->string('imgfront', 100)->nullable();
            $table->string('imgback', 100)->nullable();
            $table->string('imgguest', 100)->nullable();
            $table->text('contacttype')->nullable();
            $table->string('zipcode', 100)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('passport', 120)->nullable();
            $table->string('visano', 80)->nullable();
            $table->string('purpose', 80)->nullable();
            $table->string('profileimage')->nullable();
            $table->string('city')->nullable();
            $table->string('gender')->nullable();
            $table->string('dob')->nullable();
            $table->text('anniversary')->nullable();
            $table->string('country')->nullable();
            $table->string('username')->nullable();
            $table->text('cust_phone')->nullable();
            $table->string('pass')->nullable();
            $table->decimal('balance', 10)->default(0);
            $table->integer('active')->nullable();
            $table->text('password_reset_token')->nullable();
            $table->date('signupdate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customerinfo');
    }
};
